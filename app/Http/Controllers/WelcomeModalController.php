<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Descarta o modal de boas-vindas (BruceIA + Sala de Estratégia) — idempotente,
 * uma vez chamado, o campo welcome_dismissed_at fica preenchido e o modal
 * não aparece mais pra esse usuário.
 */
class WelcomeModalController extends Controller
{
    public function dismiss(): JsonResponse
    {
        $user = auth()->user();

        if ($user->welcome_dismissed_at === null) {
            $user->welcome_dismissed_at = now();
            $user->save();
        }

        return response()->json(['ok' => true]);
    }
}
