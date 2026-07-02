<?php

namespace App\Http\Controllers;

use App\Jobs\RunStrategyDebateJob;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use Illuminate\Http\Request;

/**
 * Sala de Estrategia — Fase 2. UI minima.
 *
 * Rotas:
 *   GET  /strategy-room                 → index (lista sessoes recentes)
 *   GET  /strategy-room/{session}       → detalhe (timeline das falas)
 *   POST /strategy-room                 → dispara novo debate em queue
 *
 * Feature flag: config('strategy_room.enabled'). Se off, 404.
 */
class StrategyRoomController extends Controller
{
    private function ensureEnabled(): void
    {
        if (!config('strategy_room.enabled')) {
            abort(404);
        }
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();

        $sessions = StrategySession::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('messages')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('strategy-room.index', compact('sessions'));
    }

    public function show(Request $request, StrategySession $session)
    {
        $this->ensureEnabled();

        // Isolamento manual (paranoia) — BelongsToTenant ja aplica global scope,
        // mas confirma explicitamente.
        if ((int) $session->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }

        $messages = StrategyMessage::where('strategy_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('strategy-room.show', compact('session', 'messages'));
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();

        $tenantId = (int) auth()->user()->tenant_id;
        if (!$tenantId) abort(403);

        // Cria a sessao vazia agora pra ter o ID pra redirecionar.
        $session = StrategySession::create([
            'tenant_id'    => $tenantId,
            'trigger_type' => 'manual_ui',
            'status'       => 'em_andamento',
        ]);

        RunStrategyDebateJob::dispatch($tenantId, $session->id);

        return redirect()
            ->route('strategy-room.show', $session->id)
            ->with('success', 'Debate iniciado. A pagina vai atualizar automaticamente a cada 5s ate concluir (~40-90s).');
    }
}
