<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DRE Service — agregacao financeira pra painel PJ pequena empresa.
 *
 * Nao inclui deducoes/tributos por ora (Vivensi nao coleta esses dados
 * dos lancamentos). Vira Onda 3 quando integrar com contador.
 */
class DreService
{
    public const PRESETS = [
        'current_month' => 'Mês atual',
        'last_month'    => 'Mês passado',
        'last_3_months' => 'Últimos 3 meses',
        'last_12_months'=> 'Últimos 12 meses',
        'current_year'  => 'Ano atual',
        'last_year'     => 'Ano passado',
    ];

    /**
     * Resolve [inicio, fim] Carbon dado um preset ou par de datas custom.
     */
    public function resolvePeriod(string $preset, ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'last_month'      => [$now->copy()->subMonth()->startOfMonth(),        $now->copy()->subMonth()->endOfMonth()],
            'last_3_months'   => [$now->copy()->subMonths(2)->startOfMonth(),      $now->copy()->endOfMonth()],
            'last_12_months'  => [$now->copy()->subMonths(11)->startOfMonth(),     $now->copy()->endOfMonth()],
            'current_year'    => [$now->copy()->startOfYear(),                     $now->copy()->endOfYear()],
            'last_year'       => [$now->copy()->subYear()->startOfYear(),          $now->copy()->subYear()->endOfYear()],
            'custom'          => [Carbon::parse($from ?: $now->startOfMonth()),    Carbon::parse($to ?: $now->endOfMonth())],
            default           => [$now->copy()->startOfMonth(),                    $now->copy()->endOfMonth()],
        };
    }

    /**
     * Calcula o DRE completo pra um tenant no periodo.
     * Retorna array com receita, despesas por categoria, resultado, margem,
     * comparativo com periodo anterior, top clientes e pendencias.
     */
    public function calculate(int $tenantId, Carbon $from, Carbon $to): array
    {
        $paid = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        $totalIncome  = (float) (clone $paid)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $paid)->where('type', 'expense')->sum('amount');
        $resultado    = $totalIncome - $totalExpense;
        $margem       = $totalIncome > 0 ? ($resultado / $totalIncome * 100) : null;

        // Comparativo: mesmo tamanho de janela imediatamente anterior
        $diffDays  = $from->diffInDays($to) + 1;
        $prevTo    = $from->copy()->subDay();
        $prevFrom  = $prevTo->copy()->subDays($diffDays - 1);

        $prevPaid = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('date', [$prevFrom->toDateString(), $prevTo->toDateString()]);

        $prevIncome  = (float) (clone $prevPaid)->where('type', 'income')->sum('amount');
        $prevExpense = (float) (clone $prevPaid)->where('type', 'expense')->sum('amount');
        $prevResult  = $prevIncome - $prevExpense;

        return [
            'period' => [
                'from'      => $from->copy(),
                'to'        => $to->copy(),
                'prev_from' => $prevFrom,
                'prev_to'   => $prevTo,
            ],
            'kpis' => [
                'income'        => $totalIncome,
                'expense'       => $totalExpense,
                'result'        => $resultado,
                'margin'        => $margem,
                'income_delta'  => $this->pctDelta($totalIncome, $prevIncome),
                'expense_delta' => $this->pctDelta($totalExpense, $prevExpense),
                'result_delta'  => $this->pctDelta($resultado, $prevResult),
                'prev_income'   => $prevIncome,
                'prev_expense'  => $prevExpense,
                'prev_result'   => $prevResult,
            ],
            'expenses_by_category' => $this->groupByCategory($tenantId, $from, $to, 'expense'),
            'income_by_category'   => $this->groupByCategory($tenantId, $from, $to, 'income'),
            'top_clients'          => $this->topClients($tenantId, $from, $to),
            'pending'              => $this->pending($tenantId, $from, $to),
            'monthly_trend'        => $this->monthlyTrend($tenantId, $from, $to),
        ];
    }

    private function pctDelta(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null; // sem baseline pra dividir
        }
        return (($current - $previous) / abs($previous)) * 100;
    }

    private function groupByCategory(int $tenantId, Carbon $from, Carbon $to, string $type): Collection
    {
        return DB::table('transactions as t')
            ->leftJoin('financial_categories as c', 't.category_id', '=', 'c.id')
            ->where('t.tenant_id', $tenantId)
            ->where('t.type', $type)
            ->where('t.status', 'paid')
            ->whereBetween('t.date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(c.name, "(Sem categoria)") as category, COALESCE(c.color, "#94a3b8") as color, SUM(t.amount) as total, COUNT(*) as tx_count')
            ->groupByRaw('COALESCE(c.name, "(Sem categoria)"), COALESCE(c.color, "#94a3b8")')
            ->orderByDesc('total')
            ->get();
    }

    private function topClients(int $tenantId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('transactions as t')
            ->join('clients as cl', 't.client_id', '=', 'cl.id')
            ->where('t.tenant_id', $tenantId)
            ->where('t.type', 'income')
            ->where('t.status', 'paid')
            ->whereBetween('t.date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('cl.id, cl.name, cl.stage, SUM(t.amount) as total_paid, COUNT(*) as tx_count')
            ->groupBy('cl.id', 'cl.name', 'cl.stage')
            ->orderByDesc('total_paid')
            ->limit(10)
            ->get();
    }

    /**
     * Contas a receber e a pagar (status=pending) — nao filtra por periodo do DRE,
     * mostra sempre o snapshot atual pra ajudar decisao gerencial.
     */
    private function pending(int $tenantId, Carbon $from, Carbon $to): array
    {
        $now = Carbon::now()->toDateString();

        $receivables = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('status', 'pending')
            ->get(['id', 'description', 'amount', 'date', 'client_id']);

        $payables = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->get(['id', 'description', 'amount', 'date']);

        return [
            'receivables_total'         => (float) $receivables->sum('amount'),
            'receivables_overdue_total' => (float) $receivables->filter(fn ($t) => $t->date && $t->date < $now)->sum('amount'),
            'receivables_count'         => $receivables->count(),
            'payables_total'            => (float) $payables->sum('amount'),
            'payables_overdue_total'    => (float) $payables->filter(fn ($t) => $t->date && $t->date < $now)->sum('amount'),
            'payables_count'            => $payables->count(),
        ];
    }

    /**
     * Serie mensal (receita/despesa/resultado) dentro do periodo pro grafico.
     * Se o periodo tem menos de 2 meses, retorna serie diaria.
     */
    private function monthlyTrend(int $tenantId, Carbon $from, Carbon $to): array
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $months   = $from->diffInMonths($to);
        $useDaily = $months < 2;

        if ($useDaily) {
            $bucketExpr = $isSqlite ? "strftime('%Y-%m-%d', date)" : "DATE_FORMAT(date, '%Y-%m-%d')";
            $labelFmt   = 'd/m';
        } else {
            $bucketExpr = $isSqlite ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')";
            $labelFmt   = 'M/Y';
        }

        $rows = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("{$bucketExpr} as bucket, type, SUM(amount) as total")
            ->groupBy('bucket', 'type')
            ->orderBy('bucket')
            ->get();

        $grouped = $rows->groupBy('bucket');
        $labels  = $grouped->keys()->all();

        $income  = [];
        $expense = [];
        $result  = [];
        foreach ($labels as $bucket) {
            $rowsBucket = $grouped[$bucket];
            $in  = (float) ($rowsBucket->firstWhere('type', 'income')?->total ?? 0);
            $ex  = (float) ($rowsBucket->firstWhere('type', 'expense')?->total ?? 0);
            $income[]  = $in;
            $expense[] = $ex;
            $result[]  = $in - $ex;
        }

        // Rotula legivel
        $displayLabels = array_map(function ($bucket) use ($useDaily, $labelFmt) {
            try {
                return Carbon::parse($bucket)->translatedFormat($labelFmt);
            } catch (\Throwable) {
                return $bucket;
            }
        }, $labels);

        return [
            'labels'  => $displayLabels,
            'income'  => $income,
            'expense' => $expense,
            'result'  => $result,
        ];
    }
}
