<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappChatAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * P1 (2026-08-05) — grava o historico de handoffs do chat WhatsApp.
 *
 * Toda mudanca de assigned_to (take_over, transfer, release) passa aqui:
 *   1) fecha o assignment "aberto" atual do chat (ended_at + duration);
 *   2) se newAssigneeId != null, abre um novo assignment.
 *
 * NAO altera a coluna `assigned_to` do chat — quem chama e responsavel
 * por persistir a mudanca no proprio chat. Este servico so mantem a
 * trilha historica canonica (fonte pra dashboard de produtividade).
 */
class ChatAssignmentRecorder
{
    public const ACTION_TAKE_OVER = 'take_over';
    public const ACTION_TRANSFER  = 'transfer';
    public const ACTION_RELEASE   = 'release';

    /**
     * Registra uma mudanca de assignment.
     *
     * @param  WhatsappChat $chat            chat afetado
     * @param  int|null     $newAssigneeId   novo dono (null = release)
     * @param  User         $actor           quem executou a acao
     * @param  string       $action          take_over | transfer | release
     * @param  int|null     $previousAssigneeId  dono anterior (evita leitura extra)
     * @return WhatsappChatAssignment|null  novo assignment aberto, ou null em release
     */
    public function record(
        WhatsappChat $chat,
        ?int $newAssigneeId,
        User $actor,
        string $action,
        ?int $previousAssigneeId = null
    ): ?WhatsappChatAssignment {
        return DB::transaction(function () use ($chat, $newAssigneeId, $actor, $action, $previousAssigneeId) {
            $now = Carbon::now();

            // 1) Fecha o assignment aberto atual do chat (se existir).
            $open = WhatsappChatAssignment::where('chat_id', $chat->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();

            if ($open !== null) {
                $open->ended_at = $now;
                $open->duration_seconds = max(0, $now->diffInSeconds($open->started_at));
                $open->save();
            }

            // 2) Release nao abre novo registro — so fecha o anterior.
            if ($newAssigneeId === null) {
                return null;
            }

            return WhatsappChatAssignment::create([
                'tenant_id'           => $chat->tenant_id,
                'chat_id'             => $chat->id,
                'from_user_id'        => $previousAssigneeId,
                'to_user_id'          => $newAssigneeId,
                'assigned_by_user_id' => $actor->id,
                'action'              => $action,
                'started_at'          => $now,
            ]);
        });
    }
}
