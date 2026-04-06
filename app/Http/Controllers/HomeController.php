<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Transaction;
use App\Services\AiFinancialAdvisor;

class HomeController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $role = auth()->user()->role;

        // 1. Financial Stats (Common to all)
        $advisor = new AiFinancialAdvisor($tenantId);
        $metrics = $advisor->getSurvivalMetrics(); // uses Transactions sum
        
        $balance = $metrics['balance'];
        
        // 2. Role Specific Stats
        $stats = [];

        if ($role == 'manager') {
            // --- Real Manager Data ---
            
            // 1. Runway (Calculated same as NGO for consistency)
            $avgExpansion = Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')
                ->where('date', '>=', now()->subMonths(3))
                ->sum('amount') / 3;
            $stats['runway'] = ($avgExpansion > 0) ? round($balance / $avgExpansion, 1) : '> 12';

            // 2. Active Projects
            $stats['active_projects'] = Project::where('tenant_id', $tenantId)->where('status', 'active')->count();

            // 3. Backlog (Tasks Pending/In Progress)
            $stats['backlog_count'] = \App\Models\Task::whereHas('project', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->whereIn('status', ['pending', 'in_progress'])->count();

            // 4. Critical Risks (Overdue Tasks)
            $stats['critical_risks'] = \App\Models\Task::whereHas('project', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->where('due_date', '<', now())
              ->where('status', '!=', 'completed')
              ->count();
            
            // 5. Team Performance (Top 5 users by completed tasks)
            // We need a raw query or collection logic if User-Task relation isn't direct, assuming Task has assigned_to
            $teamPerformance = \App\Models\User::where('tenant_id', $tenantId)
                ->withCount(['tasks as completed_tasks' => function($q) {
                    $q->where('status', 'completed');
                }])
                ->orderBy('completed_tasks', 'desc')
                ->take(5)
                ->get();
            $stats['team_performance'] = $teamPerformance;

            // 6. Chart Data (Monthly Cash Flow)
            $monthlyStats = Transaction::where('tenant_id', $tenantId)
                ->whereYear('date', now()->year)
                ->selectRaw('MONTH(date) as month, type, SUM(amount) as total')
                ->groupBy('month', 'type')
                ->get();

            $chartData = [
                'income' => array_fill(0, 12, 0),
                'expense' => array_fill(0, 12, 0)
            ];

            foreach ($monthlyStats as $stat) {
                // Adjust for 0-indexed array (Jan = 0)
                $idx = $stat->month - 1; 
                if (isset($chartData[$stat->type][$idx])) {
                    $chartData[$stat->type][$idx] = (float)$stat->total;
                }
            }
            $stats['chart_data'] = $chartData;

        } elseif ($role == 'ngo') {
            // --- Real NGO Data ---
            
            // 1. Volunteer Force
            $stats['volunteers_count'] = \App\Models\Volunteer::where('tenant_id', $tenantId)->count();
            
            // 2. Monthly Income (Arrecadação Mês Atual)
            $stats['monthly_income'] = Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount');

            // 3. Runway Calculation (Caixa / Média Despesas 3 meses)
            $avgExpansion = Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')
                ->where('date', '>=', now()->subMonths(3))
                ->sum('amount') / 3;
            
            $stats['runway'] = ($avgExpansion > 0) ? round($balance / $avgExpansion, 1) : '> 12';

            // 4. Reach (Total Donors)
            $stats['total_donors'] = \App\Models\NgoDonor::where('tenant_id', $tenantId)->count();

            // 5. Active Campaigns (Limit 3)
            $stats['active_campaigns'] = \App\Models\Campaign::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();
            
            // 6. Recent Grants (Editais)
            $stats['recent_grants'] = \App\Models\NgoGrant::where('tenant_id', $tenantId)
                ->orderBy('deadline', 'asc') // Most urgent first
                ->take(4)
                ->get();
        }

        // 3. Recent Activity (Feed)
        $recentTransactions = Transaction::where('tenant_id', $tenantId)
                                         ->orderBy('date', 'desc')
                                         ->limit(5)
                                         ->get();

        return view('dashboard', compact('balance', 'stats', 'recentTransactions', 'role'));
    }
}
