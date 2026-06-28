<?php

namespace App\Services\Bruno;

use App\Models\KanbanCard;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Services\KanbanService;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza estado do CRM (Lead + Kanban) a partir de uma conversa que o
 * Bruno (sales_bot) acabou de processar. Idempotente: pode ser chamado a
 * cada turno sem duplicar registros.
 *
 * Fluxo:
 * - Garante que existe um Lead vinculado ao WhatsappChat (cria se não há).
 * - Atualiza tags + meta do Lead com a qualificação mais recente.
 * - Quando qualification === 'quente', cria um KanbanCard na primeira coluna
 *   do board default do tenant (ou sub-board comercial, se existir). Idem
 *   idempotente: não cria card duplicado pro mesmo chat.
 *
 * Falhas são logadas (warning) mas nunca propagadas — sincronização não pode
 * quebrar o envio da resposta ao lead.
 */
class BrunoLeadSync
{
    public static function syncFromChat(WhatsappChat $chat, ?array $qualification): void
    {
        try {
            $lead = self::ensureLead($chat);

            if ($qualification && !empty($qualification['qualification'])) {
                self::updateLeadTags($lead, $qualification);

                if ($qualification['qualification'] === 'quente') {
                    self::ensureKanbanCard($chat, $lead, $qualification);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('BrunoLeadSync falhou', [
                'err'     => $e->getMessage(),
                'chat_id' => $chat->id,
            ]);
        }
    }

    private static function ensureLead(WhatsappChat $chat): Lead
    {
        $lead = Lead::where('whatsapp_chat_id', $chat->id)->first();
        if ($lead) {
            return $lead;
        }

        return Lead::create([
            'tenant_id'           => $chat->tenant_id,
            'whatsapp_chat_id'    => $chat->id,
            'name'                => $chat->contact_name ?: 'Lead WhatsApp',
            'phone'               => $chat->contact_phone,
            'phone_normalized'    => preg_replace('/\D/', '', (string) $chat->contact_phone),
            'status'              => Lead::STATUS_PENDING,
            'consent_origin'      => 'whatsapp_bruno',
            'consent_at'          => now(),
            'last_interaction_at' => now(),
            'tags'                => ['bruno', 'whatsapp_inbound'],
            'meta'                => ['source' => 'bruno_sales_bot'],
        ]);
    }

    private static function updateLeadTags(Lead $lead, array $qualification): void
    {
        $q = $qualification['qualification'];
        $tags = is_array($lead->tags) ? $lead->tags : [];

        // Remove tags antigas de qualification (qual:frio, qual:morno, qual:quente)
        // e adiciona a nova classificação.
        $tags = array_values(array_filter($tags, fn ($t) => !is_string($t) || !str_starts_with($t, 'qual:')));
        $tags = array_values(array_unique(array_merge($tags, ['bruno', 'qual:' . $q])));

        $lead->update([
            'tags'                => $tags,
            'last_interaction_at' => now(),
            'meta'                => array_merge(is_array($lead->meta) ? $lead->meta : [], [
                'qualification' => $q,
                'intent'        => $qualification['intent']      ?? null,
                'summary'       => $qualification['summary']     ?? null,
                'next_action'   => $qualification['next_action'] ?? null,
                'confidence'    => $qualification['confidence']  ?? null,
            ]),
        ]);
    }

    private static function ensureKanbanCard(WhatsappChat $chat, Lead $lead, array $qualification): void
    {
        $existing = KanbanCard::where('tenant_id', $chat->tenant_id)
            ->where('whatsapp_chat_id', $chat->id)
            ->first();
        if ($existing) {
            return;
        }

        $tenant = Tenant::find($chat->tenant_id);
        if (!$tenant) {
            return;
        }

        $kanban = app(KanbanService::class);
        $board  = $kanban->ensureDefaultBoard($tenant);
        $column = $board->columns()->orderBy('position')->first();
        if (!$column) {
            return;
        }

        $title = '🔥 ' . ($lead->name ?: ($chat->contact_phone ?: 'Lead Bruno'));
        $description = $qualification['summary'] ?? 'Lead classificado como quente pelo Bruno.';
        if (!empty($qualification['next_action'])) {
            $description .= "\n\nPróxima ação sugerida: " . $qualification['next_action'];
        }

        $kanban->createCard($column, [
            'title'            => $title,
            'description'      => $description,
            'whatsapp_chat_id' => $chat->id,
            'meta'             => [
                'source'        => 'bruno_sales_bot',
                'lead_id'       => $lead->id,
                'qualification' => $qualification,
            ],
        ]);
    }
}
