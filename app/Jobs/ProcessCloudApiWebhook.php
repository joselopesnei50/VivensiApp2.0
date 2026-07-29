<?php

namespace App\Jobs;

use App\Models\WhatsappChat;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processa payload de webhook da Meta WhatsApp Business Cloud API.
 *
 * Estrutura do payload (v20):
 *   {
 *     "object": "whatsapp_business_account",
 *     "entry": [{
 *       "id": "<WABA_ID>",
 *       "changes": [{
 *         "value": {
 *           "messaging_product": "whatsapp",
 *           "metadata": {"phone_number_id": "<PHONE_ID>", "display_phone_number": "..."},
 *           "contacts": [{"profile": {"name": "..."}, "wa_id": "..."}],
 *           "messages": [{...}],   // inbound
 *           "statuses": [{...}]    // outbound status updates
 *         },
 *         "field": "messages"
 *       }]
 *     }]
 *   }
 *
 * Persistência idêntica ao Evolution — mesmas tabelas whatsapp_chats/whatsapp_messages.
 */
class ProcessCloudApiWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];

    public function __construct(protected array $payload)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        foreach ($this->payload['entry'] ?? [] as $entry) {
            $wabaId = $entry['id'] ?? null;

            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                if ($field === 'message_template_status_update') {
                    $this->handleTemplateStatusUpdate($wabaId, $value);
                    continue;
                }

                if ($field !== 'messages') {
                    continue; // Ignora demais campos (billing, account_update, etc.) por enquanto.
                }

                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (!$phoneNumberId) {
                    Log::warning('CloudApi Job: entry sem phone_number_id', ['entry_id' => $wabaId]);
                    continue;
                }

                $instance = WhatsappInstance::withoutGlobalScope('tenant')
                    ->where('provider', WhatsappInstance::PROVIDER_CLOUD_API)
                    ->where('phone_number_id', $phoneNumberId)
                    ->first();

                if (!$instance) {
                    Log::warning('CloudApi Job: phone_number_id desconhecido', ['phone_number_id' => $phoneNumberId]);
                    continue;
                }

                foreach ($value['messages'] ?? [] as $msg) {
                    $this->persistInbound($instance, $msg, $value['contacts'] ?? []);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->applyStatus($instance, $status);
                }
            }
        }
    }

    /**
     * Processa evento message_template_status_update — Meta notifica quando aprova/rejeita
     * um template criado via API. Delega ao CloudApiTemplateService::applyStatusUpdate
     * que atualiza WhatsappTemplate no cache local (isolado por waba_id).
     */
    private function handleTemplateStatusUpdate(?string $wabaId, array $value): void
    {
        if (!$wabaId) {
            Log::warning('CloudApi Job: template_status_update sem waba_id');
            return;
        }

        $metaTemplateId = (string) ($value['message_template_id'] ?? '');
        $event          = $value['event']           ?? null; // APPROVED | REJECTED | PAUSED | DISABLED | PENDING_DELETION
        $reason         = $value['reason']          ?? null;

        if (empty($metaTemplateId) || empty($event)) {
            Log::warning('CloudApi Job: template_status_update sem id/event', ['payload' => $value]);
            return;
        }

        app(\App\Services\WhatsApp\CloudApiTemplateService::class)
            ->applyStatusUpdate($wabaId, $metaTemplateId, $event, $reason);
    }

    private function persistInbound(WhatsappInstance $instance, array $msg, array $contacts): void
    {
        $messageId = $msg['id'] ?? null;
        $waId      = $msg['from'] ?? null;
        $type      = $msg['type'] ?? 'text';

        if (!$messageId || !$waId) {
            Log::warning('CloudApi Job: mensagem sem id/from', ['payload' => $msg]);
            return;
        }

        // Idempotência: message_id é unique.
        if (WhatsappMessage::where('message_id', $messageId)->exists()) {
            return;
        }

        [$content, $mediaCaption, $mediaPath] = $this->extractContent($type, $msg);

        $senderName = null;
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? null) === $waId) {
                $senderName = $contact['profile']['name'] ?? null;
                break;
            }
        }

        $chat = WhatsappChat::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $instance->tenant_id, 'wa_id' => $waId],
            [
                'contact_name'     => $senderName ?: 'Contato WhatsApp',
                'contact_phone'    => $waId,
                'status'           => 'open',
                'opt_in_at'        => now(),  // Inbound implica opt-in pra resposta
                'last_message_at'  => now(),
                'last_inbound_at'  => now(),
            ]
        );

        $newMessage = WhatsappMessage::create([
            'tenant_id'     => $instance->tenant_id,
            'chat_id'       => $chat->id,
            'message_id'    => $messageId,
            'content'       => $content,
            'direction'     => 'inbound',
            'type'          => $this->mapType($type),
            'media_path'    => $mediaPath,
            'media_caption' => $mediaCaption,
        ]);

        // Broadcast em tempo real pro front — toast + badge no /whatsapp/chat
        // e qualquer outra tela aberta do mesmo tenant. Falha silenciosa se
        // Pusher estiver fora (nunca bloqueia webhook).
        try {
            event(new \App\Events\WhatsappMessageReceived($newMessage, $chat));
        } catch (\Throwable $e) {
            Log::warning('WhatsappMessageReceived broadcast falhou (nao bloqueante)', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0:string,1:?string,2:?string}  [content, mediaCaption, mediaPath]
     */
    private function extractContent(string $type, array $msg): array
    {
        return match ($type) {
            'text'     => [(string) ($msg['text']['body'] ?? ''), null, null],
            'image'    => [(string) ($msg['image']['caption'] ?? '[imagem]'), $msg['image']['caption'] ?? null, $msg['image']['id'] ?? null],
            'video'    => [(string) ($msg['video']['caption'] ?? '[video]'), $msg['video']['caption'] ?? null, $msg['video']['id'] ?? null],
            'audio'    => ['[audio]', null, $msg['audio']['id'] ?? null],
            'document' => [(string) ($msg['document']['caption'] ?? ($msg['document']['filename'] ?? '[documento]')), $msg['document']['caption'] ?? null, $msg['document']['id'] ?? null],
            'sticker'  => ['[figurinha]', null, $msg['sticker']['id'] ?? null],
            'location' => ['[localizacao]', null, null],
            'contacts' => ['[contato]', null, null],
            'button'   => [(string) ($msg['button']['text'] ?? ''), null, null],
            'interactive' => [(string) ($msg['interactive']['button_reply']['title'] ?? $msg['interactive']['list_reply']['title'] ?? ''), null, null],
            'reaction' => [(string) ($msg['reaction']['emoji'] ?? ''), null, null],
            default    => ['[mensagem nao suportada]', null, null],
        };
    }

    /**
     * Meta Cloud API usa tipos ligeiramente diferentes do Evolution.
     * Normalizamos pra vocabulário comum.
     */
    private function mapType(string $cloudType): string
    {
        return match ($cloudType) {
            'text'        => 'text',
            'image'       => 'image',
            'video'       => 'video',
            'audio'       => 'audio',
            'document'    => 'document',
            'sticker'     => 'sticker',
            'location'    => 'location',
            'contacts'    => 'contact',
            'button'      => 'button_reply',
            'interactive' => 'interactive_reply',
            'reaction'    => 'reaction',
            default       => 'unsupported',
        };
    }

    /**
     * Atualiza status de mensagens outbound (delivered/read/failed).
     * Idempotente — se WhatsappMessage não existe (ex: enviada por outro sistema),
     * ignoramos silenciosamente.
     *
     * Alem de status, tambem captura conversation+pricing quando presente
     * (Fase 5 Billing) e linka a WhatsappMessage ao seu WhatsappConversation.
     */
    private function applyStatus(WhatsappInstance $instance, array $status): void
    {
        $messageId = $status['id'] ?? null;
        $newStatus = $status['status'] ?? null;

        if (!$messageId || !$newStatus) {
            return;
        }

        // Só progride status pra frente: sent -> delivered -> read; failed é terminal.
        $ranking = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 99];

        $msg = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('message_id', $messageId)
            ->first();

        if (!$msg) {
            return;
        }

        $updates = [];
        $currentRank = $ranking[$msg->status] ?? 0;
        $newRank     = $ranking[$newStatus] ?? 0;

        if ($newRank > $currentRank) {
            $updates['status'] = $newStatus;
        }

        // Captura conversation+pricing (Fase 5 Billing) apenas se ambos vieram.
        // Meta envia isso tipicamente no primeiro status "sent" da conversa.
        if (!empty($status['conversation']) && !empty($status['pricing'])) {
            $conversation = $this->upsertConversation($instance, $status);

            if ($conversation && !$msg->whatsapp_conversation_id) {
                $updates['whatsapp_conversation_id'] = $conversation->id;
                $updates['meta_pricing_category']    = $conversation->category;
            }
        }

        if (!empty($updates)) {
            $msg->update($updates);
        }
    }

    /**
     * Cria ou atualiza WhatsappConversation a partir do payload conversation+pricing
     * do webhook Meta. Idempotente por (instance_id, meta_conversation_id).
     *
     * Payload Meta (statuses[]):
     *   "conversation": {
     *     "id": "abc123",
     *     "expiration_timestamp": "1755180000",
     *     "origin": { "type": "utility" }
     *   },
     *   "pricing": {
     *     "billable": true,
     *     "pricing_model": "CBP",
     *     "category": "utility"
     *   }
     */
    private function upsertConversation(WhatsappInstance $instance, array $status): ?WhatsappConversation
    {
        $conv       = $status['conversation'] ?? [];
        $pricing    = $status['pricing']      ?? [];
        $convId     = $conv['id'] ?? null;

        if (!$convId) {
            return null;
        }

        $category    = $pricing['category'] ?? ($conv['origin']['type'] ?? null);
        $originType  = $conv['origin']['type'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;
        $countryCode = $this->deriveCountryCode($recipientId);

        // Fallback: se webhook nao trouxe billable ou pricing_model.
        $isBillable    = (bool) ($pricing['billable'] ?? true);
        $pricingModel  = strtoupper((string) ($pricing['pricing_model'] ?? WhatsappConversation::PRICING_MODEL_CBP));

        // Custo em USD micros — Meta nao envia valor no webhook, calculamos via config.
        $costMicros = $isBillable ? $this->resolveCostMicros($countryCode, $category) : 0;

        $expiresAt = isset($conv['expiration_timestamp'])
            ? \Carbon\Carbon::createFromTimestamp((int) $conv['expiration_timestamp'])
            : now()->addDay();

        return WhatsappConversation::withoutGlobalScope('tenant')->updateOrCreate(
            [
                'whatsapp_instance_id' => $instance->id,
                'meta_conversation_id' => $convId,
            ],
            [
                'tenant_id'       => $instance->tenant_id,
                'contact_wa_id'   => (string) ($recipientId ?? ''),
                'category'        => $category ?? WhatsappConversation::CATEGORY_UTILITY,
                'origin_type'     => $originType,
                'expires_at'      => $expiresAt,
                'started_at'      => now(),
                'cost_usd_micros' => $costMicros,
                'pricing_model'   => $pricingModel,
                'is_billable'     => $isBillable,
                'country_code'    => $countryCode,
            ]
        );
    }

    /**
     * Deriva o country code (ISO 3166-1 alpha-2) do numero E.164 sem +.
     * Cobre os principais paises Vivensi; fallback pra null (dashboard usa 'default').
     */
    private function deriveCountryCode(?string $recipientId): ?string
    {
        if (!$recipientId) {
            return null;
        }

        return match (true) {
            str_starts_with($recipientId, '55') => 'BR',
            str_starts_with($recipientId, '1')  => 'US',
            default                             => null,
        };
    }

    /**
     * Resolve custo em USD micros pela tabela config/whatsapp_pricing.php.
     */
    private function resolveCostMicros(?string $countryCode, ?string $category): int
    {
        if (!$category) {
            return 0;
        }

        $table   = config('whatsapp_pricing.pricing', []);
        $country = $countryCode && isset($table[$countryCode]) ? $countryCode : 'default';

        return (int) ($table[$country][$category] ?? 0);
    }
}
