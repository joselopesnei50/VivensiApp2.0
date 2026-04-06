<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\AuditDownload;

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

    public function drePdf(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $year = (int) $request->input('year', date('Y'));

        AuditDownload::log('Report:DRE', null, [
            'format' => 'pdf',
            'year' => $year,
        ]);

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

        $expenseCategories = FinancialCategory::where('type', 'expense')->get();
        $expenses = [];
        $totalExpense = 0;

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

        $result = $totalIncome - $totalExpense;

        $orgName = (auth()->user()->tenant_id == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $periodLabel = '01/01/' . $year . ' a 31/12/' . $year;
        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.reports.dre_pdf', compact(
            'year',
            'incomes',
            'totalIncome',
            'expenses',
            'totalExpense',
            'result',
            'orgName',
            'periodLabel',
            'generatedAt'
        ));

        $filename = 'dre-' . $year . '-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportDreCsv(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $year = (int) $request->input('year', date('Y'));

        AuditDownload::log('Report:DRE', null, [
            'format' => 'csv',
            'year' => $year,
        ]);

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

        $expenseCategories = FinancialCategory::where('type', 'expense')->get();
        $expenses = [];
        $totalExpense = 0;

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

        $result = $totalIncome - $totalExpense;
        $chartData = $this->getMonthlyResult($tenant_id, $year);

        $filename = 'dre-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($year, $incomes, $totalIncome, $expenses, $totalExpense, $result, $chartData) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['DRE', 'Ano', $year]);
            fputcsv($out, []);

            fputcsv($out, ['RECEITAS OPERACIONAIS BRUTAS', '', '']);
            fputcsv($out, ['Categoria', 'Valor', '']);
            foreach ($incomes as $inc) {
                fputcsv($out, [$inc['name'], number_format((float) $inc['value'], 2, ',', '.'), '']);
            }
            fputcsv($out, ['TOTAL RECEITAS', number_format((float) $totalIncome, 2, ',', '.'), '']);
            fputcsv($out, []);

            fputcsv($out, ['CUSTOS E DESPESAS OPERACIONAIS', '', '']);
            fputcsv($out, ['Categoria', 'Valor', '']);
            foreach ($expenses as $exp) {
                fputcsv($out, [$exp['name'], number_format((float) $exp['value'], 2, ',', '.'), '']);
            }
            fputcsv($out, ['TOTAL DESPESAS', number_format((float) $totalExpense, 2, ',', '.'), '']);
            fputcsv($out, []);

            fputcsv($out, ['RESULTADO DO EXERCÍCIO', number_format((float) $result, 2, ',', '.'), '']);
            fputcsv($out, []);

            $labels = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            fputcsv($out, ['RESULTADO MENSAL (jan-dez)', '', '']);
            fputcsv($out, ['Mês', 'Resultado', '']);
            for ($m = 1; $m <= 12; $m++) {
                $v = (float) (($chartData[$m] ?? 0) ?: 0);
                fputcsv($out, [$labels[$m-1] ?? (string)$m, number_format($v, 2, ',', '.'), '']);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
