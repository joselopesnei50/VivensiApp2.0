<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BroadcastCampaign;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBroadcastCampaignJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MAX_RECIPIENTS = 500;

    public $campaignId;
    public $tenantId;
    public $timeout = 7200; // 2h — campanhas grandes precisam de mais tempo
    public $tries   = 1;
    public $failOnTimeout = true;

    public function __construct($campaignId, $tenantId)
    {
        $this->campaignId = $campaignId;
        $this->tenantId   = $tenantId;
        $this->onQueue('whatsapp');
    }

    public function uniqueId(): string
    {
        return (string) $this->campaignId;
    }

    public function handle()
    {
        $campaign = BroadcastCampaign::where('id', $this->campaignId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$campaign || $campaign->status === 'completed') {
            return;
        }

        $campaign->update(['status' => 'processing', 'started_at' => now()]);

        $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
            ->where('status', 'open')->first();

        if (!$instance) {
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            Log::error("Broadcast failed: No active instance for tenant {$campaign->tenant_id}");
            return;
        }

        $evo     = new EvolutionApiService($instance);
        $antiBan = new AntiBanManager($evo);

        // ── Pré-carrega dados de compliance LGPD (1 query cada, evita N+1 no loop) ──
        $config       = \App\Models\WhatsappConfig::where('tenant_id', $campaign->tenant_id)->first();
        $requireOptIn = (bool) ($config?->require_opt_in ?? false);

        // Blacklist como Set [phone => true] para lookup O(1)
        $blacklistSet = \App\Models\WhatsappBlacklist::where('tenant_id', $campaign->tenant_id)
            ->pluck('phone')
            ->mapWithKeys(fn ($p) => [(string) $p => true])
            ->all();

        $policy = app(\App\Services\WhatsappOutboundPolicy::class);

        // Para 'all': 3 queries leves (count, wa_ids, cursor) evitam carregar
        // milhares de contatos na memória de uma vez com get().
        if ($campaign->audience_type === 'all') {
            $baseQuery = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')->whereNull('blocked_at');

            $recipientCount = min((clone $baseQuery)->count(), self::MAX_RECIPIENTS);
            if ($recipientCount === 0) {
                $campaign->update(['status' => 'completed', 'completed_at' => now(), 'actual_recipients' => 0]);
                return;
            }

            $waIdsAll           = (clone $baseQuery)->limit(self::MAX_RECIPIENTS)->pluck('wa_id')->all();
            $recipientsIterable = (clone $baseQuery)->select(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at'])->limit(self::MAX_RECIPIENTS)->cursor();
        } else {
            $collection = $this->getRecipients($campaign, $evo)->take(self::MAX_RECIPIENTS);
            if ($collection->isEmpty()) {
                $campaign->update(['status' => 'completed', 'completed_at' => now(), 'actual_recipients' => 0]);
                return;
            }
            $recipientCount     = $collection->count();
            $waIdsAll           = $collection->pluck('wa_id')->all();
            $recipientsIterable = $collection;
        }

        // Rejeitar campanha antes de iniciar se contiver URL encurtada
        if ($antiBan->containsBlockedShortener($campaign->message ?? '')) {
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            Log::error("Broadcast bloqueado: mensagem contém URL encurtada (risco de ban)", ['campaign_id' => $campaign->id]);
            return;
        }

        $campaign->update(['actual_recipients' => $recipientCount]);

        $sentCount         = 0;
        $failedCount       = 0;
        $skippedCount      = 0;
        $consecutiveErrors = 0;

        $mediaToSend = null;
        $imageMime   = 'image/jpeg';
        
        if ($campaign->has_image && $campaign->image_path) {
            // Tentamos primeiro enviar a URL pública (mais leve, evita erro 413)
            $mediaToSend = Storage::disk('public')->url($campaign->image_path);
            
            // Se a URL não for absoluta (ex: /storage/...), prefixamos com o APP_URL
            if (!str_starts_with($mediaToSend, 'http')) {
                $mediaToSend = rtrim(config('app.url'), '/') . $mediaToSend;
            }

            if (Storage::disk('public')->exists($campaign->image_path)) {
                $imageMime = Storage::disk('public')->mimeType($campaign->image_path);
            }
        }

        // Para envios individuais (não grupo direto), valida e corrige JIDs via Evolution API
        // Resolve o problema do "9º dígito" brasileiro: entrega no celular depende do JID exato
        $isGroupChatMode = ($campaign->audience_type === 'groups')
            && (($campaign->group_send_mode ?? 'group') === 'group');

        $jidMap = [];
        if (!$isGroupChatMode) {
            $normalizedNumbers = collect($waIdsAll)
                ->map(fn($n) => EvolutionApiService::normalizeBrazilianPhone((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($normalizedNumbers)) {
                Log::info('Validando números no WhatsApp antes do disparo', [
                    'campaign_id' => $campaign->id,
                    'total'       => count($normalizedNumbers),
                ]);
                $jidMap = $evo->checkWhatsappNumbers($normalizedNumbers);
                Log::info('Validação concluída', [
                    'campaign_id' => $campaign->id,
                    'validos'     => count($jidMap),
                    'invalidos'   => count($normalizedNumbers) - count($jidMap),
                ]);

                // Cache os JIDs validados no banco para reuso futuro (opcional — não bloqueia o envio)
                if (!empty($jidMap)) {
                    foreach ($jidMap as $original => $jid) {
                        try {
                            WhatsappChat::where('tenant_id', $campaign->tenant_id)
                                ->whereRaw('`wa_id` = ?', [(string) $original])
                                ->update([
                                    'wa_jid'                => (string) $jid,
                                    'whatsapp_validated_at' => now(),
                                ]);
                        } catch (\Exception $e) {
                            Log::warning('Falha ao cachear wa_jid — broadcast continua', [
                                'wa_id' => $original,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($recipientsIterable as $recipient) {
            $campaign->refresh();
            if ($campaign->status !== 'processing') break;

            // ── Anti-ban: verifica janela de horário e limite diário ──────────
            if (!$antiBan->canSendMessage($instance)) {
                $instance->refresh();
                if (!$instance->isWithinSafeWindow()) {
                    // Fora da janela horária — salva progresso e encerra
                    // O operador deve reagendar dentro da janela configurada
                    $campaign->update([
                        'status'       => 'paused',
                        'completed_at' => now(),
                        'total_sent'   => $sentCount,
                        'total_failed' => $failedCount,
                    ]);
                    Log::info("Broadcast pausado: fora da janela horária — reagende dentro da janela", ['campaign_id' => $campaign->id, 'sent' => $sentCount]);
                    return;
                } else {
                    $campaign->update(['status' => 'paused', 'completed_at' => now(), 'total_sent' => $sentCount, 'total_failed' => $failedCount]);
                    Log::info("Broadcast pausado: limite diário/horário atingido", ['campaign_id' => $campaign->id, 'sent' => $sentCount]);
                    return;
                }
            }

            try {
                $rawWaId = $recipient->wa_id;

                if ($isGroupChatMode) {
                    $waId = $rawWaId;
                } else {
                    $normalized = EvolutionApiService::normalizeBrazilianPhone((string) $rawWaId);
                    if (!$normalized || !isset($jidMap[$normalized])) {
                        Log::warning('Número inválido ou não existe no WhatsApp — pulando', [
                            'campaign_id' => $campaign->id,
                            'wa_id'       => $rawWaId,
                        ]);
                        $failedCount++;
                        $campaign->update(['total_sent' => $sentCount, 'total_failed' => $failedCount]);
                        continue;
                    }
                    $waId = $jidMap[$normalized];

                    // ── Compliance LGPD — usa complianceStatus() (mesma regra do chat individual) ──
                    $blockCode = $policy->complianceStatus(
                        $requireOptIn,
                        $recipient->opt_in_at ?? null,
                        $recipient->opt_out_at ?? null,
                        $recipient->blocked_at ?? null,
                        isset($blacklistSet[$normalized]) || isset($blacklistSet[$waId])
                    );

                    if ($blockCode) {
                        Log::info('Broadcast: contato bloqueado por compliance — pulando', [
                            'campaign_id' => $campaign->id,
                            'wa_id'       => substr($waId, 0, -4) . '****',
                            'reason'      => $blockCode,
                        ]);
                        if (!empty($recipient->id)) {
                            try {
                                \App\Models\WhatsappAuditLog::create([
                                    'tenant_id'  => $campaign->tenant_id,
                                    'chat_id'    => $recipient->id,
                                    'actor_type' => 'system',
                                    'event'      => 'broadcast_skipped',
                                    'details'    => ['reason' => $blockCode, 'campaign_id' => $campaign->id],
                                ]);
                            } catch (\Throwable) {}
                        }
                        $skippedCount++;
                        $campaign->update(['total_skipped' => $skippedCount, 'total_sent' => $sentCount, 'total_failed' => $failedCount]);
                        continue;
                    }
                }

                // ── Anti-ban: simula digitação antes do envio (apenas individuais) ──
                if (!$isGroupChatMode) {
                    $antiBan->simulateHumanTyping($instance, $waId, $campaign->message);
                }

                // delay=0 nos individuais: simulateHumanTyping já cobriu o tempo orgânico.
                // Tarefa 3.3 da auditoria — evita double-counting (era rand(2,5) antes,
                // somando 2-5s redundantes ao envio).
                // Em grupo (sem simulateHumanTyping), passa rand(2,5) como delay nativo.
                $delayForApi = $isGroupChatMode ? rand(2, 5) : 0;

                $res = $mediaToSend
                    ? $evo->sendMedia($waId, $mediaToSend, $campaign->message, $imageMime)
                    : $evo->sendMessage($waId, $campaign->message, null, $delayForApi);

                if (!isset($res['error']) && !empty($res)) {
                    if (isset($recipient->id)) {
                        WhatsappMessage::create([
                            'chat_id'    => $recipient->id,
                            'message_id' => $res['key']['id'] ?? ($res['messageId'] ?? ('BROADCAST_' . uniqid())),
                            'content'    => $campaign->has_image ? ('[imagem] ' . $campaign->message) : $campaign->message,
                            'direction'  => 'outbound',
                            'type'       => $campaign->has_image ? 'image' : 'text',
                        ]);
                    }
                    $sentCount++;
                    $consecutiveErrors = 0;
                    $antiBan->recordSent($instance); // contabiliza no limite diário
                } else {
                    $errorMsg = is_array($res) ? json_encode($res) : ($res ?: 'Unknown Error');
                    Log::warning("Broadcast failed for {$waId}. Campaign ID: {$campaign->id}. Error: " . $errorMsg);
                    $failedCount++;
                    $consecutiveErrors++;

                    // ── Circuit breaker: detecta sinal de ban / rate limit ────────
                    if ($antiBan->isBanSignal($res)) {
                        Log::critical("Broadcast: sinal de ban detectado — instância restrita por 24h, campanha encerrada", [
                            'campaign_id' => $campaign->id,
                            'sent'        => $sentCount,
                            'response'    => substr(json_encode($res), 0, 300),
                        ]);
                        // Marca instância como restrita por 24h — NÃO dormimos no worker
                        $antiBan->markAsRestricted($instance, 24);
                        $campaign->update([
                            'status'       => 'failed',
                            'completed_at' => now(),
                            'total_sent'   => $sentCount,
                            'total_failed' => $failedCount,
                        ]);
                        return;
                    }

                    // 5 erros consecutivos sem sinal de ban → parar campanha
                    if ($consecutiveErrors >= 5) {
                        Log::error("Broadcast encerrado: 5 erros consecutivos na API", ['campaign_id' => $campaign->id]);
                        $campaign->update(['status' => 'failed', 'completed_at' => now(), 'total_sent' => $sentCount, 'total_failed' => $failedCount]);
                        return;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Broadcast recipient exception. Campaign ID: {$campaign->id}. Message: " . $e->getMessage());
                $failedCount++;
                $consecutiveErrors++;
            }

            $campaign->update([
                'total_sent'   => $sentCount,
                'total_failed' => $failedCount,
            ]);

            // ── Anti-ban: delay entre mensagens (mínimo 5s, orgânico) ───────
            $minDelay = max(5, $campaign->cadence ?: 5);
            sleep(rand($minDelay, $minDelay + 10));

            // ── Anti-ban: pausa de 3-5 min a cada 30 mensagens ──────────────
            if ($sentCount > 0 && $sentCount % 30 === 0) {
                $pause = rand(180, 300);
                Log::info("Broadcast: pausa anti-ban ({$pause}s) após 30 msgs", ['campaign_id' => $campaign->id, 'sent' => $sentCount]);
                sleep($pause);
            }
        }

        $campaign->update(['status' => 'completed', 'completed_at' => now(), 'total_skipped' => $skippedCount]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBroadcastCampaignJob falhou definitivamente", [
            'campaign_id' => $this->campaignId,
            'error'       => $exception->getMessage(),
        ]);

        $campaign = BroadcastCampaign::where('id', $this->campaignId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if ($campaign && in_array($campaign->status, ['queued', 'processing'])) {
            $campaign->update([
                'status'       => 'failed',
                'completed_at' => now(),
            ]);
        }
    }

    protected function getRecipients($campaign, EvolutionApiService $evo = null)
    {
        if ($campaign->audience_type === 'groups') {
            $groupIds = $campaign->group_ids ?: [];

            // Modo "members": expande cada grupo nos seus membros individuais
            if (($campaign->group_send_mode ?? 'group') === 'members') {
                Log::info('Broadcast members mode: iniciando expansão de grupos', [
                    'campaign_id' => $campaign->id,
                    'group_count' => count($groupIds),
                ]);

                if (!$evo) {
                    $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
                        ->where('status', 'open')->first();
                    if (!$instance) {
                        Log::error("Broadcast members mode: no active instance for tenant {$campaign->tenant_id}");
                        return collect();
                    }
                    $evo = new EvolutionApiService($instance);
                }
                $members = collect();
                $allPhones = [];

                foreach ($groupIds as $groupId) {
                    $jids = $evo->getGroupMembers($groupId);
                    Log::info('Broadcast members mode: grupo expandido', [
                        'campaign_id' => $campaign->id,
                        'group_id'    => $groupId,
                        'jids_count'  => count($jids),
                    ]);
                    foreach ($jids as $jid) {
                        $allPhones[] = str_replace('@s.whatsapp.net', '', $jid);
                    }
                }

                // Uma única query para todos os membros (evita N+1)
                $allPhones = array_unique($allPhones);
                $chatsMap = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                    ->whereIn('wa_id', $allPhones)
                    ->get(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at'])
                    ->keyBy('wa_id');

                foreach ($allPhones as $phone) {
                    $chat = $chatsMap->get($phone);
                    $members->push((object)[
                        'wa_id'      => $phone,
                        'id'         => $chat?->id,
                        'opt_in_at'  => $chat?->opt_in_at,
                        'opt_out_at' => $chat?->opt_out_at,
                        'blocked_at' => $chat?->blocked_at,
                    ]);
                }

                $unique = $members->unique('wa_id')->values();

                Log::info('Broadcast members mode: total final', [
                    'campaign_id'  => $campaign->id,
                    'total_unique' => $unique->count(),
                ]);

                if ($unique->isEmpty() && !empty($groupIds)) {
                    Log::error("Broadcast members mode: nenhum membro retornado para campanha #{$campaign->id}. Verifique permissões do bot ou versão Evolution API.");
                }

                return $unique;
            }

            // Modo "group": envia UMA mensagem para o chat do grupo
            return collect($groupIds)->map(fn($id) => (object)['wa_id' => $id, 'id' => null]);
        }

        if ($campaign->audience_type === 'selected' && $campaign->phones) {
            $phones = array_map(
                fn($p) => preg_replace('/\D+/', '', $p),
                explode(',', $campaign->phones)
            );
            $phones = array_filter($phones, fn($p) => strlen($p) >= 10);

            $existingChats = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                ->whereIn('wa_id', $phones)
                ->get(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at'])
                ->keyBy('wa_id');

            // Compliance (opt_out, blocked, opt_in, blacklist) é verificado no loop principal
            return collect($phones)->map(function ($phone) use ($existingChats) {
                $chat = $existingChats->get($phone);
                return (object)[
                    'wa_id'      => $phone,
                    'id'         => $chat?->id,
                    'opt_in_at'  => $chat?->opt_in_at,
                    'opt_out_at' => $chat?->opt_out_at,
                    'blocked_at' => $chat?->blocked_at,
                ];
            })->values();
        }

        return WhatsappChat::where('tenant_id', $campaign->tenant_id)
            ->whereNull('opt_out_at')->whereNull('blocked_at')
            ->get(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at']);
    }
}
