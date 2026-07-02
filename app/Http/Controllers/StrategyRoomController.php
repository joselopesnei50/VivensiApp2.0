<?php

namespace App\Http\Controllers;

use App\Jobs\RunStrategyDebateJob;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sala de Estrategia — Fase 2. UI premium.
 *
 * Rotas:
 *   GET  /strategy-room                     → index (diretoria + sessoes)
 *   GET  /strategy-room/{session}           → detalhe (timeline das falas)
 *   GET  /strategy-room/{session}/status    → JSON pra polling AJAX
 *   POST /strategy-room                     → dispara novo debate em queue
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

    /**
     * Casting da diretoria virtual. Metadata visual + narrativa dos 4 agentes.
     * Nomes aprovados pelo usuario:
     *   Bruce (Estrategista-Chefe · CEO)
     *   Olga (Financeira · CFO)
     *   Maria (Inteligencia · Pesquisadora)
     *   Time Vibra (Mobilizacao · CMO — o "time")
     */
    public static function agentsMeta(): array
    {
        return [
            'estrategista_chefe' => [
                'name'      => 'Bruce',
                'role'      => 'Estrategista-Chefe',
                'sub_role'  => 'CEO · Moderador',
                'bio'       => 'Sintetiza as vozes dos outros três e prioriza uma ação. Nunca recebe dado bruto — só as falas dos colegas.',
                'icon'      => 'fa-chess-king',
                'color'     => '#4f46e5',
                'from'      => '#6366f1',
                'to'        => '#4338ca',
                'initials'  => 'B',
                'ordem'     => 4,
            ],
            'financeiro' => [
                'name'      => 'Olga',
                'role'      => 'Guardiã do Caixa',
                'sub_role'  => 'CFO',
                'bio'       => 'Analisa saldo, receita, despesa e score por projeto. Nunca projeta cenário com número que não veio do painel.',
                'icon'      => 'fa-coins',
                'color'     => '#10b981',
                'from'      => '#34d399',
                'to'        => '#059669',
                'initials'  => 'O',
                'ordem'     => 1,
            ],
            'inteligencia' => [
                'name'      => 'Maria',
                'role'      => 'Pesquisadora',
                'sub_role'  => 'Mapa de Oportunidades',
                'bio'       => 'Cruza pipeline de projetos com editais cadastrados. Nunca cita edital que não veio do sistema.',
                'icon'      => 'fa-magnifying-glass-chart',
                'color'     => '#3b82f6',
                'from'      => '#60a5fa',
                'to'        => '#2563eb',
                'initials'  => 'M',
                'ordem'     => 2,
            ],
            'mobilizacao' => [
                'name'      => 'Time Vibra',
                'role'      => 'Canais e Mobilização',
                'sub_role'  => 'CMO',
                'bio'       => 'Mede uso dos canais (WhatsApp, e-mail) e saúde da base de contatos. Sempre agregado, nunca PII individual.',
                'icon'      => 'fa-bullhorn',
                'color'     => '#f59e0b',
                'from'      => '#fbbf24',
                'to'        => '#d97706',
                'initials'  => 'V',
                'ordem'     => 3,
            ],
        ];
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();

        $sessions = StrategySession::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('messages')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $agents = collect(self::agentsMeta())->sortBy('ordem')->values()->all();

        return view('strategy-room.index', compact('sessions', 'agents'));
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

        $agents = self::agentsMeta();

        return view('strategy-room.show', compact('session', 'messages', 'agents'));
    }

    /**
     * Endpoint JSON pra polling AJAX. Substitui o antigo <meta http-equiv=refresh>
     * que reloadava a pagina inteira (efeito colateral: menu piscava a cada 5s).
     */
    public function status(Request $request, StrategySession $session): JsonResponse
    {
        $this->ensureEnabled();

        if ((int) $session->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }

        return response()->json([
            'session_id'     => $session->id,
            'status'         => $session->status,
            'messages_count' => $session->messages()->count(),
            'is_running'     => $session->status === 'em_andamento',
        ]);
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
            ->with('success', 'Reunião estratégica iniciada. A página atualiza sozinha assim que a diretoria concluir (~40-90s).');
    }
}
