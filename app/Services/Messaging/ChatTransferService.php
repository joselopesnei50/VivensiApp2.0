<?php

namespace App\Services\Messaging;

use App\Models\Notification;
use App\Models\User;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappChat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ChatTransferService — Fase 3.A do roadmap.
 *
 * Encapsula a regra de transferência de atendimento no OmniChannel:
 * autoriza, persiste, audita e notifica. Mantém isolamento por tenant.
 */
class ChatTransferService
{
    /** Roles que podem receber atendimento e fazer override de transferência. */
    public const AGENT_ROLES = ['manager', 'ngo', 'super_admin'];

    /**
     * Atribui o chat a outro usuário. Atendente atual ou qualquer
     * manager/super_admin pode transferir. Cria audit log e notificação
     * in-app (broadcast pelo Notification::booted) pro novo responsável.
     */
    public function transferTo(WhatsappChat $chat, User $target, User $actor): WhatsappChat
    {
        $this->ensureSameTenant($chat, $target, $actor);
        $this->ensureTargetIsAgent($target);
        $this->ensureActorCanTransfer($chat, $actor);

        if ($chat->assigned_to === $target->id) {
            // No-op — evitar lixo no audit/notifications.
            return $chat;
        }

        $previousAssigneeId = $chat->assigned_to;

        DB::transaction(function () use ($chat, $target, $actor, $previousAssigneeId): void {
            $chat->update([
                'assigned_to'   => $target->id,
                'is_bot_active' => false,
                'status'        => 'human_attending',
            ]);

            WhatsappAuditLog::create([
                'tenant_id'     => $chat->tenant_id,
                'chat_id'       => $chat->id,
                'actor_user_id' => $actor->id,
                'actor_type'    => 'user',
                'event'         => 'chat_transferred',
                'details'       => [
                    'from_user_id' => $previousAssigneeId,
                    'to_user_id'   => $target->id,
                    'mode'         => $previousAssigneeId === $actor->id ? 'self_handoff' : 'override',
                ],
            ]);

            // Historico dedicado (P1 2026-08-05).
            app(ChatAssignmentRecorder::class)->record(
                $chat,
                $target->id,
                $actor,
                ChatAssignmentRecorder::ACTION_TRANSFER,
                $previousAssigneeId
            );

            Notification::create([
                'tenant_id' => $chat->tenant_id,
                'user_id'   => $target->id,
                'title'     => 'Novo atendimento atribuído',
                'message'   => sprintf(
                    'Você recebeu o atendimento de %s.',
                    $chat->contact_name ?: ($chat->contact_phone ?: 'um contato')
                ),
                'type'      => 'whatsapp_chat_assigned',
                'link'      => url('/whatsapp/chat?chat_id=' . $chat->id),
            ]);
        });

        return $chat->fresh();
    }

    /**
     * Libera o chat (assigned_to = null). Volta pra fila/não atribuídos.
     * Só atendente atual ou manager/super_admin libera.
     */
    public function release(WhatsappChat $chat, User $actor): WhatsappChat
    {
        $this->ensureSameTenant($chat, $actor, $actor);
        $this->ensureActorCanTransfer($chat, $actor);

        if ($chat->assigned_to === null) {
            return $chat; // nada a fazer
        }

        $previousAssigneeId = $chat->assigned_to;

        DB::transaction(function () use ($chat, $actor, $previousAssigneeId): void {
            $chat->update([
                'assigned_to' => null,
                'status'      => 'open',
            ]);

            WhatsappAuditLog::create([
                'tenant_id'     => $chat->tenant_id,
                'chat_id'       => $chat->id,
                'actor_user_id' => $actor->id,
                'actor_type'    => 'user',
                'event'         => 'chat_released',
                'details'       => [
                    'from_user_id' => $previousAssigneeId,
                    'mode'         => $previousAssigneeId === $actor->id ? 'self_release' : 'override',
                ],
            ]);

            // Historico dedicado (P1 2026-08-05) — release so fecha o aberto.
            app(ChatAssignmentRecorder::class)->record(
                $chat,
                null,
                $actor,
                ChatAssignmentRecorder::ACTION_RELEASE,
                $previousAssigneeId
            );
        });

        return $chat->fresh();
    }

    /**
     * Lista usuários elegíveis pra receber atendimento dentro do tenant.
     * Inclui apenas roles consideradas agentes.
     */
    public function eligibleAgents(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', self::AGENT_ROLES)
            ->when(static fn ($q) => $q->where('status', 'active'), fn ($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'agent_availability', 'availability_changed_at']);
    }

    /**
     * Verifica se o actor pode transferir/liberar este chat.
     * Permitido: manager/super_admin (override) OU o atual assigned_to.
     */
    public function actorCanTransfer(WhatsappChat $chat, User $actor): bool
    {
        // Defesa em camadas: ainda que um chat tenha sido atribuído por engano
        // a alguém sem role de agente, esse usuário não ganha autoridade.
        if (!in_array($actor->role, self::AGENT_ROLES, true)) {
            return false;
        }
        if (in_array($actor->role, ['manager', 'super_admin'], true)) {
            return true;
        }
        return $chat->assigned_to !== null && $actor->id === $chat->assigned_to;
    }

    // ── Guards ─────────────────────────────────────────────────────────────

    private function ensureSameTenant(WhatsappChat $chat, User $a, User $b): void
    {
        // Cast int: em SQLite/MySQL tenant_id pode voltar como string ou int
        // dependendo do driver — comparação strict falharia por tipo, não
        // por valor real. A intenção é comparar o ID numerico.
        $chatT = (int) $chat->tenant_id;
        if ($chatT !== (int) $a->tenant_id || $chatT !== (int) $b->tenant_id) {
            throw new RuntimeException('Operação cross-tenant negada.');
        }
    }

    private function ensureTargetIsAgent(User $target): void
    {
        if (!in_array($target->role, self::AGENT_ROLES, true)) {
            throw new RuntimeException("Usuário '{$target->name}' não pode receber atendimentos (role inválida).");
        }
    }

    private function ensureActorCanTransfer(WhatsappChat $chat, User $actor): void
    {
        if (!$this->actorCanTransfer($chat, $actor)) {
            throw new RuntimeException('Você não tem permissão para transferir este atendimento.');
        }
    }
}
