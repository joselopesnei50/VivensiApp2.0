<?php

namespace App\Http\Controllers;

use App\Jobs\RunStrategyDebateJob;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Models\Tenant;
use App\Services\KanbanService;
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
     * Casting da diretoria virtual. Metadata visual + narrativa dos 5 agentes.
     * Nomes aprovados pelo usuario:
     *   Bruce (Estrategista-Chefe · CEO)
     *   Olga (Financeira · CFO)
     *   Maria (Inteligencia · Pesquisadora)
     *   Time Vibra (Mobilizacao · CMO — o "time")
     *   Sofia (Programas · COO — Fase 4)
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
                'ordem'     => 5,
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
            'programas' => [
                'name'      => 'Sofia',
                'role'      => 'Diretora de Programas',
                'sub_role'  => 'COO',
                'bio'       => 'Acompanha frequência, risco de evasão dos beneficiários e execução de tarefas. Só contagens agregadas, nunca nome de beneficiário.',
                'icon'      => 'fa-hands-holding-child',
                'color'     => '#ec4899',
                'from'      => '#f472b6',
                'to'        => '#db2777',
                'initials'  => 'S',
                'ordem'     => 4,
            ],
        ];
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();

        StrategySession::healStaleForTenant((int) auth()->user()->tenant_id);

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

        $session->healIfStale();

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

        $session->healIfStale();

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

        // Cota diaria por tenant (Fase 3) — protege custo de API.
        $quota = (int) config('strategy_room.daily_quota', 10);
        if ($quota > 0) {
            $hoje = StrategySession::where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->count();
            if ($hoje >= $quota) {
                return redirect()
                    ->route('strategy-room.index')
                    ->with('error', "Cota diária de reuniões atingida ({$quota}/dia). A diretoria volta amanhã — ou revise as reuniões de hoje abaixo.");
            }
        }

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

    /**
     * Fase 3 — transforma a decisao do Chefe em card no Kanban Geral.
     * 1 card por sessao: se ja existe, so redireciona pro board.
     */
    public function createTask(Request $request, StrategySession $session, KanbanService $kanban)
    {
        $this->ensureEnabled();

        if ((int) $session->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }

        if ($session->status !== 'concluida') {
            return back()->with('error', 'A reunião ainda não terminou — aguarde a síntese do Bruce.');
        }

        if ($session->kanban_card_id && $session->kanbanCard) {
            return redirect()
                ->route('manager.kanban.index')
                ->with('success', "A tarefa da reunião #{$session->id} já está no Kanban.");
        }

        $action = is_array($session->proposed_action) ? $session->proposed_action : [];
        $titulo = trim((string) ($action['titulo'] ?? ''));

        // Fallback pra sessoes anteriores a Fase 3 (sem proposed_action):
        // usa a primeira frase da fala do Chefe.
        if ($titulo === '') {
            $chefe = $session->messages()->where('agent', 'estrategista_chefe')->latest()->first();
            if (!$chefe) {
                return back()->with('error', 'Esta reunião não tem síntese do Bruce — não há decisão pra virar tarefa.');
            }
            $titulo = mb_substr(preg_split('/(?<=[.!?])\s+/u', $chefe->content, 2)[0] ?? $chefe->content, 0, 120);
            $action['descricao'] = $chefe->content;
        }

        $tenant = Tenant::findOrFail((int) $session->tenant_id);
        $board  = $kanban->ensureDefaultBoard($tenant, auth()->user());
        $column = $board->columns()->orderBy('position')->firstOrFail();

        $descricao = trim((string) ($action['descricao'] ?? ''));
        $descricao .= ($descricao !== '' ? "\n\n" : '')
            . "— Decisão da Sala de Estratégia (reunião #{$session->id}): "
            . route('strategy-room.show', $session->id);

        $card = $kanban->createCard($column, [
            'title'       => $titulo,
            'description' => mb_substr($descricao, 0, 2000),
            'meta'        => [
                'source'              => 'strategy_room',
                'strategy_session_id' => $session->id,
            ],
        ], auth()->user());

        $session->update(['kanban_card_id' => $card->id]);

        return redirect()
            ->route('manager.kanban.index')
            ->with('success', "Tarefa criada no Kanban a partir da reunião #{$session->id}.");
    }
}
