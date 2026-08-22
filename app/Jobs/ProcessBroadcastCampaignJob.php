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
    const CHUNK_SIZE     = 20;   // mensagens por execução de job

    /**
     * Volume maximo por campanha quando ha audio. Broadcast de audio identico
     * pra muitos contatos e vetor classico de ban Meta/Evolution — trava
     * espelhada em BroadcastCampaign::AUDIO_MAX_RECIPIENTS_PER_CAMPAIGN.
     */
    const AUDIO_MAX_RECIPIENTS = 100;

    public $campaignId;
    public $tenantId;
    public $offset   = 0;    // posição no array de destinatários
    public $timeout  = 1800; // 30min por chunk — cobre até 20 msgs × 60s (Evolution lenta)
    public $tries    = 3;    // retenta o chunk em falhas transitórias
    public $failOnTimeout = true;

    public function __construct($campaignId, $tenantId, $offset = 0)
    {
        $this->campaignId = $campaignId;
        $this->tenantId   = $tenantId;
        $this->offset     = $offset;
        $this->onQueue('whatsapp');
    }

    public function uniqueId(): string
    {
        // Inclui offset: impede dispatch duplo do mesmo chunk; chunks distintos coexistem na fila
        return $this->campaignId . '_' . $this->offset;
    }

    public function handle()
    {
        $campaign = BroadcastCampaign::where('id', $this->campaignId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$campaign || in_array($campaign->status, ['completed', 'failed'])) {
            return;
        }

        // Primeiro chunk: inicializa estado; chunks seguintes apenas verificam status
        if ($this->offset === 0) {
            $campaign->update(['status' => 'processing', 'started_at' => now()]);
        } elseif ($campaign->status !== 'processing') {
            // Campanha foi pausada/cancelada entre chunks
            Log::info("Broadcast chunk abortado: status={$campaign->status}", ['campaign_id' => $campaign->id, 'offset' => $this->offset]);
            return;
        }

        // Broadcast v1 Cloud API template (2026-08-21): resolve a instancia
        // Cloud da tenant (nao depende de status='open' — a Cloud API nao tem
        // sessao WhatsApp Web, so credenciais Graph API).
        $isCloudTemplate = $campaign->send_channel === BroadcastCampaign::CHANNEL_CLOUD_API_TEMPLATE;
        $template        = null;
        $cloudSender     = null;

        if ($isCloudTemplate) {
            $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
                ->whereNotNull('waba_id')
                ->whereNotNull('graph_access_token')
                ->first();

            if (!$instance) {
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                Log::error("Broadcast Cloud template failed: no Cloud API instance for tenant {$campaign->tenant_id}");
                return;
            }

            $template = \App\Models\WhatsappTemplate::withoutGlobalScope('tenant')
                ->where('id', $campaign->template_id)
                ->where('tenant_id', $campaign->tenant_id)
                ->where('status', \App\Models\WhatsappTemplate::STATUS_APPROVED)
                ->first();

            if (!$template) {
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                Log::error("Broadcast Cloud template failed: template {$campaign->template_id} nao encontrado/APPROVED", [
                    'campaign_id' => $campaign->id,
                    'tenant_id'   => $campaign->tenant_id,
                ]);
                return;
            }

            $cloudSender = \App\Services\WhatsApp\WhatsAppSenderFactory::forInstance($instance);
        } else {
            $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
                ->where('status', 'open')->first();

            if (!$instance) {
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                Log::error("Broadcast failed: No active instance for tenant {$campaign->tenant_id}");
                return;
            }
        }

        $evo     = new EvolutionApiService($instance);
        $antiBan = new AntiBanManager($evo);

        // Pré-carrega dados de compliance LGPD (1 query cada, evita N+1 no loop)
        $config       = \App\Models\WhatsappConfig::where('tenant_id', $campaign->tenant_id)->first();
        $requireOptIn = (bool) ($config?->require_opt_in ?? false);

        $blacklistSet = \App\Models\WhatsappBlacklist::where('tenant_id', $campaign->tenant_id)
            ->pluck('phone')
            ->mapWithKeys(fn ($p) => [(string) $p => true])
            ->all();

        $policy = app(\App\Services\WhatsappOutboundPolicy::class);

        // Broadcast de audio: cap e janela 24h dependem do tipo de audience.
        // Individual: cap 100 + so contatos com inbound recente (evita spam frio).
        // Groups: cap 10 grupos + sem janela (membros ja opted-in ao entrar).
        $isAudioBroadcast    = (bool) $campaign->has_audio;
        $isAudioIndividual   = $isAudioBroadcast && $campaign->audience_type !== 'groups';
        $isAudioToGroups     = $isAudioBroadcast && $campaign->audience_type === 'groups';
        $effectiveMaxRecips  = self::MAX_RECIPIENTS;
        if ($isAudioIndividual) {
            $effectiveMaxRecips = self::AUDIO_MAX_RECIPIENTS;
        } elseif ($isAudioToGroups) {
            $effectiveMaxRecips = BroadcastCampaign::AUDIO_MAX_GROUPS_PER_CAMPAIGN;
        }
        $audioWindowStart = $isAudioIndividual
            ? now()->subHours(BroadcastCampaign::AUDIO_ACTIVE_WINDOW_HOURS)
            : null;

        // ── Carrega apenas o chunk atual de destinatários ──────────────────────
        if ($campaign->audience_type === 'all') {
            $baseQuery = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')->whereNull('blocked_at');

            if ($isAudioIndividual) {
                // Janela ativa: so contatos que enviaram inbound nas ultimas 24h.
                // Reduz drasticamente a chance de o audio soar como spam frio.
                $baseQuery->whereNotNull('last_inbound_at')
                          ->where('last_inbound_at', '>=', $audioWindowStart);
            }

            $recipientCount = min((clone $baseQuery)->count(), $effectiveMaxRecips);

            if ($recipientCount === 0) {
                $campaign->update(['status' => 'completed', 'completed_at' => now(), 'actual_recipients' => 0]);
                return;
            }

            if ($this->offset === 0) {
                $campaign->update(['actual_recipients' => $recipientCount]);
            }

            $recipientsIterable = (clone $baseQuery)
                ->select(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at', 'last_inbound_at'])
                ->orderBy('id')
                ->offset($this->offset)
                ->limit(self::CHUNK_SIZE)
                ->get();
        } else {
            $fullCollection = $this->getRecipients($campaign, $evo);

            if ($isAudioIndividual) {
                // Filtra fora da janela 24h APOS montar (getRecipients tem forma variavel).
                // NAO aplicavel a groups (grupos nao tem last_inbound_at por participante).
                $fullCollection = $fullCollection->filter(function ($r) use ($audioWindowStart) {
                    $li = $r->last_inbound_at ?? null;
                    return $li && \Illuminate\Support\Carbon::parse($li)->gte($audioWindowStart);
                })->values();
            }

            $fullCollection = $fullCollection->take($effectiveMaxRecips);

            if ($fullCollection->isEmpty()) {
                $campaign->update(['status' => 'completed', 'completed_at' => now(), 'actual_recipients' => 0]);
                return;
            }

            $recipientCount = $fullCollection->count();

            if ($this->offset === 0) {
                $campaign->update(['actual_recipients' => $recipientCount]);
            }

            $recipientsIterable = $fullCollection->slice($this->offset, self::CHUNK_SIZE)->values();
        }

        if ($recipientsIterable->isEmpty()) {
            $campaign->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }

        // Rejeitar campanha antes de iniciar se contiver URL encurtada
        if ($this->offset === 0 && $antiBan->containsBlockedShortener($campaign->message ?? '')) {
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            Log::error("Broadcast bloqueado: mensagem contém URL encurtada (risco de ban)", ['campaign_id' => $campaign->id]);
            return;
        }

        // Captura contagens anteriores (chunks já processados) antes de qualquer update no loop
        $baseSent    = $campaign->total_sent    ?? 0;
        $baseFailed  = $campaign->total_failed  ?? 0;
        $baseSkipped = $campaign->total_skipped ?? 0;

        $sentCount         = 0;
        $failedCount       = 0;
        $skippedCount      = 0;
        $consecutiveErrors = 0;

        // Pre-carrega binario do audio uma unica vez fora do loop (base64 pesado —
        // Evolution API aceita audio em base64 direto via sendWhatsAppAudio).
        $audioBase64 = null;
        if ($isAudioBroadcast && $campaign->audio_path) {
            if (Storage::disk('local')->exists($campaign->audio_path)) {
                $audioBase64 = base64_encode(Storage::disk('local')->get($campaign->audio_path));
            } else {
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                Log::error("Broadcast audio failed: arquivo nao encontrado no storage", [
                    'campaign_id' => $campaign->id,
                    'audio_path'  => $campaign->audio_path,
                ]);
                return;
            }
        }

        $mediaToSend = null;
        $imageMime   = 'image/jpeg';

        // Fix 2026-08-11: sem silent fallback pra texto quando a imagem sumiu.
        // Antes: has_image=1 + arquivo ausente => enviava so texto e marcava
        // como sucesso (cliente via "OK" mas destinatario recebia sem imagem).
        // Agora falha explicito com log critico no primeiro chunk.
        if ($campaign->has_image) {
            if (!$campaign->image_path) {
                Log::critical('Broadcast: has_image=true mas image_path e NULL — DB inconsistente', [
                    'campaign_id' => $campaign->id,
                    'tenant_id'   => $campaign->tenant_id,
                ]);
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                return;
            }

            if (!Storage::disk('public')->exists($campaign->image_path)) {
                Log::critical('Broadcast: imagem configurada mas arquivo nao existe no disk public', [
                    'campaign_id' => $campaign->id,
                    'tenant_id'   => $campaign->tenant_id,
                    'image_path'  => $campaign->image_path,
                ]);
                $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                return;
            }

            $mediaToSend = Storage::disk('public')->url($campaign->image_path);
            if (!str_starts_with($mediaToSend, 'http')) {
                $mediaToSend = rtrim(config('app.url'), '/') . $mediaToSend;
            }

            // Deriva mime real; fallback pela extensao do path se leitura falhar.
            try {
                $imageMime = Storage::disk('public')->mimeType($campaign->image_path) ?: $imageMime;
            } catch (\Throwable) {
                $ext = strtolower(pathinfo($campaign->image_path, PATHINFO_EXTENSION));
                $extMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
                $imageMime = $extMap[$ext] ?? 'image/jpeg';
            }
        }

        $isGroupChatMode = ($campaign->audience_type === 'groups')
            && (($campaign->group_send_mode ?? 'group') === 'group');

        // Anti-ban: avalia diversidade no lote completo ANTES de reduzir o chunk.
        // Cloud API template dispensa (Meta faz rate limiting proprio + template aprovado).
        $waIdsChunk       = $recipientsIterable->pluck('wa_id')->all();
        $conservativeMode = false;
        if (!$isGroupChatMode && !$isCloudTemplate && !empty($waIdsChunk)) {
            $newRatio = $antiBan->getNewRecipientRatio($instance, $waIdsChunk);
            if ($newRatio > AntiBanManager::NEW_RECIPIENT_RISK_THRESHOLD) {
                $conservativeMode = true;
                Log::warning('AntiBan: audiência majoritariamente nova — modo conservador ativado', [
                    'campaign_id' => $campaign->id,
                    'offset'      => $this->offset,
                    'new_ratio'   => round($newRatio, 2),
                ]);
            }
        }

        // Chunk size dinâmico: garante que o chunk cabe no $timeout=900s com margem
        // Fórmula: 800s disponíveis ÷ delay máximo por mensagem
        $minDelay           = max(5, $campaign->cadence ?: 5);
        $multiplier         = $conservativeMode ? 2 : 1;
        $maxMsgDelay        = ($minDelay + 10) * $multiplier;
        $effectiveChunkSize = min(self::CHUNK_SIZE, max(3, (int) floor(800 / max(1, $maxMsgDelay))));

        if ($effectiveChunkSize < self::CHUNK_SIZE) {
            $recipientsIterable = $recipientsIterable->take($effectiveChunkSize);
            Log::info('Broadcast: chunk reduzido por cadência/modo conservador', [
                'campaign_id'       => $campaign->id,
                'effective_chunk'   => $effectiveChunkSize,
                'max_delay_per_msg' => $maxMsgDelay,
                'conservative'      => $conservativeMode,
            ]);
        }

        // Valida apenas os números do chunk efetivo (após redução)
        $waIdsChunk = $recipientsIterable->pluck('wa_id')->all();
        $jidMap     = [];

        if (!$isGroupChatMode && !$isCloudTemplate) {
            $normalizedNumbers = collect($waIdsChunk)
                ->map(fn($n) => EvolutionApiService::normalizeBrazilianPhone((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($normalizedNumbers)) {
                Log::info('Validando números do chunk no WhatsApp', [
                    'campaign_id' => $campaign->id,
                    'offset'      => $this->offset,
                    'total'       => count($normalizedNumbers),
                ]);
                $jidMap = $evo->checkWhatsappNumbers($normalizedNumbers);

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

            // Anti-ban: fingerprint de conteúdo (Fase 1 2026).
            // Cloud template dispensa — Meta valida template previamente na aprovacao.
            if (!$isCloudTemplate && !empty($campaign->message) && !$antiBan->contentFingerprintAllowed($instance, $campaign->message)) {
                Log::warning('AntiBan: fingerprint de conteúdo atingiu limite diário — campanha pausada', [
                    'campaign_id' => $campaign->id,
                    'instance_id' => $instance->id,
                ]);
                $campaign->update([
                    'status'       => 'paused',
                    'completed_at' => now(),
                    'total_sent'   => $baseSent + $sentCount,
                    'total_failed' => $baseFailed + $failedCount,
                ]);
                return;
            }

            // Anti-ban: fingerprint de audio (mesmo hash sha256 do binario ja salvo).
            // Trata o `audio_fingerprint` como se fosse um "conteudo" pro cache do
            // AntiBanManager — reusa a mesma logica de limite diario (MAX_SAME_CONTENT_PER_DAY),
            // mas com trava propria mais estrita (AUDIO_MAX_SAME_FINGERPRINT_DAY) aplicada
            // ao final via contagem separada da chave de audio.
            if ($isAudioBroadcast) {
                $audioKey  = 'wa:audio_fp:' . $instance->id . ':' . $campaign->audio_fingerprint;
                $audioSent = (int) \Illuminate\Support\Facades\Cache::get($audioKey, 0);
                if ($audioSent >= BroadcastCampaign::AUDIO_MAX_SAME_FINGERPRINT_DAY) {
                    Log::warning('AntiBan: fingerprint de audio atingiu limite diario — campanha pausada', [
                        'campaign_id' => $campaign->id,
                        'instance_id' => $instance->id,
                    ]);
                    $campaign->update([
                        'status'       => 'paused',
                        'completed_at' => now(),
                        'total_sent'   => $baseSent + $sentCount,
                        'total_failed' => $baseFailed + $failedCount,
                    ]);
                    return;
                }
            }

            // Anti-ban: verifica janela de horário e limite diário.
            // Cloud template pula esse gate — a Meta Cloud API tem rate limit
            // proprio e nao tem sessao WhatsApp Web pra "adormecer".
            if (!$isCloudTemplate && !$antiBan->canSendMessage($instance)) {
                $instance->refresh();
                $statusMsg = $instance->isWithinSafeWindow() ? 'limite diário/horário atingido' : 'fora da janela horária';
                $campaign->update([
                    'status'       => 'paused',
                    'completed_at' => now(),
                    'total_sent'   => $baseSent + $sentCount,
                    'total_failed' => $baseFailed + $failedCount,
                ]);
                Log::info("Broadcast pausado: {$statusMsg}", ['campaign_id' => $campaign->id, 'sent' => $sentCount]);
                return;
            }

            try {
                $rawWaId = $recipient->wa_id;

                if ($isGroupChatMode) {
                    $waId = $rawWaId;
                } elseif ($isCloudTemplate) {
                    // Cloud API aceita numero E.164 direto (sem checar via Evolution).
                    // Compliance LGPD (opt-in, blacklist) continua valendo.
                    $normalized = preg_replace('/\D+/', '', (string) $rawWaId);
                    if (!$normalized) {
                        $failedCount++;
                        continue;
                    }
                    $waId = $normalized;

                    $blockCode = $policy->complianceStatus(
                        $requireOptIn,
                        $recipient->opt_in_at ?? null,
                        $recipient->opt_out_at ?? null,
                        $recipient->blocked_at ?? null,
                        isset($blacklistSet[$normalized]) || isset($blacklistSet[$waId])
                    );
                    if ($blockCode) {
                        Log::info('Broadcast Cloud template: contato bloqueado por compliance — pulando', [
                            'campaign_id' => $campaign->id,
                            'reason'      => $blockCode,
                        ]);
                        $skippedCount++;
                        $campaign->update([
                            'total_skipped' => $baseSkipped + $skippedCount,
                            'total_sent'    => $baseSent + $sentCount,
                            'total_failed'  => $baseFailed + $failedCount,
                        ]);
                        continue;
                    }
                } else {
                    $normalized = EvolutionApiService::normalizeBrazilianPhone((string) $rawWaId);
                    if (!$normalized || !isset($jidMap[$normalized])) {
                        Log::warning('Número inválido ou não existe no WhatsApp — pulando', [
                            'campaign_id' => $campaign->id,
                            'wa_id'       => $rawWaId,
                        ]);
                        $failedCount++;
                        $campaign->update([
                            'total_sent'   => $baseSent + $sentCount,
                            'total_failed' => $baseFailed + $failedCount,
                        ]);
                        continue;
                    }
                    $waId = $jidMap[$normalized];

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
                        // Fix 2026-08-19: sempre grava audit log, mesmo quando recipient
                        // e numero avulso (audience=selected sem chat cadastrado). Antes
                        // o `if (!empty($recipient->id))` engolia esses skips e cliente
                        // via "N ignorados" sem rastro. wa_id vai em details pra
                        // rastreabilidade sem depender do chat_id.
                        try {
                            \App\Models\WhatsappAuditLog::create([
                                'tenant_id'  => $campaign->tenant_id,
                                'chat_id'    => $recipient->id ?? null,
                                'actor_type' => 'system',
                                'event'      => 'broadcast_skipped',
                                'details'    => [
                                    'reason'      => $blockCode,
                                    'campaign_id' => $campaign->id,
                                    'wa_id'       => $waId,
                                ],
                            ]);
                        } catch (\Throwable) {}
                        $skippedCount++;
                        $campaign->update([
                            'total_skipped' => $baseSkipped + $skippedCount,
                            'total_sent'    => $baseSent + $sentCount,
                            'total_failed'  => $baseFailed + $failedCount,
                        ]);
                        continue;
                    }
                }

                // Anti-ban: simula digitação antes do envio (apenas individuais Evolution).
                // Cloud API template nao precisa (Meta oficial + sem sessao Web).
                if (!$isGroupChatMode && !$isCloudTemplate) {
                    $antiBan->simulateHumanTyping($instance, $waId, $campaign->message);
                }

                $delayForApi = $isGroupChatMode ? rand(2, 5) : 0;

                // Coerce null -> '' aqui pra intencao ficar explicita no chamador
                // (defesa 1 — sendMedia/sendMessage tambem toleram null como defesa 2).
                $captionOrText = (string) ($campaign->message ?? '');

                if ($isCloudTemplate) {
                    // sendTemplate espera lista ordenada, nao assoc (Meta pareia
                    // por indice: [0] = {{1}}, [1] = {{2}}...).
                    $vars = array_values($campaign->template_variables ?? []);
                    $res  = $cloudSender->sendTemplate(
                        $waId,
                        $template->name,
                        $template->language,
                        $vars
                    );
                    // Adapta pro contrato "success = !isset(error) && !empty(res)"
                    // usado abaixo. CloudApiWhatsAppSender ja normaliza 'ok'.
                    if (($res['ok'] ?? false) === false && !isset($res['error'])) {
                        $res['error'] = $res['error'] ?? 'send_failed';
                    }
                } elseif ($isAudioBroadcast) {
                    $res = $evo->sendAudio($waId, $audioBase64);
                } elseif ($mediaToSend) {
                    $res = $evo->sendMedia($waId, $mediaToSend, $captionOrText, $imageMime);
                } else {
                    $res = $evo->sendMessage($waId, $captionOrText, null, $delayForApi);
                }

                if (!isset($res['error']) && !empty($res)) {
                    if (isset($recipient->id)) {
                        // provider_message_id (Cloud API normalizado) OU key.id (Evolution).
                        $providerMsgId = $res['provider_message_id']
                            ?? $res['key']['id']
                            ?? $res['messageId']
                            ?? ('BROADCAST_' . uniqid());

                        if ($isCloudTemplate) {
                            $content = '[template ' . $template->name . '] '
                                . mb_substr((string) ($template->bodyText() ?? ''), 0, 200);
                            $type    = 'template';
                        } elseif ($isAudioBroadcast) {
                            $content = '[áudio enviado via broadcast]';
                            $type    = 'audio';
                        } elseif ($campaign->has_image) {
                            $content = '[imagem] ' . $campaign->message;
                            $type    = 'image';
                        } else {
                            $content = $campaign->message;
                            $type    = 'text';
                        }

                        WhatsappMessage::create([
                            'chat_id'    => $recipient->id,
                            'message_id' => $providerMsgId,
                            'content'    => $content,
                            'direction'  => 'outbound',
                            'type'       => $type,
                        ]);
                    }
                    $sentCount++;
                    $consecutiveErrors = 0;
                    $antiBan->recordSent($instance);
                    if (!empty($campaign->message)) {
                        $antiBan->recordContentSent($instance, $campaign->message);
                    }
                    if ($isAudioBroadcast) {
                        // Incrementa contador diario do fingerprint do audio (TTL ate fim do dia).
                        $audioKey = 'wa:audio_fp:' . $instance->id . ':' . $campaign->audio_fingerprint;
                        $ttl      = max(1, (int) now()->diffInSeconds(now()->endOfDay()));
                        \Illuminate\Support\Facades\Cache::add($audioKey, 0, $ttl);
                        \Illuminate\Support\Facades\Cache::increment($audioKey);
                    }

                    // Marker pro Bruno: quando este contato responder no WhatsApp,
                    // ele saberá que a mensagem é continuação desta campanha —
                    // evita abertura fria como se o lead fosse desconhecido.
                    \Illuminate\Support\Facades\Cache::put(
                        "bruno:campaign_ctx:{$campaign->tenant_id}:{$rawWaId}",
                        [
                            'campaign_id'   => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'preview'       => mb_substr($campaign->message ?? '', 0, 250),
                            'sent_at'       => now()->toDateTimeString(),
                        ],
                        now()->addDays(7)
                    );
                } else {
                    $errorMsg = is_array($res) ? json_encode($res) : ($res ?: 'Unknown Error');
                    Log::warning("Broadcast failed for {$waId}. Campaign ID: {$campaign->id}. Error: " . $errorMsg);
                    $failedCount++;
                    $consecutiveErrors++;

                    if ($antiBan->isBanSignal($res)) {
                        Log::critical("Broadcast: sinal de ban detectado — instância restrita por 24h, campanha encerrada", [
                            'campaign_id' => $campaign->id,
                            'sent'        => $sentCount,
                            'response'    => substr(json_encode($res), 0, 300),
                        ]);
                        $antiBan->markAsRestricted($instance, 24);
                        $campaign->update([
                            'status'       => 'failed',
                            'completed_at' => now(),
                            'total_sent'   => $baseSent + $sentCount,
                            'total_failed' => $baseFailed + $failedCount,
                        ]);
                        return;
                    }

                    if ($consecutiveErrors >= 5) {
                        Log::error("Broadcast encerrado: 5 erros consecutivos na API", ['campaign_id' => $campaign->id]);
                        $campaign->update([
                            'status'       => 'failed',
                            'completed_at' => now(),
                            'total_sent'   => $baseSent + $sentCount,
                            'total_failed' => $baseFailed + $failedCount,
                        ]);
                        return;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Broadcast recipient exception. Campaign ID: {$campaign->id}. Message: " . $e->getMessage());
                $failedCount++;
                $consecutiveErrors++;
            }

            $campaign->update([
                'total_sent'   => $baseSent + $sentCount,
                'total_failed' => $baseFailed + $failedCount,
            ]);

            // Delay entre mensagens — usa $minDelay/$multiplier calculados antes do loop
            sleep(rand($minDelay * $multiplier, ($minDelay + 10) * $multiplier));
        }

        // ── Verifica se a campanha foi interrompida externamente durante o chunk ──
        $campaign->refresh();
        if ($campaign->status !== 'processing') {
            return;
        }

        $processedUpTo = $this->offset + $effectiveChunkSize;
        $hasMore       = $processedUpTo < $recipientCount;

        if ($hasMore) {
            // Pausa anti-ban de 3-5 min a cada 30 mensagens (agora via delay de fila, não sleep)
            $totalSentGlobal = $campaign->total_sent ?? 0;
            $dispatchDelay   = 0;
            $prevBatch       = (int) floor(($totalSentGlobal - $sentCount) / 30);
            $curBatch        = (int) floor($totalSentGlobal / 30);
            if ($curBatch > $prevBatch && $totalSentGlobal > 0) {
                $dispatchDelay = rand(180, 300);
                Log::info("Broadcast: pausa anti-ban ({$dispatchDelay}s) via delay de fila após {$totalSentGlobal} msgs", [
                    'campaign_id' => $campaign->id,
                    'next_offset' => $processedUpTo,
                ]);
            }

            static::dispatch($this->campaignId, $this->tenantId, $processedUpTo)
                ->delay(now()->addSeconds($dispatchDelay));
        } else {
            $campaign->update(['status' => 'completed', 'completed_at' => now()]);
            Log::info("Broadcast concluído", [
                'campaign_id' => $campaign->id,
                'total_sent'  => $campaign->total_sent,
                'total_failed'=> $campaign->total_failed,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBroadcastCampaignJob chunk falhou definitivamente", [
            'campaign_id' => $this->campaignId,
            'offset'      => $this->offset,
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
                $members   = collect();
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

                $allPhones = array_unique($allPhones);
                $chatsMap  = WhatsappChat::where('tenant_id', $campaign->tenant_id)
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

            return collect($groupIds)->map(fn($id) => (object)['wa_id' => $id, 'id' => null]);
        }

        if ($campaign->audience_type === 'labels' && !empty($campaign->label_ids)) {
            return WhatsappChat::where('tenant_id', $campaign->tenant_id)
                ->whereNotNull('opt_in_at')
                ->whereNull('opt_out_at')
                ->whereNull('blocked_at')
                ->whereHas('labelTags', fn ($q) => $q->whereIn('whatsapp_labels.id', $campaign->label_ids))
                ->get(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at']);
        }

        // Selected sem phones NUNCA pode cair no fallback (base inteira do
        // tenant) — campanha legada/malformada encerra vazia.
        if ($campaign->audience_type === 'selected' && !$campaign->phones) {
            return collect();
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

        // Fallback (audience_type legado/desconhecido): exige opt-in como o
        // caminho 'all' — nunca dispara pra número frio por engano.
        return WhatsappChat::where('tenant_id', $campaign->tenant_id)
            ->whereNotNull('opt_in_at')
            ->whereNull('opt_out_at')->whereNull('blocked_at')
            ->get(['id', 'wa_id', 'opt_in_at', 'opt_out_at', 'blocked_at']);
    }
}
