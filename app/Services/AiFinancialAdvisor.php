<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AiFinancialAdvisor
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getSurvivalMetrics()
    {
        // 1. Current Balance
        $income = Transaction::where('tenant_id', $this->tenantId)
                             ->where('status', 'paid')
                             ->where('type', 'income')
                             ->sum('amount');
        
        $expense = Transaction::where('tenant_id', $this->tenantId)
                              ->where('status', 'paid')
                              ->where('type', 'expense')
                              ->sum('amount');

        $balance = $income - $expense;

        // 2. Average Monthly Burn (Last 6 months for better trend)
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $totalBurn = Transaction::where('tenant_id', $this->tenantId)
                                ->where('status', 'paid')
                                ->where('type', 'expense')
                                ->where('date', '>=', $sixMonthsAgo)
                                ->sum('amount');

        $avgMonthlyBurn = $totalBurn > 0 ? $totalBurn / 6 : 0;

        // 3. Runway
        $monthsLeft = $avgMonthlyBurn > 0 ? $balance / $avgMonthlyBurn : 99; // 99 as infinity

        // 4. Burn Trend (Comparison between last 2 months)
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $prevMonth = Carbon::now()->subMonths(2)->startOfMonth();

        $lastMonthBurn = Transaction::where('tenant_id', $this->tenantId)
                                    ->where('type', 'expense')
                                    ->whereMonth('date', $lastMonth->month)
                                    ->sum('amount');
        
        $prevMonthBurn = Transaction::where('tenant_id', $this->tenantId)
                                    ->where('type', 'expense')
                                    ->whereMonth('date', $prevMonth->month)
                                    ->sum('amount');

        return [
            'balance' => $balance,
            'avg_monthly_burn' => $avgMonthlyBurn,
            'months_left' => $monthsLeft,
            'burn_trend' => $prevMonthBurn > 0 ? (($lastMonthBurn - $prevMonthBurn) / $prevMonthBurn) * 100 : 0,
            'last_month_burn' => $lastMonthBurn
        ];
    }

    public function getInsights()
    {
        $insights = [];
        $metrics = $this->getSurvivalMetrics();

        // Insight 1: Runway Check
        if ($metrics['months_left'] < 3 && $metrics['avg_monthly_burn'] > 0) {
            $insights[] = [
                'type' => 'critical',
                'icon' => 'fa-exclamation-triangle',
                'title' => 'Alerta de Sobrevivência',
                'message' => 'Seu caixa cobre apenas ' . number_format($metrics['months_left'], 1) . ' meses de operação. Recomenda-se reduzir custos fixos imediatamente.'
            ];
        } elseif ($metrics['months_left'] >= 99) {
             $insights[] = [
                'type' => 'success',
                'icon' => 'fa-infinity',
                'title' => 'Sustentabilidade Plena',
                'message' => 'Sua operação é altamente sustentável. Suas receitas cobrem seus custos sem consumir reservas.'
            ];
        }

        // Insight 2: Burn Trend
        if ($metrics['burn_trend'] > 15) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'fa-chart-line',
                'title' => 'Aumento de Custos',
                'message' => 'Seus gastos aumentaram ' . number_format($metrics['burn_trend'], 0) . '% no último mês. Verifique se isso é sazonal ou recorrente.'
            ];
        }

        // Insight 3: Opportunity - based on category (fail-safe if migrations not ready)
        try {
            if (Schema::hasTable('financial_categories') && Schema::hasColumn('transactions', 'category_id')) {
                $mktExpense = Transaction::where('tenant_id', $this->tenantId)
                    ->where('category_id', function ($q) {
                        $q->select('id')
                            ->from('financial_categories')
                            ->where('name', 'LIKE', '%Marketing%')
                            ->limit(1);
                    })
                    ->sum('amount');

                if ($mktExpense > 0 && ($metrics['months_left'] ?? 0) > 6) {
                    $insights[] = [
                        'type' => 'info',
                        'icon' => 'fa-rocket',
                        'title' => 'Potencial de Escala',
                        'message' => 'Com 6+ meses de runway, você tem margem para aumentar seu investimento em Marketing e acelerar o crescimento.'
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore category-based insight if DB schema isn't ready.
        }

        return $insights;
    }

    public function getPredictiveData()
    {
        $metrics = $this->getSurvivalMetrics();
        $balance = $metrics['balance'];
        $burn = $metrics['avg_monthly_burn'];
        
        $labels = [];
        $values = [];
        
        for ($i = 0; $i <= 6; $i++) {
            $labels[] = Carbon::now()->addMonths($i)->format('M/y');
            $projected = $balance - ($burn * $i);
            $values[] = max(0, $projected);
        }
        
        return [
            'labels' => $labels,
            'values' => $values
        ];
    }
}
