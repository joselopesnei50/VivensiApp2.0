<?php

namespace App\Services\Reports;

use App\Models\FinancialCategory;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Números da DRE (tela, CSV e PDF usam o mesmo cálculo).
 * Lançamentos sem categoria entram na linha "Sem categoria".
 */
class DreCalculator
{
    /** @return array{0: array, 1: float, 2: array, 3: float} [receitas, total, despesas, total] */
    public function build(int $tenant_id, string $from, string $to, ?int $categoryId = null): array
    {
        $q = Transaction::where('tenant_id', $tenant_id)
            ->whereBetween('date', [$from, $to])
            ->where('status', 'paid')
            ->select('category_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id', 'type');

        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }

        $rows = $q->get();
        $sumsByCat = [];
        foreach ($rows->whereNotNull('category_id') as $r) {
            $sumsByCat[$r->category_id] = ($sumsByCat[$r->category_id] ?? 0) + (float) $r->total;
        }
        // Lançamentos sem categoria também entram no resultado (antes ficavam de fora
        // e a DRE mostrava R$ 0,00 com o gráfico mensal cheio).
        $semCategoria = ['income' => 0.0, 'expense' => 0.0];
        foreach ($rows->whereNull('category_id') as $r) {
            if (isset($semCategoria[$r->type])) {
                $semCategoria[$r->type] += (float) $r->total;
            }
        }

        $incomeCategories = FinancialCategory::where('tenant_id', $tenant_id)->where('type', 'income')->get();
        $incomes      = [];
        $totalIncome  = 0;
        foreach ($incomeCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) {
                $incomes[] = ['name' => $cat->name, 'value' => $val];
                $totalIncome += $val;
            }
        }

        if ($semCategoria['income'] > 0) {
            $incomes[] = ['name' => 'Sem categoria', 'value' => $semCategoria['income']];
            $totalIncome += $semCategoria['income'];
        }

        $expenseCategories = FinancialCategory::where('tenant_id', $tenant_id)->where('type', 'expense')->get();
        $expenses     = [];
        $totalExpense = 0;
        foreach ($expenseCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) {
                $expenses[] = ['name' => $cat->name, 'value' => $val];
                $totalExpense += $val;
            }
        }

        if ($semCategoria['expense'] > 0) {
            $expenses[] = ['name' => 'Sem categoria', 'value' => $semCategoria['expense']];
            $totalExpense += $semCategoria['expense'];
        }

        return [$incomes, $totalIncome, $expenses, $totalExpense];
    }
}
