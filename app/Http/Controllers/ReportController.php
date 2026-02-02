<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function dre(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $year = $request->input('year', date('Y'));
        
        // 1. Receitas Operacionais Brutas
        $incomeCategories = FinancialCategory::where('type', 'income')->get();
        $incomes = [];
        $totalIncome = 0;

        foreach ($incomeCategories as $cat) {
            $val = Transaction::where('tenant_id', $tenant_id)
                ->where('category_id', $cat->id)
                ->whereYear('date', $year)
                ->where('status', 'paid')
                ->sum('amount');
            
            if ($val > 0) {
                $incomes[] = ['name' => $cat->name, 'value' => $val];
                $totalIncome += $val;
            }
        }

        // 2. Custos e Despesas Operacionais
        $expenseCategories = FinancialCategory::where('type', 'expense')->get();
        $expenses = [];
        $totalExpense = 0;
        
        // Agrupar despesas por grupos macro (opcional, aqui simplificado por categoria)
        foreach ($expenseCategories as $cat) {
            $val = Transaction::where('tenant_id', $tenant_id)
                ->where('category_id', $cat->id)
                ->whereYear('date', $year)
                ->where('status', 'paid')
                ->sum('amount');

            if ($val > 0) {
                $expenses[] = ['name' => $cat->name, 'value' => $val];
                $totalExpense += $val;
            }
        }

        // 3. Resultado do Exercício
        $result = $totalIncome - $totalExpense;

        // Dados mensais para gráfico (opcional)
        $chartData = $this->getMonthlyResult($tenant_id, $year);

        return view('ngo.reports.dre', compact('year', 'incomes', 'totalIncome', 'expenses', 'totalExpense', 'result', 'chartData'));
    }

    private function getMonthlyResult($tenant_id, $year)
    {
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $inc = Transaction::where('tenant_id', $tenant_id)
                ->whereYear('date', $year)
                ->whereMonth('date', $m)
                ->where('type', 'income')
                ->where('status', 'paid')
                ->sum('amount');
                
            $exp = Transaction::where('tenant_id', $tenant_id)
                ->whereYear('date', $year)
                ->whereMonth('date', $m)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->sum('amount');

            $data[$m] = $inc - $exp;
        }
        return $data;
    }
}
