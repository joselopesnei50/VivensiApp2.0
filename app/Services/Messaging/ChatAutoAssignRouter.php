<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Models\WhatsappChat;
use Illuminate\Support\Facades\DB;

/**
 * P3 (2026-08-05) — auto-assign round-robin de chat WhatsApp inbound.
 *
 * Quando o webhook cria um chat NOVO individual e `WhatsappConfig.auto_assign_mode`
 * do tenant esta em 'round_robin', chamamos aqui pra sortear o proximo agente
 * elegivel. Ordem: `last_auto_assigned_at ASC` (quem foi atribuido ha mais
 * tempo — ou nunca — recebe primeiro). Nulos vem antes de datas antigas.
 *
 * Elegivel = User com:
 *   - tenant_id == chat.tenant_id
 *   - role em AGENT_ROLES
 *   - status = active
 *   - agent_availability = available (respeita toggle do P2)
 *
 * O `assigned_to` do chat E atualizado aqui com whereNull() atomico — se
 * outro processo assumir no meio (webhook duplo, agente clicando), o
 * auto-assign pula sem sobrescrever.
 */
class ChatAutoAssignRouter
{
    public const MODE_OFF          = 'off';
    public const MODE_ROUND_ROBIN  = 'round_robin';

    /**
     * Sorteia e atribui um agente ao chat. Retorna o User atribuido ou null
     * se nao ha agente elegivel (fila vazia) ou se outro processo ja pegou.
     */
    public function assign(WhatsappChat $chat): ?User
    {
        if ($chat->assigned_to !== null || $chat->is_group) {
            return null;
        }

        return DB::transaction(function () use ($chat): ?User {
            $agent = User::query()
                ->where('tenant_id', $chat->tenant_id)
                ->whereIn('role', ChatTransferService::AGENT_ROLES)
                ->where('status', 'active')
                ->where('agent_availability', User::AVAILABILITY_AVAILABLE)
                ->orderByRaw('last_auto_assigned_at IS NULL DESC') // nulos primeiro
                ->orderBy('last_auto_assigned_at', 'asc')
                ->orderBy('id', 'asc') // tiebreaker deterministico
                ->lockForUpdate()
                ->first();

            if (!$agent) {
                return null;
            }

            $affected = WhatsappChat::where('id', $chat->id)
                ->whereNull('assigned_to')
                ->update([
                    'assigned_to' => $agent->id,
                    'status'      => 'human_attending',
                    'updated_at'  => now(),
                ]);

            if ($affected === 0) {
                return null; // corrida — outro processo atribuiu primeiro
            }

            $agent->forceFill(['last_auto_assigned_at' => now()])->save();

            app(ChatAssignmentRecorder::class)->record(
                $chat->refresh(),
                $agent->id,
                null, // actor null = sistema (auto-assign)
                ChatAssignmentRecorder::ACTION_TAKE_OVER,
                null
            );

            return $agent;
        });
    }
}
