<?php

namespace App\Http\Controllers;

use App\Services\AntiBanTermService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsappAntiBanController — Fase 2, sub-etapa 2.A.2.
 *
 * Expõe o termo de responsabilidade anti-ban vigente e registra o aceite
 * do gestor do tenant. Endpoint consumido pelo modal Blade antes da
 * criação de instância Evolution.
 */
class WhatsappAntiBanController extends Controller
{
    public function __construct(private AntiBanTermService $service)
    {
    }

    /**
     * Retorna o termo vigente + se o tenant já aceitou esta versão.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->tenant === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $term = $this->service->currentTerm();
        if ($term === null) {
            Log::error('AntiBan: termo vigente não encontrado em config(whatsapp.anti_ban_terms).', [
                'version' => $this->service->currentVersion(),
            ]);
            return response()->json(['error' => 'term_not_configured'], 500);
        }

        return response()->json([
            'version'      => $this->service->currentVersion(),
            'title'        => $term['title'],
            'text'         => $term['text'],
            'effective_at' => $term['effective_at'],
            'has_accepted' => $this->service->hasAcceptedCurrent($user->tenant),
        ]);
    }

    /**
     * Registra o aceite da versão vigente. Idempotente — re-aceitar
     * devolve o aceite existente.
     */
    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->tenant === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        try {
            $acceptance = $this->service->accept($user, $request);
        } catch (\Throwable $e) {
            Log::error('AntiBan: falha ao registrar aceite.', [
                'user_id'  => $user->id,
                'tenant_id'=> $user->tenant_id,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'accept_failed'], 500);
        }

        return response()->json([
            'ok'           => true,
            'version'      => $acceptance->version,
            'accepted_at'  => $acceptance->accepted_at?->toIso8601String(),
        ]);
    }
}
