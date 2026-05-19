<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BroadcastCampaign;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBroadcastCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignId;
    public $timeout = 3600;
    public $tries   = 1;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
        $this->onQueue('whatsapp');
    }

    public function handle()
    {
        $campaign = BroadcastCampaign::find($this->campaignId);
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

        $evo = new EvolutionApiService($instance);
        $recipients = $this->getRecipients($campaign);

        if ($recipients->isEmpty()) {
            $campaign->update(['status' => 'completed', 'completed_at' => now(), 'actual_recipients' => 0]);
            return;
        }

        $campaign->update(['actual_recipients' => $recipients->count()]);

        $sentCount = 0;
        $failedCount = 0;

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
            $normalizedNumbers = $recipients
                ->pluck('wa_id')
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

                // Cache os JIDs validados no banco para reuso futuro
                if (!empty($jidMap)) {
                    foreach ($jidMap as $original => $jid) {
                        WhatsappChat::where('tenant_id', $campaign->tenant_id)
                            ->where('wa_id', $original)
                            ->update([
                                'wa_jid'                 => $jid,
                                'whatsapp_validated_at'  => now(),
                            ]);
                    }
                }
            }
        }

        foreach ($recipients as $recipient) {
            $campaign->refresh();
            if ($campaign->status !== 'processing') break;

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
                }

                $res = $mediaToSend
                    ? $evo->sendMedia($waId, $mediaToSend, $campaign->message, $imageMime)
                    : $evo->sendMessage($waId, $campaign->message, null, rand(1, 3));

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
                } else {
                    $errorMsg = is_array($res) ? json_encode($res) : ($res ?: 'Unknown Error');
                    Log::warning("Broadcast failed for {$waId}. Campaign ID: {$campaign->id}. Error: " . $errorMsg);
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Broadcast recipient exception for {$waId}. Campaign ID: {$campaign->id}. Message: " . $e->getMessage());
                $failedCount++;
            }

            $campaign->update([
                'total_sent'   => $sentCount,
                'total_failed' => $failedCount
            ]);

            sleep($campaign->cadence ?: 3);
        }

        $campaign->update(['status' => 'completed', 'completed_at' => now()]);
    }

    protected function getRecipients($campaign)
    {
        if ($campaign->audience_type === 'groups') {
            $groupIds = $campaign->group_ids ?: [];

            // Modo "members": expande cada grupo nos seus membros individuais
            if (($campaign->group_send_mode ?? 'group') === 'members') {
                Log::info('Broadcast members mode: iniciando expansão de grupos', [
                    'campaign_id' => $campaign->id,
                    'group_count' => count($groupIds),
                ]);

                $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
                    ->where('status', 'open')->first();

                if (!$instance) {
                    Log::error("Broadcast members mode: no active instance for tenant {$campaign->tenant_id}");
                    return collect();
                }

                $evo = new EvolutionApiService($instance);
                $members = collect();

                foreach ($groupIds as $groupId) {
                    $jids = $evo->getGroupMembers($groupId);
                    Log::info('Broadcast members mode: grupo expandido', [
                        'campaign_id' => $campaign->id,
                        'group_id'    => $groupId,
                        'jids_count'  => count($jids),
                    ]);

                    foreach ($jids as $jid) {
                        $phone = str_replace('@s.whatsapp.net', '', $jid);
                        $chat = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                            ->where('wa_id', $phone)->first();

                        if ($chat && ($chat->opt_out_at || $chat->blocked_at)) continue;

                        $members->push((object)[
                            'wa_id' => $phone,
                            'id'    => $chat?->id,
                        ]);
                    }
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
                ->get()
                ->keyBy('wa_id');

            return collect($phones)->map(function ($phone) use ($existingChats) {
                if ($existingChats->has($phone)) {
                    $chat = $existingChats->get($phone);
                    if ($chat->opt_out_at || $chat->blocked_at) return null;
                    return (object)['wa_id' => $phone, 'id' => $chat->id];
                }
                return (object)['wa_id' => $phone, 'id' => null];
            })->filter()->values();
        }

        return WhatsappChat::where('tenant_id', $campaign->tenant_id)
            ->whereNull('opt_out_at')->whereNull('blocked_at')
            ->get();
    }
}
