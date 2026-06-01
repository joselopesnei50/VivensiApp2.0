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
        $year = $request->input('year', date('Y'));

        [$incomes, $totalIncome, $expenses, $totalExpense] = $this->buildDreData($tenant_id, $year);

        $result    = $totalIncome - $totalExpense;
        $chartData = $this->getMonthlyResult($tenant_id, $year);

        return view('ngo.reports.dre', compact('year', 'incomes', 'totalIncome', 'expenses', 'totalExpense', 'result', 'chartData'));
    }

    public function drePdf(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year     = (int) $request->input('year', date('Y'));

        AuditDownload::log('Report:DRE', null, ['format' => 'pdf', 'year' => $year]);

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'dre_pdf',
            'params'    => [
                'tenant_id' => $tenantId,
                'year'      => $year,
                'org_name'  => ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL',
            ],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function exportDreCsv(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $year = (int) $request->input('year', date('Y'));

        AuditDownload::log('Report:DRE', null, [
            'format' => 'csv',
            'year' => $year,
        ]);

        [$incomes, $totalIncome, $expenses, $totalExpense] = $this->buildDreData($tenant_id, $year);

        $result    = $totalIncome - $totalExpense;
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

    /**
     * Build DRE arrays using a single GROUP BY query instead of one query per category.
     * Returns [$incomes, $totalIncome, $expenses, $totalExpense].
     */
    private function buildDreData($tenant_id, $year): array
    {
        // 1 query: all paid sums for the year, keyed by category_id
        $sumsByCat = Transaction::where('tenant_id', $tenant_id)
            ->whereYear('date', $year)
            ->where('status', 'paid')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $incomeCategories = FinancialCategory::where('type', 'income')->get();
        $incomes      = [];
        $totalIncome  = 0;
        foreach ($incomeCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) {
                $incomes[] = ['name' => $cat->name, 'value' => $val];
                $totalIncome += $val;
            }
        }

        $expenseCategories = FinancialCategory::where('type', 'expense')->get();
        $expenses     = [];
        $totalExpense = 0;
        foreach ($expenseCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) {
                $expenses[] = ['name' => $cat->name, 'value' => $val];
                $totalExpense += $val;
            }
        }

        return [$incomes, $totalIncome, $expenses, $totalExpense];
    }

    /**
     * Monthly income vs expense result using a single GROUP BY instead of 24 queries.
     */
    private function getMonthlyResult($tenant_id, $year): array
    {
        $rows = Transaction::where('tenant_id', $tenant_id)
            ->whereYear('date', $year)
            ->where('status', 'paid')
            ->selectRaw('MONTH(date) as month, type, SUM(amount) as total')
            ->groupBy(DB::raw('MONTH(date)'), 'type')
            ->get();

        $byMonthType = [];
        foreach ($rows as $row) {
            $byMonthType[(int) $row->month][$row->type] = (float) $row->total;
        }

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = ($byMonthType[$m]['income'] ?? 0) - ($byMonthType[$m]['expense'] ?? 0);
        }
        return $data;
    }
}
