<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AiFinancialAdvisor;
use App\Models\Campaign;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Models\Project;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Volunteer;

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
            'steps' => $steps,
            'percentage' => $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0
        ];

        $role = $user->role;
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
        $user = auth()->user();
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
        // Cache manager stats for 5 minutes
        $activeProjects = \Cache::remember("manager_stats_{$tenantId}", 300, function() use ($tenantId) {
            return Project::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count();
        });

        // Feed de Impacto do Gestor: Atividades recentes em projetos
        $impactFeed = Task::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($task) {
                return [
                    'icon' => 'fa-check-double',
                    'color' => '#10b981',
                    'title' => 'Marco atingido: ' . $task->title,
                    'time' => \Carbon\Carbon::parse($task->updated_at)->diffForHumans()
                ];
            });
        
        return view('dashboards.manager', [
            'activeProjects' => $activeProjects,
            'impactFeed' => $impactFeed,
            'stats' => [
                'team_size' => User::where('tenant_id', $tenantId)->count(),
                'pending_tasks' => Task::where('tenant_id', $tenantId)->where('status', '!=', 'completed')->count()
            ]
        ]);
    }

    private function ngoDashboard($tenantId)
    {
        // Cache NGO stats for 5 minutes
        $stats = \Cache::remember("ngo_stats_{$tenantId}", 300, function() use ($tenantId) {
            $advisor = new AiFinancialAdvisor($tenantId);
        
        // Feed de Impacto da ONG: Editais e Doações
        $impactFeed = collect();
        
        // Editais próximos do vencimento
        $upcomingGrants = NgoGrant::where('tenant_id', $tenantId)
            ->where('deadline', '>=', now())
            ->orderBy('deadline', 'asc')
            ->limit(2)
            ->get();

        foreach($upcomingGrants as $grant) {
            $impactFeed->push([
                'icon' => 'fa-file-signature',
                'color' => '#f59e0b',
                'title' => 'Prazo de Edital: ' . $grant->title,
                'time' => 'Vence ' . \Carbon\Carbon::parse($grant->deadline)->diffForHumans()
            ]);
        }

        // Doações recentes
        $recentDonations = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get();

        foreach($recentDonations as $donation) {
            $impactFeed->push([
                'icon' => 'fa-heart',
                'color' => '#ef4444',
                'title' => 'Nova Doação Recebida: R$ ' . number_format((float) $donation->amount, 0),
                'time' => \Carbon\Carbon::parse($donation->date)->diffForHumans()
            ]);
        }

        $stats = [
            'runway' => number_format($advisor->getSurvivalMetrics()['months_left'], 1),
            'monthly_income' => Transaction::where('tenant_id', $tenantId)->where('type', 'income')->whereMonth('date', now()->month)->sum('amount'),
            'volunteers_count' => Volunteer::where('tenant_id', $tenantId)->count(),
            'total_donors' => NgoDonor::where('tenant_id', $tenantId)->count(),
            'active_campaigns' => Campaign::where('tenant_id', $tenantId)->where('status', 'active')->get(),
            'recent_grants' => NgoGrant::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->limit(3)->get(),
            'ai_insight' => collect($advisor->getInsights())->first()['message'] ?? 'Adicione mais transações para gerar insights precisos.',
            'impactFeed' => $impactFeed
        ];

            return [
                'runway' => number_format($advisor->getSurvivalMetrics()['months_left'], 1),
                'monthly_income' => Transaction::where('tenant_id', $tenantId)->where('type', 'income')->whereMonth('date', now()->month)->sum('amount'),
                'volunteers_count' => Volunteer::where('tenant_id', $tenantId)->count(),
                'total_donors' => NgoDonor::where('tenant_id', $tenantId)->count(),
                'active_campaigns' => Campaign::where('tenant_id', $tenantId)->where('status', 'active')->get(),
                'recent_grants' => NgoGrant::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->limit(3)->get(),
                'ai_insight' => collect($advisor->getInsights())->first()['message'] ?? 'Adicione mais transações para gerar insights precisos.',
            ];
        });

        // Impact feed not cached (real-time)
        $impactFeed = collect();
        $upcomingGrants = NgoGrant::where('tenant_id', $tenantId)
            ->where('deadline', '>=', now())
            ->orderBy('deadline', 'asc')
            ->limit(2)
            ->get();

        foreach($upcomingGrants as $grant) {
            $impactFeed->push([
                'icon' => 'fa-file-signature',
                'color' => '#f59e0b',
                'title' => 'Prazo de Edital: ' . $grant->title,
                'time' => 'Vence ' . \Carbon\Carbon::parse($grant->deadline)->diffForHumans()
            ]);
        }

        $recentDonations = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get();

        foreach($recentDonations as $donation) {
            $impactFeed->push([
                'icon' => 'fa-heart',
                'color' => '#ef4444',
                'title' => 'Nova Doação Recebida: R$ ' . number_format((float) $donation->amount, 0),
                'time' => \Carbon\Carbon::parse($donation->date)->diffForHumans()
            ]);
        }

        $stats['impactFeed'] = $impactFeed;

        return view('dashboards.ngo', compact('stats'));
    }

    private function commonDashboard($tenantId)
    {
        // Totais de todo o período
        $totalIncome = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->sum('amount');

        $balance = $totalIncome - $totalExpense;

        $recentTransactions = Transaction::where('tenant_id', $tenantId)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        $userId = Auth::id();
        $pendingTasks = Task::where('tenant_id', $tenantId)
            ->where('assigned_to', $userId)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // Feed de Impacto Pessoa Comum: Insights e Pagamentos
        $impactFeed = collect();
        
        // Alerta de custo fixo / tarefa financeira
        foreach($pendingTasks as $task) {
            if (str_contains(strtolower($task->title), 'pagar') || str_contains(strtolower($task->title), 'conta')) {
                $impactFeed->push([
                    'icon' => 'fa-receipt',
                    'color' => '#6366f1',
                    'title' => 'Lembrete de Pagamento: ' . $task->title,
                    'time' => 'Para ' . \Carbon\Carbon::parse($task->due_date)->format('d/m')
                ]);
            }
        }

        // Marco de saldo positivo
        if ($balance > 1000) {
            $impactFeed->push([
                'icon' => 'fa-trophy',
                'color' => '#10b981',
                'title' => 'Meta de Reserva: Saldo acima de R$ 1.000',
                'time' => 'Hoje'
            ]);
        }

        // 6 Meses de Histórico para o Gráfico
        $chartLabels = [];
        $chartIncome = [];
        $chartExpense = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthNum = $date->month;
            $year = $date->year;

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

        return view('dashboards.common', compact('totalIncome', 'totalExpense', 'balance', 'recentTransactions', 'pendingTasks', 'chartLabels', 'chartIncome', 'chartExpense', 'impactFeed'));
    }
}
