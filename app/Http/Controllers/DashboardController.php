<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AiFinancialAdvisor;
use App\Models\Campaign;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Models\NgoGrantDocument;
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

            if ($user->role === 'manager') {
                $firstActionLink = '/projects/create';
                $firstActionLabel = 'Criar Primeiro Projeto';
            } elseif ($user->role === 'ngo') {
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
        // ── Projetos com progresso real (tarefas concluídas / total) ──
        $projects = Project::where('tenant_id', $tenantId)
            ->withCount([
                'tasks as total_tasks',
                'tasks as done_tasks' => fn($q) => $q->whereIn('status', ['done', 'completed']),
            ])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($p) {
                $p->progress = $p->total_tasks > 0
                    ? (int) round(($p->done_tasks / $p->total_tasks) * 100)
                    : 0;
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

        // ── Resumo financeiro do mês atual ──
        $monthlyIncome = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

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
            'monthly_income'    => $monthlyIncome,
            'monthly_expense'   => $monthlyExpense,
            'monthly_balance'   => $monthlyIncome - $monthlyExpense,
            'pending_approvals' => $pendingApprovals->count(),
        ];

        // ── Gráfico de Uso do Sistema (Últimos 6 Meses) — 2 queries em vez de 12 ──
        $chartLabels   = [];
        $chartProjects = [];
        $chartTasks    = [];

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        $projectsByMonth = DB::table('projects')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn($r) => sprintf('%04d-%02d', $r->y, $r->m));

        $tasksByMonth = DB::table('tasks')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['done', 'completed'])
            ->where('updated_at', '>=', $sixMonthsAgo)
            ->selectRaw('YEAR(updated_at) as y, MONTH(updated_at) as m, COUNT(*) as total')
            ->groupByRaw('YEAR(updated_at), MONTH(updated_at)')
            ->get()
            ->keyBy(fn($r) => sprintf('%04d-%02d', $r->y, $r->m));

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

        // ── Marcadores do Mapa de Impacto ──
        $mapMarkers = Beneficiary::where('tenant_id', $tenantId)
            ->whereNotNull('latitude')
            ->get(['name', 'latitude', 'longitude', 'status'])
            ->map(fn($b) => [
                'lat'   => (float)$b->latitude,
                'lng'   => (float)$b->longitude,
                'label' => $b->name,
                'type'  => 'beneficiary'
            ])->toArray();

        return view('dashboards.manager', compact(
            'activeProjects', 'impactFeed', 'stats',
            'projects', 'urgentTasks', 'pendingApprovals',
            'chartLabels', 'chartProjects', 'chartTasks',
            'radarData', 'mapMarkers'
        ));
    }

    private function ngoDashboard($tenantId)
    {
        // Cache NGO stats for 5 minutes
        $stats = \Cache::remember("ngo_stats_{$tenantId}", 300, function () use ($tenantId) {
            $advisor = new AiFinancialAdvisor($tenantId);

            return [
                'runway'           => number_format($advisor->getSurvivalMetrics()['months_left'], 1),
                'monthly_income'   => Transaction::where('tenant_id', $tenantId)->where('type', 'income')->whereMonth('date', now()->month)->sum('amount'),
                'volunteers_count' => Volunteer::where('tenant_id', $tenantId)->count(),
                'total_donors'     => NgoDonor::where('tenant_id', $tenantId)->count(),
                'active_campaigns' => Campaign::where('tenant_id', $tenantId)->where('status', 'active')->get(),
                'recent_grants'    => NgoGrant::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->limit(3)->get(),
                'ai_insight'       => data_get($advisor->getInsights(), '0.message', 'Adicione mais transações para gerar insights precisos.'),
            ];
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

        // ── Gráfico de Captação (Últimos 6 Meses) — 2 queries em vez de 12 ──
        $chartLabels    = [];
        $chartDonors    = [];
        $chartDonations = [];

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        $donorsByMonth = DB::table('ngo_donors')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn($r) => sprintf('%04d-%02d', $r->y, $r->m));

        $donationsByMonth = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('date', '>=', $sixMonthsAgo)
            ->selectRaw('YEAR(date) as y, MONTH(date) as m, SUM(amount) as total')
            ->groupByRaw('YEAR(date), MONTH(date)')
            ->get()
            ->keyBy(fn($r) => sprintf('%04d-%02d', $r->y, $r->m));

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

        // ── Marcadores do Mapa de Impacto ──
        $beneficiaries = Beneficiary::where('tenant_id', $tenantId)
            ->whereNotNull('latitude')
            ->get(['name', 'latitude', 'longitude'])
            ->map(fn($b) => ['lat' => (float)$b->latitude, 'lng' => (float)$b->longitude, 'label' => $b->name, 'type' => 'beneficiary']);

        $donors = NgoDonor::where('tenant_id', $tenantId)
            ->whereNotNull('latitude')
            ->get(['name', 'latitude', 'longitude'])
            ->map(fn($d) => ['lat' => (float)$d->latitude, 'lng' => (float)$d->longitude, 'label' => $d->name, 'type' => 'donor']);

        $mapMarkers = $beneficiaries->concat($donors)->toArray();

        $teamUsers = User::where('tenant_id', $tenantId)
            ->whereNotIn('role', ['super_admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $upcomingTasks = Task::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['done', 'completed'])
            ->whereNotNull('due_date')
            ->orderByRaw("CASE WHEN due_date < CURDATE() THEN 0 ELSE 1 END")
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
            'mapMarkers',
            'teamUsers',
            'upcomingTasks'
        ));
    }

    private function commonDashboard($tenantId)
    {
        $userId = Auth::id();

        // Totais financeiros do período inteiro
        $totalIncome = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->sum('amount');

        $balance = $totalIncome - $totalExpense;

        // Transações recentes
        $recentTransactions = Transaction::where('tenant_id', $tenantId)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        // Tarefas pessoais pendentes do usuário
        $pendingTasks = Task::where('tenant_id', $tenantId)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['done', 'completed'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // Feed de Impacto: alertas financeiros pessoais
        $impactFeed = collect();

        foreach ($pendingTasks->take(3) as $task) {
            if ($task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast()) {
                $impactFeed->push([
                    'icon'  => 'fa-triangle-exclamation',
                    'color' => '#ef4444',
                    'title' => 'Tarefa vencida: ' . $task->title,
                    'time'  => 'Deveria estar concluída ' . \Carbon\Carbon::parse($task->due_date)->diffForHumans(),
                ]);
            } elseif (str_contains(strtolower($task->title), 'pagar') || str_contains(strtolower($task->title), 'conta')) {
                $impactFeed->push([
                    'icon'  => 'fa-receipt',
                    'color' => '#6366f1',
                    'title' => 'Lembrete de Pagamento: ' . $task->title,
                    'time'  => $task->due_date ? 'Para ' . \Carbon\Carbon::parse($task->due_date)->format('d/m') : 'Pendente',
                ]);
            }
        }

        if ($balance > 1000) {
            $impactFeed->push([
                'icon'  => 'fa-trophy',
                'color' => '#10b981',
                'title' => 'Meta de Reserva: Saldo acima de R$ 1.000',
                'time'  => 'Hoje',
            ]);
        }

        // 6 Meses de histórico para o gráfico
        $chartLabels  = [];
        $chartIncome  = [];
        $chartExpense = [];

        for ($i = 5; $i >= 0; $i--) {
            $date     = now()->subMonths($i);
            $monthNum = $date->month;
            $year     = $date->year;

            $chartLabels[] = ucfirst($date->translatedFormat('M/Y'));

            $chartIncome[] = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->sum('amount');

            $chartExpense[] = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->sum('amount');
        }

        return view('dashboards.common', compact(
            'totalIncome', 'totalExpense', 'balance',
            'recentTransactions', 'pendingTasks',
            'chartLabels', 'chartIncome', 'chartExpense',
            'impactFeed'
        ));
    }
}
