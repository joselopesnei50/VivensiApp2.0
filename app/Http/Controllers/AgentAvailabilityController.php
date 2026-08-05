<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Messaging\ChatTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * P2 (2026-08-05) — toggle de disponibilidade do agente.
 *
 * So usuarios em AGENT_ROLES (manager|ngo|super_admin) podem alterar o
 * proprio status. Nao bloqueia transferencia — e um sinal informativo
 * consumido por eligibleAgents e pela UI de transferencia.
 */
class AgentAvailabilityController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ChatTransferService::AGENT_ROLES, true)) {
            return response()->json([
                'success' => false,
                'error'   => 'Seu perfil nao usa disponibilidade de atendimento.',
            ], 403);
        }

        $data = $request->validate([
            'availability' => ['required', 'string', Rule::in(User::AVAILABILITIES)],
        ]);

        $user->forceFill([
            'agent_availability'      => $data['availability'],
            'availability_changed_at' => now(),
        ])->save();

        return response()->json([
            'success'                 => true,
            'agent_availability'      => $user->agent_availability,
            'availability_changed_at' => $user->availability_changed_at?->toIso8601String(),
        ]);
    }
}
