<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\AiFinancialAdvisor;
use App\Services\PerfilOperacionalService;
use App\Models\Campaign;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Models\NgoGrantDocument;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Beneficiary;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Define onboarding steps if not set
        if (!$user->onboarding_steps || count($user->onboarding_steps) === 0) {

            $firstActionLink = '#';
            $firstActionLabel = 'Primeira Ação';

            if ($user->isManager()) {
                $firstActionLink = '/projects/create';
                $firstActionLabel = 'Criar Primeiro Projeto';
            } elseif ($user->isNgo()) {
                $firstActionLink = '/ngo/donors/create';
                $firstActionLabel = 'Cadastrar Doador';
            } else {
                $firstActionLink = '/tasks/create';
                $firstActionLabel = 'Criar Primeira Tarefa';
            }

            $user->update([
                'onboarding_steps' => [
                    ['id' => 'profile', 'label' => 'Completar Perfil', 'completed' => false, 'link' => '/profile'],
                    ['id' => 'first_action', 'label' => $firstActionLabel, 'completed' => false, 'link' => $firstActionLink],
                    ['id' => 'tour', 'label' => 'Explorar o Sistema', 'completed' => false, 'link' => '#'],
                ]
            ]);
            $user->refresh();
        }

        $steps = $user->onboarding_steps ?? [];
        $totalSteps = count($steps);
        $completedSteps = collect($steps)->where('completed', true)->count();

        $onboarding = [
            'completed' => (bool)$user->onboarding_completed_at,
            'steps'     => $steps,
            'percentage' => $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0
        ];

        $role     = $user->role;
        $tenantId = $user->tenant_id;

        view()->share('onboarding', $onboarding);

        switch ($role) {
            case 'super_admin':
                return redirect('/admin');

            case 'manager':
                return $this->managerDashboard($tenantId);

            case 'ngo':
                return $this->ngoDashboard($tenantId);

            default: // common / employee
                return $this->commonDashboard($tenantId);
        }
    }

    public function completeOnboardingStep($stepId)
    {
        $user  = auth()->user();
        $steps = $user->onboarding_steps;

        if (!$steps) return back();

        $updatedSteps = collect($steps)->map(function ($step) use ($stepId) {
            if ($step['id'] === $stepId) {
                $step['completed'] = true;
            }
            return $step;
        })->toArray();

        $user->update(['onboarding_steps' => $updatedSteps]);

        $allCompleted = collect($updatedSteps)->every(fn($s) => $s['completed']);
        if ($allCompleted) {
            $user->update(['onboarding_completed_at' => now()]);
        }

        return back()->with('success', 'Passo do Guia de Início concluído!');
    }

    private function managerDashboard($tenantId)
    {
        // Cache de 5 minutos para métricas financeiras pesadas
        $cachedStats = Cache::remember("dashboard.manager.stats.{$tenantId}", 300, function () use ($tenantId) {
            return [
                'monthlyIncome'    => (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount'),
                'lastMonthIncome'  => (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount'),
                'monthlyExpense'   => (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount'),
                'activeProjects'   => Project::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'overdueTasksCount'=> Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
            ];
        });

        // ── Projetos com progresso real — sem N+1 ──
        // Busca gastos de todos os projetos em 1 query e faz join em memória
        $spentByProject = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereNotNull('project_id')
            ->select('project_id', DB::raw('SUM(amount) as spent'))
            ->groupBy('project_id')
            ->pluck('spent', 'project_id');

        $projects = Project::where('tenant_id', $tenantId)
            ->withCount([
                'tasks as total_tasks',
                'tasks as done_tasks' => fn($q) => $q->whereIn('status', ['done', 'completed']),
            ])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($p) use ($spentByProject) {
                $p->progress = $p->total_tasks > 0
                    ? (int) round(($p->done_tasks / $p->total_tasks) * 100)
                    : 0;
                $spent = (float) ($spentByProject[$p->id] ?? 0);
                $p->spent = $spent;
                $p->budget_percent = ($p->budget > 0) ? min(100, (int) round(($spent / $p->budget) * 100)) : 0;
                return $p;
            });

        $activeProjects = $projects->where('status', 'active')->count()
            ?: Project::where('tenant_id', $tenantId)->where('status', 'active')->count();

        // ── Tarefas urgentes: vencidas ou com prazo em 3 dias ──
        $urgentTasks = Task::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['done', 'completed'])
            ->where(function ($q) {
                $q->whereNotNull('due_date')
                  ->where('due_date', '<=', now()->addDays(3)->toDateString());
            })
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // ── Resumo financeiro — usa cache ──
        $monthlyIncome     = $cachedStats['monthlyIncome'];
        $lastMonthIncome   = $cachedStats['lastMonthIncome'];
        $monthlyExpense    = $cachedStats['monthlyExpense'];
        $overdueTasksCount = $cachedStats['overdueTasksCount'];

        $incomeChange = $lastMonthIncome > 0
            ? (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100
            : null;

        // ── Aprovações de despesas pendentes ──
        $pendingApprovals = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere('approval_status', 'pending');
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ── Feed de Impacto: tarefas concluídas recentemente ──
        $impactFeed = Task::where('tenant_id', $tenantId)
            ->whereIn('status', ['done', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($task) => [
                'icon'  => 'fa-check-double',
                'color' => '#10b981',
                'title' => 'Marco atingido: ' . $task->title,
                'time'  => \Carbon\Carbon::parse($task->updated_at)->diffForHumans(),
            ]);

        // ── Status breakdown ──
        $stats = [
            'team_size'         => User::where('tenant_id', $tenantId)->count(),
            'pending_tasks'     => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->count(),
            'overdue_tasks'     => $overdueTasksCount,
            'monthly_income'    => $monthlyIncome,
            'last_month_income' => $lastMonthIncome,
            'income_change'     => $incomeChange,
            'monthly_expense'   => $monthlyExpense,
            'monthly_balance'   => $monthlyIncome - $monthlyExpense,
            'pending_approvals' => $pendingApprovals->count(),
        ];

        // ── Gráfico de Uso do Sistema (Últimos 6 Meses) — cached 5 min ──
        [$projectsByMonth, $tasksByMonth] = Cache::remember(
            "dashboard.manager.chart.{$tenantId}." . now()->format('Y-m'),
            300,
            function () use ($tenantId) {
                $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
                $isSqlite     = DB::getDriverName() === 'sqlite';
                $yearCreated  = $isSqlite ? "strftime('%Y', created_at)" : 'YEAR(created_at)';
                $monthCreated = $isSqlite ? "strftime('%m', created_at)" : 'MONTH(created_at)';
                $yearUpdated  = $isSqlite ? "strftime('%Y', updated_at)" : 'YEAR(updated_at)';
                $monthUpdated = $isSqlite ? "strftime('%m', updated_at)" : 'MONTH(updated_at)';

                return [
                    DB::table('projects')
                        ->where('tenant_id', $tenantId)
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->selectRaw("{$yearCreated} as y, {$monthCreated} as m, COUNT(*) as total")
                        ->groupByRaw("{$yearCreated}, {$monthCreated}")
                        ->get()
                        ->keyBy(fn($r) => sprintf('%04d-%02d', (int)$r->y, (int)$r->m)),
                    DB::table('tasks')
                        ->where('tenant_id', $tenantId)
                        ->whereIn('status', ['done', 'completed'])
                        ->where('updated_at', '>=', $sixMonthsAgo)
                        ->selectRaw("{$yearUpdated} as y, {$monthUpdated} as m, COUNT(*) as total")
                        ->groupByRaw("{$yearUpdated}, {$monthUpdated}")
                        ->get()
                        ->keyBy(fn($r) => sprintf('%04d-%02d', (int)$r->y, (int)$r->m)),
                ];
            }
        );

        $chartLabels   = [];
        $chartProjects = [];
        $chartTasks    = [];

        for ($i = 5; $i >= 0; $i--) {
            $date            = now()->subMonths($i);
            $key             = $date->format('Y-m');
            $chartLabels[]   = ucfirst($date->translatedFormat('M/Y'));
            $chartProjects[] = (int) ($projectsByMonth[$key]->total ?? 0);
            $chartTasks[]    = (int) ($tasksByMonth[$key]->total ?? 0);
        }

        // ── Radar de Saúde do Projeto (AI Health Radar) ──
        $financialScore  = $monthlyIncome > 0 ? min(100, ($monthlyIncome / ($monthlyIncome + $monthlyExpense)) * 100) : 0;
        $executionScore  = $projects->avg('progress') ?: 0;
        $teamScore       = min(100, ($stats['team_size'] * 10)); // Benchmark: 10 pessoas
        $complianceScore = max(0, 100 - ($stats['pending_approvals'] * 15));
        $fundingScore    = min(100, ($activeProjects * 20)); // Benchmark: 5 projetos ativos

        $radarData = [
            'labels' => ['Financeiro', 'Execução', 'Equipe', 'Conformidade', 'Captação'],
            'scores' => [$financialScore, $executionScore, $teamScore, $complianceScore, $fundingScore]
        ];

        // ── Marcadores do Mapa de Impacto — cached 10 min ──
        $mapMarkers = Cache::remember("dashboard.manager.map.{$tenantId}", 600, function () use ($tenantId) {
            return Beneficiary::where('tenant_id', $tenantId)
                ->whereNotNull('latitude')
                ->get(['name', 'latitude', 'longitude', 'status'])
                ->map(fn($b) => [
                    'lat'   => (float)$b->latitude,
                    'lng'   => (float)$b->longitude,
                    'label' => $b->name,
                    'type'  => 'beneficiary',
                ])->toArray();
        });

        // ── Máquina de Engajamento (WhatsApp) — cached 5 min ──
        $waCache = Cache::remember("dashboard.manager.wa.{$tenantId}", 300, function () use ($tenantId) {
            $waStats = DB::table('whatsapp_messages')
                ->join('whatsapp_chats', 'whatsapp_messages.chat_id', '=', 'whatsapp_chats.id')
                ->where('whatsapp_chats.tenant_id', $tenantId)
                ->select(
                    'whatsapp_messages.direction',
                    DB::raw("SUM(CASE WHEN whatsapp_messages.status IN ('delivered','read') AND whatsapp_messages.direction='outbound' THEN 1 ELSE 0 END) as delivered"),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('whatsapp_messages.direction')
                ->get()
                ->keyBy('direction');

            $sevenDaysAgo = now()->subDays(6)->startOfDay();
            $waDailyRaw = DB::table('whatsapp_messages')
                ->join('whatsapp_chats', 'whatsapp_messages.chat_id', '=', 'whatsapp_chats.id')
                ->where('whatsapp_chats.tenant_id', $tenantId)
                ->where('whatsapp_messages.created_at', '>=', $sevenDaysAgo)
                ->selectRaw("DATE(whatsapp_messages.created_at) as day, whatsapp_messages.direction, COUNT(*) as total")
                ->groupByRaw("DATE(whatsapp_messages.created_at), whatsapp_messages.direction")
                ->get()
                ->groupBy('day');

            return [
                'stats'    => [
                    'total_sent'      => (int) ($waStats['outbound']->total ?? 0),
                    'total_delivered' => (int) ($waStats['outbound']->delivered ?? 0),
                    'total_replies'   => (int) ($waStats['inbound']->total ?? 0),
                ],
                'dailyRaw' => $waDailyRaw,
            ];
        });

        $whatsappStats = $waCache['stats'];
        $waDailyRaw    = $waCache['dailyRaw'];

        $waDailyLabels   = [];
        $waDailySent     = [];
        $waDailyReceived = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $waDailyLabels[] = now()->subDays($i)->format('d/m');
            $dayData = $waDailyRaw->get($day, collect());
            $waDailySent[]     = (int) ($dayData->firstWhere('direction', 'outbound')->total ?? 0);
            $waDailyReceived[] = (int) ($dayData->firstWhere('direction', 'inbound')->total ?? 0);
        }

        // ── KPIs operacionais por Perfil (Fase 1 — Etapa C) ─────────────────
        // Resolve sources sob demanda — categorias 'outro' não pedem nada
        // e o foreach abaixo nem entra. Mantém o painel atual intacto.
        $resolvedKpis = [];
        $leadsBreakdown = null;
        $tenant = \App\Models\Tenant::find($tenantId);
        if ($tenant !== null) {
            $perfilService = app(PerfilOperacionalService::class);
            $sources       = $perfilService->getKpiSources($tenant);
            $sourceValues  = [];
            foreach ($sources as $source) {
                $sourceValues[$source] = $this->resolveKpiSource($source, $tenantId, $whatsappStats);
            }
            $resolvedKpis = $perfilService->getResolvedKpis($tenant, $sourceValues);

            // P1.6 — Base de cadastros por cidade/segmentação (só categorias
            // que vendem mobilização). Devolve null para perfis que não usam.
            $leadsBreakdown = $this->resolveLeadsBreakdown($tenant, $perfilService);
        }

        return view('dashboards.manager', compact(
            'activeProjects', 'impactFeed', 'stats',
            'projects', 'urgentTasks', 'pendingApprovals',
            'chartLabels', 'chartProjects', 'chartTasks',
            'radarData', 'mapMarkers', 'whatsappStats',
            'waDailyLabels', 'waDailySent', 'waDailyReceived',
            'resolvedKpis', 'leadsBreakdown'
        ));
    }

    /**
     * P1.6 — Top 10 cidades e top 10 tags da base de leads ativos
     * (status pending + confirmed). Só roda para perfis que vendem
     * mobilização; demais retornam null pra view não exibir a seção.
     */
    private function resolveLeadsBreakdown(\App\Models\Tenant $tenant, PerfilOperacionalService $perfilService): ?array
    {
        $categoria = $perfilService->getCategoria($tenant);
        $habilitadas = [
            \App\Models\TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL,
            \App\Models\TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL,
        ];
        if (!in_array($categoria, $habilitadas, true)) {
            return null;
        }

        return Cache::remember("dashboard.manager.leads_breakdown.{$tenant->id}", 300, function () use ($tenant) {
            $rows = Lead::where('tenant_id', $tenant->id)
                ->whereIn('status', [Lead::STATUS_PENDING, Lead::STATUS_CONFIRMED])
                ->get(['city', 'tags']);

            $total = $rows->count();
            if ($total === 0) {
                return [
                    'total'  => 0,
                    'cities' => ['labels' => [], 'values' => []],
                    'tags'   => ['labels' => [], 'values' => []],
                ];
            }

            // Agrupamento puro em PHP — para a ordem de magnitude esperada
            // (poucos milhares por tenant) é mais simples que GROUP BY com
            // JSON_TABLE e portável entre MySQL/SQLite.
            $cityCounts = [];
            $tagCounts  = [];
            foreach ($rows as $r) {
                $city = trim((string) ($r->city ?? ''));
                if ($city !== '') {
                    $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
                }
                foreach ((array) ($r->tags ?? []) as $tag) {
                    $tag = trim((string) $tag);
                    if ($tag !== '') {
                        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                    }
                }
            }

            return [
                'total'  => $total,
                'cities' => $this->topNWithOthers($cityCounts, 10, 'Sem cidade'),
                'tags'   => $this->topNWithOthers($tagCounts, 10, 'Sem segmentação'),
            ];
        });
    }

    /**
     * Ordena desc, mantém top N e empilha o resto como "Outras".
     * Quando o map está vazio devolve labels com placeholder pra view
     * indicar "ninguém ainda" em vez de gráfico em branco.
     *
     * @param array<string,int> $counts
     * @return array{labels: list<string>, values: list<int>}
     */
    private function topNWithOthers(array $counts, int $topN, string $emptyLabel): array
    {
        if ($counts === []) {
            return ['labels' => [$emptyLabel], 'values' => [0]];
        }
        arsort($counts);
        $top    = array_slice($counts, 0, $topN, true);
        $rest   = array_slice($counts, $topN, null, true);
        $labels = array_keys($top);
        $values = array_values($top);
        $restSum = array_sum($rest);
        if ($restSum > 0) {
            $labels[] = 'Outras';
            $values[] = $restSum;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Resolve um source de KPI operacional. Mantém cache curto pra não
     * sobrecarregar o dashboard quando o perfil ativa fontes adicionais.
     */
    private function resolveKpiSource(string $source, int $tenantId, array $whatsappStats)
    {
        return match ($source) {
            'whatsapp_inbound_total' => (int) ($whatsappStats['total_replies'] ?? 0),
            'leads_total' => (int) Cache::remember(
                "dashboard.manager.kpi.leads.{$tenantId}",
                300,
                fn () => Lead::where('tenant_id', $tenantId)->count()
            ),
            // monthly_revenue já aparece na hero como "Maré Financeira" — null
            // sinaliza pro Service omitir e evitar duplicar card.
            'monthly_revenue' => null,
            default => null,
        };
    }

    private function ngoDashboard($tenantId)
    {
        // Cache NGO stats for 5 minutes
        $stats = \Cache::remember("ngo_stats_{$tenantId}", 300, function () use ($tenantId) {
            $advisor = new AiFinancialAdvisor($tenantId);

            $monthlyIncome     = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount');
            $lastMonthIncome   = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount');

            $incomeChange = null;
            if ($lastMonthIncome > 0) {
                $incomeChange = (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100;
            }

            return [
                'runway'             => number_format($advisor->getSurvivalMetrics()['months_left'], 1),
                'monthly_income'     => $monthlyIncome,
                'last_month_income'  => $lastMonthIncome,
                'income_change'      => $incomeChange,
                'volunteers_count'   => Volunteer::where('tenant_id', $tenantId)->count(),
                'total_donors'       => NgoDonor::where('tenant_id', $tenantId)->count(),
                'beneficiary_count'  => Beneficiary::where('tenant_id', $tenantId)->count(),
                'active_campaigns'   => Campaign::where('tenant_id', $tenantId)->where('status', 'active')->get(),
                'recent_grants'      => NgoGrant::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->limit(3)->get(),
                'upcoming_deadlines' => NgoGrant::where('tenant_id', $tenantId)
                                            ->whereNotIn('status', ['closed'])
                                            ->whereNotNull('deadline')
                                            ->where('deadline', '>=', now())
                                            ->where('deadline', '<=', now()->addDays(30))
                                            ->orderBy('deadline')
                                            ->limit(3)
                                            ->get(),
                'ai_insight'         => data_get($advisor->getInsights(), '0.message', 'Adicione mais transações para gerar insights precisos.'),
            ];
        });

        // ── Projetos com progresso real — sem N+1 ──
        $ngoSpentByProject = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereNotNull('project_id')
            ->select('project_id', DB::raw('SUM(amount) as spent'))
            ->groupBy('project_id')
            ->pluck('spent', 'project_id');

        $projects = Project::where('tenant_id', $tenantId)
            ->withCount([
                'tasks as total_tasks',
                'tasks as done_tasks' => fn($q) => $q->whereIn('status', ['done', 'completed']),
            ])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($p) use ($ngoSpentByProject) {
                $p->progress = $p->total_tasks > 0
                    ? (int) round(($p->done_tasks / $p->total_tasks) * 100)
                    : 0;
                $spent = (float) ($ngoSpentByProject[$p->id] ?? 0);
                $p->spent = $spent;
                $p->budget_percent = ($p->budget > 0) ? min(100, (int) round(($spent / $p->budget) * 100)) : 0;
                return $p;
            });

        // Impact feed not cached (real-time)
        $impactFeed = collect();

        $upcomingGrants = NgoGrant::where('tenant_id', $tenantId)
            ->where('deadline', '>=', now())
            ->orderBy('deadline', 'asc')
            ->limit(2)
            ->get();

        foreach ($upcomingGrants as $grant) {
            $impactFeed->push([
                'icon'  => 'fa-file-signature',
                'color' => '#f59e0b',
                'title' => 'Prazo de Edital: ' . $grant->title,
                'time'  => 'Vence ' . \Carbon\Carbon::parse($grant->deadline)->diffForHumans()
            ]);
        }

        $recentDonations = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentDonations as $donation) {
            $impactFeed->push([
                'icon'  => 'fa-heart',
                'color' => '#ef4444',
                'title' => 'Nova Doação Recebida: R$ ' . number_format((float) $donation->amount, 0),
                'time'  => \Carbon\Carbon::parse($donation->date)->diffForHumans()
            ]);
        }

        $stats['impactFeed'] = $impactFeed;

        // ── Gráfico de Captação (Últimos 6 Meses) — cached 5 min ──
        [$donorsByMonth, $donationsByMonth] = Cache::remember(
            "dashboard.ngo.chart.{$tenantId}." . now()->format('Y-m'),
            300,
            function () use ($tenantId) {
                $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
                $isSqlite     = DB::getDriverName() === 'sqlite';
                $yearCreated  = $isSqlite ? "strftime('%Y', created_at)" : 'YEAR(created_at)';
                $monthCreated = $isSqlite ? "strftime('%m', created_at)" : 'MONTH(created_at)';
                $yearDate     = $isSqlite ? "strftime('%Y', date)" : 'YEAR(date)';
                $monthDate    = $isSqlite ? "strftime('%m', date)" : 'MONTH(date)';

                return [
                    DB::table('ngo_donors')
                        ->where('tenant_id', $tenantId)
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->selectRaw("{$yearCreated} as y, {$monthCreated} as m, COUNT(*) as total")
                        ->groupByRaw("{$yearCreated}, {$monthCreated}")
                        ->get()
                        ->keyBy(fn($r) => sprintf('%04d-%02d', (int)$r->y, (int)$r->m)),
                    DB::table('transactions')
                        ->where('tenant_id', $tenantId)
                        ->where('type', 'income')
                        ->where('date', '>=', $sixMonthsAgo)
                        ->selectRaw("{$yearDate} as y, {$monthDate} as m, SUM(amount) as total")
                        ->groupByRaw("{$yearDate}, {$monthDate}")
                        ->get()
                        ->keyBy(fn($r) => sprintf('%04d-%02d', (int)$r->y, (int)$r->m)),
                ];
            }
        );

        $chartLabels    = [];
        $chartDonors    = [];
        $chartDonations = [];

        for ($i = 5; $i >= 0; $i--) {
            $date             = now()->subMonths($i);
            $key              = $date->format('Y-m');
            $chartLabels[]    = ucfirst($date->translatedFormat('M/Y'));
            $chartDonors[]    = (int) ($donorsByMonth[$key]->total ?? 0);
            $chartDonations[] = (float) ($donationsByMonth[$key]->total ?? 0);
        }

        // ── Radar de Saúde da ONG (AI Health Radar) ──
        $financialScore  = $stats['monthly_income'] > 0 ? 100 : 0;
        $executionScore  = min(100, (collect($stats['recent_grants'])->where('status', 'open')->count() * 33));
        $teamScore       = min(100, ($stats['volunteers_count'] * 5)); // Benchmark: 20 voluntários
        $complianceScore = max(0, 100 - ($impactFeed->where('icon', 'fa-file-signature')->count() * 20));
        $fundingScore    = min(100, ($stats['total_donors'] / 10)); // Benchmark: 1000 doadores

        $radarData = [
            'labels' => ['Financeiro', 'Projetos', 'Equipe', 'Editais', 'Doadores'],
            'scores' => [$financialScore, $executionScore, $teamScore, $complianceScore, $fundingScore]
        ];

        // ── Distribuição Geográfica — beneficiários + doadores por estado e cidade ──
        $benefByState = Beneficiary::where('tenant_id', $tenantId)
            ->whereNotNull('address_state')->where('address_state', '!=', '')
            ->selectRaw('address_state as state, COUNT(*) as total')
            ->groupBy('address_state')->pluck('total', 'state')->toArray();

        $donorsByState = NgoDonor::where('tenant_id', $tenantId)
            ->whereNotNull('address_state')->where('address_state', '!=', '')
            ->selectRaw('address_state as state, COUNT(*) as total')
            ->groupBy('address_state')->pluck('total', 'state')->toArray();

        // Merge states, sum counts
        $geoByState = [];
        foreach (array_unique(array_merge(array_keys($benefByState), array_keys($donorsByState))) as $st) {
            $geoByState[$st] = ($benefByState[$st] ?? 0) + ($donorsByState[$st] ?? 0);
        }
        arsort($geoByState);
        $geoByState = array_slice($geoByState, 0, 8, true);

        $benefByCity = Beneficiary::where('tenant_id', $tenantId)
            ->whereNotNull('address_city')->where('address_city', '!=', '')
            ->selectRaw('address_city as city, address_state as state, COUNT(*) as total')
            ->groupBy('address_city', 'address_state')->get()->keyBy('city')->toArray();

        $donorsByCity = NgoDonor::where('tenant_id', $tenantId)
            ->whereNotNull('address_city')->where('address_city', '!=', '')
            ->selectRaw('address_city as city, address_state as state, COUNT(*) as total')
            ->groupBy('address_city', 'address_state')->get()->keyBy('city')->toArray();

        // Merge cities
        $mergedCities = [];
        foreach (array_unique(array_merge(array_keys($benefByCity), array_keys($donorsByCity))) as $city) {
            $mergedCities[$city] = [
                'city'   => $city,
                'state'  => ($benefByCity[$city]['state'] ?? $donorsByCity[$city]['state'] ?? ''),
                'benef'  => (int) ($benefByCity[$city]['total']  ?? 0),
                'donors' => (int) ($donorsByCity[$city]['total'] ?? 0),
                'total'  => (int) ($benefByCity[$city]['total']  ?? 0) + (int) ($donorsByCity[$city]['total'] ?? 0),
            ];
        }
        usort($mergedCities, fn($a, $b) => $b['total'] - $a['total']);
        $geoByCity = array_slice($mergedCities, 0, 8);

        $geoTotal = array_sum(array_column($geoByCity, 'total'));

        // ── Equipe — cached 10 min (muda raramente) ──
        $teamUsers = Cache::remember("dashboard.ngo.team.{$tenantId}", 600, function () use ($tenantId) {
            return User::where('tenant_id', $tenantId)
                ->whereNotIn('role', ['super_admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        });

        $today         = now()->toDateString();
        $upcomingTasks = Task::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['done', 'completed'])
            ->whereNotNull('due_date')
            ->orderByRaw("CASE WHEN due_date < ? THEN 0 ELSE 1 END", [$today])
            ->orderBy('due_date', 'asc')
            ->limit(12)
            ->with(['assignee:id,name'])
            ->get();

        return view('dashboards.ngo', compact(
            'stats',
            'chartLabels',
            'chartDonors',
            'chartDonations',
            'radarData',
            'geoByState',
            'geoByCity',
            'geoTotal',
            'teamUsers',
            'upcomingTasks',
            'projects'
        ));
    }

    private function commonDashboard($tenantId)
    {
        $userId = Auth::id();
        \Carbon\Carbon::setLocale('pt_BR');

        // Módulo MEI — 3 métricas-vendedoras (teto, DAS, mini-DRE).
        $meiSvc = app(\App\Services\MeiPanelService::class);
        $meiTeto = $meiSvc->tetoMei($tenantId);
        $meiDas  = $meiSvc->proximoDas($tenantId);
        $meiDre  = $meiSvc->dreMensal($tenantId);

        // ── Financeiro: cache 5 min por tenant (não é user-specific) ──────
        $financial = Cache::remember("dashboard.common.financial.{$tenantId}." . now()->format('Y-m'), 300, function () use ($tenantId) {
            $totalIncome  = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->sum('amount');
            $totalExpense = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->sum('amount');

            $monthlyIncome  = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount');
            $monthlyExpense = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount');

            $lastMonthIncome  = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount');
            $lastMonthExpense = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount');

            // Gráfico semestral — 1 query com GROUP BY type (SQLite/MySQL compatible)
            $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
            $isSqlite     = DB::getDriverName() === 'sqlite';
            $yearExpr     = $isSqlite ? "strftime('%Y', date)" : 'YEAR(date)';
            $monthExpr    = $isSqlite ? "strftime('%m', date)" : 'MONTH(date)';
            $monthlyData  = DB::table('transactions')
                ->where('tenant_id', $tenantId)->where('status', 'paid')->where('date', '>=', $sixMonthsAgo)
                ->selectRaw("{$yearExpr} as y, {$monthExpr} as m, type, SUM(amount) as total")
                ->groupByRaw("{$yearExpr}, {$monthExpr}, type")
                ->get()->groupBy(fn($r) => sprintf('%04d-%02d', (int)$r->y, (int)$r->m));

            $chartLabels = $chartIncome = $chartExpense = [];
            for ($i = 5; $i >= 0; $i--) {
                $date           = now()->subMonths($i);
                $rows           = $monthlyData->get($date->format('Y-m'), collect());
                $chartLabels[]  = ucfirst($date->translatedFormat('M/Y'));
                $chartIncome[]  = (float) ($rows->firstWhere('type', 'income')->total  ?? 0);
                $chartExpense[] = (float) ($rows->firstWhere('type', 'expense')->total ?? 0);
            }

            return compact('totalIncome', 'totalExpense', 'monthlyIncome', 'monthlyExpense', 'lastMonthIncome', 'lastMonthExpense', 'chartLabels', 'chartIncome', 'chartExpense');
        });

        $totalIncome      = $financial['totalIncome'];
        $totalExpense     = $financial['totalExpense'];
        $balance          = $totalIncome - $totalExpense;
        $monthlyIncome    = $financial['monthlyIncome'];
        $monthlyExpense   = $financial['monthlyExpense'];
        $monthlyBalance   = $monthlyIncome - $monthlyExpense;
        $lastMonthIncome  = $financial['lastMonthIncome'];
        $lastMonthExpense = $financial['lastMonthExpense'];
        $chartLabels      = $financial['chartLabels'];
        $chartIncome      = $financial['chartIncome'];
        $chartExpense     = $financial['chartExpense'];

        $incomeChange  = $lastMonthIncome  > 0 ? (($monthlyIncome  - $lastMonthIncome)  / $lastMonthIncome)  * 100 : null;
        $expenseChange = $lastMonthExpense > 0 ? (($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100 : null;

        // ── Dados user-specific: não cacheados ────────────────────────────
        $overdueCount = Task::where('tenant_id', $tenantId)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['done', 'completed'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $recentTransactions = Transaction::where('tenant_id', $tenantId)
            ->orderBy('date', 'desc')->limit(5)->get();

        $pendingTasks = Task::where('tenant_id', $tenantId)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['done', 'completed'])
            ->orderBy('due_date', 'asc')->limit(5)->get();

        $impactFeed = collect();
        foreach ($pendingTasks->take(3) as $task) {
            if ($task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast()) {
                $impactFeed->push(['icon' => 'fa-triangle-exclamation', 'color' => '#ef4444', 'title' => 'Tarefa vencida: ' . $task->title, 'time' => 'Deveria estar concluída ' . \Carbon\Carbon::parse($task->due_date)->diffForHumans()]);
            } elseif (str_contains(strtolower($task->title), 'pagar') || str_contains(strtolower($task->title), 'conta')) {
                $impactFeed->push(['icon' => 'fa-receipt', 'color' => '#6366f1', 'title' => 'Lembrete de Pagamento: ' . $task->title, 'time' => $task->due_date ? 'Para ' . \Carbon\Carbon::parse($task->due_date)->format('d/m') : 'Pendente']);
            }
        }
        if ($balance > 1000) {
            $impactFeed->push(['icon' => 'fa-trophy', 'color' => '#10b981', 'title' => 'Meta de Reserva: Saldo acima de R$ 1.000', 'time' => 'Hoje']);
        }

        return view('dashboards.common', compact(
            'totalIncome', 'totalExpense', 'balance',
            'monthlyIncome', 'monthlyExpense', 'monthlyBalance',
            'lastMonthIncome', 'incomeChange', 'expenseChange',
            'overdueCount',
            'recentTransactions', 'pendingTasks',
            'chartLabels', 'chartIncome', 'chartExpense',
            'impactFeed',
            'meiTeto', 'meiDas', 'meiDre'
        ));
    }
}
