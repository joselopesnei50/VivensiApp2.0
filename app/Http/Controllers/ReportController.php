<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfJob;
use App\Models\GeneratedReport;
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
        $year      = (int) $request->input('year', date('Y'));
        $from      = $request->input('from', "$year-01-01");
        $to        = $request->input('to',   "$year-12-31");
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;

        try {
            $from = Carbon::parse($from)->toDateString();
            $to   = Carbon::parse($to)->toDateString();
        } catch (\Exception) {
            $from = "$year-01-01";
            $to   = "$year-12-31";
        }
        if ($to < $from) $to = $from;

        $categories = FinancialCategory::where('tenant_id', $tenant_id)->orderBy('type')->orderBy('name')->get();

        [$incomes, $totalIncome, $expenses, $totalExpense] = $this->buildDreData($tenant_id, $from, $to, $categoryId);

        $result    = $totalIncome - $totalExpense;
        $chartData = $this->getMonthlyResult($tenant_id, $from, $to);

        return view('ngo.reports.dre', compact(
            'year', 'from', 'to', 'categoryId', 'categories',
            'incomes', 'totalIncome', 'expenses', 'totalExpense', 'result', 'chartData'
        ));
    }

    public function drePdf(Request $request)
    {
        $tenantId   = auth()->user()->tenant_id;
        $year       = (int) $request->input('year', date('Y'));
        $from       = $request->input('from', "$year-01-01");
        $to         = $request->input('to',   "$year-12-31");
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;

        AuditDownload::log('Report:DRE', null, ['format' => 'pdf', 'year' => $year, 'from' => $from, 'to' => $to]);

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'dre_pdf',
            'params'    => [
                'tenant_id'   => $tenantId,
                'year'        => $year,
                'from'        => $from,
                'to'          => $to,
                'category_id' => $categoryId,
                'org_name'    => auth()->user()->tenant?->documentName() ?? 'ORGANIZAÇÃO SOCIAL',
            ],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function exportDreCsv(Request $request)
    {
        $tenant_id  = auth()->user()->tenant_id;
        $year       = (int) $request->input('year', date('Y'));
        $from       = $request->input('from', "$year-01-01");
        $to         = $request->input('to',   "$year-12-31");
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;

        AuditDownload::log('Report:DRE', null, ['format' => 'csv', 'year' => $year, 'from' => $from, 'to' => $to]);

        [$incomes, $totalIncome, $expenses, $totalExpense] = $this->buildDreData($tenant_id, $from, $to, $categoryId);

        $result    = $totalIncome - $totalExpense;
        $chartData = $this->getMonthlyResult($tenant_id, $from, $to);

        $filename = 'dre-' . $year . '-' . date('Y-m-d_His') . '.csv';

        $periodLabel = Carbon::parse($from)->format('d/m/Y') . ' a ' . Carbon::parse($to)->format('d/m/Y');

        return response()->streamDownload(function () use ($year, $periodLabel, $incomes, $totalIncome, $expenses, $totalExpense, $result, $chartData) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['DRE', 'Período', $periodLabel]);
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

    private function buildDreData(int $tenant_id, string $from, string $to, ?int $categoryId = null): array
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

    private function getMonthlyResult(int $tenant_id, string $from, string $to): array
    {
        $rows = Transaction::where('tenant_id', $tenant_id)
            ->whereBetween('date', [$from, $to])
            ->where('status', 'paid')
            ->selectRaw('date, type, SUM(amount) as total')
            ->groupBy('date', 'type')
            ->get();

        // Mês calculado em PHP (MONTH() não existe no SQLite dos testes).
        $byMonthType = [];
        foreach ($rows as $row) {
            $m = (int) Carbon::parse($row->date)->month;
            $byMonthType[$m][$row->type] = ($byMonthType[$m][$row->type] ?? 0) + (float) $row->total;
        }

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = ($byMonthType[$m]['income'] ?? 0) - ($byMonthType[$m]['expense'] ?? 0);
        }
        return $data;
    }
}
