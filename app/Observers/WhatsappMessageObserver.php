<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadTimelineItem;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\LeadService;
use Illuminate\Support\Facades\Log;

/**
 * P1.7 — Persiste cada mensagem de WhatsApp (inbound e outbound) na
 * timeline do lead vinculado, se houver. Idempotência: cada
 * WhatsappMessage só roda once (hook 'created').
 *
 * Sem FK lead_id em whatsapp_chats por enquanto — lookup por
 * phone_normalized é OK pra escala atual; trocamos por coluna FK
 * quando virar gargalo.
 */
class WhatsappMessageObserver
{
    private const BODY_MAX_CHARS = 500;

    public function created(WhatsappMessage $message): void
    {
        try {
            $chat = $message->chat()->first();
            if ($chat === null) {
                return;
            }

            $lead = $this->resolveLead($chat);
            if ($lead === null) {
                return;
            }

            $direction = $message->direction === 'inbound' ? 'Contato' : 'Atendente';
            $rawBody   = trim((string) ($message->content ?? ''));
            if ($rawBody === '') {
                return;
            }
            $body = sprintf('[%s] %s', $direction, mb_substr($rawBody, 0, self::BODY_MAX_CHARS));

            LeadTimelineItem::create([
                'tenant_id' => $lead->tenant_id,
                'lead_id'   => $lead->id,
                'author_id' => null,
                'type'      => LeadTimelineItem::TYPE_WHATSAPP_MESSAGE,
                'body'      => $body,
                'meta'      => [
                    'chat_id'        => $chat->id,
                    'whatsapp_message_id' => $message->id,
                    'direction'      => $message->direction,
                    'type'           => $message->type,
                ],
            ]);

            $lead->update(['last_interaction_at' => now()]);
        } catch (\Throwable $e) {
            // Timeline é cosmético — nunca pode quebrar o flow de mensagem.
            Log::warning('WhatsappMessageObserver: falha ao escrever timeline', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function resolveLead(WhatsappChat $chat): ?Lead
    {
        $phoneInput = $chat->contact_phone ?: $chat->wa_id;
        if (empty($phoneInput)) {
            return null;
        }

        $normalized = app(LeadService::class)->normalizePhone((string) $phoneInput);
        if ($normalized === null) {
            return null;
        }

        return Lead::where('tenant_id', $chat->tenant_id)
            ->where('phone_normalized', $normalized)
            ->first();
    }
}
