<?php

namespace App\Http\Controllers;

use App\Models\BudgetTarget;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AuditDownload;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $tenant_id = auth()->user()->tenant_id;

        // Metas (Planejado)
        $targets = BudgetTarget::where('tenant_id', $tenant_id)
                                ->where('year', $year)
                                ->get();

        // Realizado (Agrupado por categoria do ano selecionado)
        $realized = Transaction::where('tenant_id', $tenant_id)
                                ->whereYear('date', $year)
                                ->where('status', 'paid')
                                ->select('category_id', 'type', DB::raw('SUM(amount) as total'))
                                ->groupBy('category_id', 'type')
                                ->get();

        // Categorias para o formulário de metas
        $incomeCategories = FinancialCategory::where('type', 'income')->get();
        $expenseCategories = FinancialCategory::where('type', 'expense')->get();

        return view('ngo.budget.index', compact('targets', 'realized', 'year', 'incomeCategories', 'expenseCategories'));
    }

    public function exportCsv(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $tenantId = auth()->user()->tenant_id;

        AuditDownload::log('Budget', null, [
            'format' => 'csv',
            'year' => $year,
        ]);

        $targets = BudgetTarget::where('tenant_id', $tenantId)
            ->where('year', $year)
            ->get();

        $realized = Transaction::where('tenant_id', $tenantId)
            ->whereYear('date', $year)
            ->where('status', 'paid')
            ->select('category_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id', 'type')
            ->get();

        $categories = FinancialCategory::orderBy('type')->orderBy('name')->get()->keyBy('id');

        $filename = 'orcamento-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($year, $targets, $realized, $categories) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ano', 'Tipo', 'Categoria', 'Planejado', 'Realizado', 'Variação', '% Execução']);

            // Build rows from union of planned + realized
            $pairs = collect();
            foreach ($targets as $t) {
                $pairs->push([$t->category_id, $t->type]);
            }
            foreach ($realized as $r) {
                $pairs->push([$r->category_id, $r->type]);
            }
            $pairs = $pairs->unique(fn($p) => ($p[0] . '|' . $p[1]))->values();

            foreach ($pairs as $p) {
                [$catId, $type] = $p;
                $cat = $categories->get($catId);
                $name = $cat->name ?? ('Categoria #' . $catId);

                $planned = (float) optional($targets->where('category_id', $catId)->where('type', $type)->first())->amount ?? 0;
                $done = (float) optional($realized->where('category_id', $catId)->where('type', $type)->first())->total ?? 0;
                $var = $done - $planned;
                $pct = $planned > 0 ? ($done / $planned) * 100 : 0;

                // Only export meaningful rows
                if ($planned == 0.0 && $done == 0.0) continue;

                fputcsv($out, [
                    $year,
                    $type,
                    $name,
                    number_format($planned, 2, ',', '.'),
                    number_format($done, 2, ',', '.'),
                    number_format($var, 2, ',', '.'),
                    number_format($pct, 2, ',', '.') . '%',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $tenant_id = auth()->user()->tenant_id;

        AuditDownload::log('Budget', null, [
            'format' => 'pdf',
            'year' => $year,
        ]);

        $targets = BudgetTarget::where('tenant_id', $tenant_id)
            ->where('year', $year)
            ->get();

        $realized = Transaction::where('tenant_id', $tenant_id)
            ->whereYear('date', $year)
            ->where('status', 'paid')
            ->select('category_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id', 'type')
            ->get();

        $incomeCategories = FinancialCategory::where('type', 'income')->orderBy('name')->get();
        $expenseCategories = FinancialCategory::where('type', 'expense')->orderBy('name')->get();

        $plannedIncome = (float) $targets->where('type', 'income')->sum('amount');
        $plannedExpense = (float) $targets->where('type', 'expense')->sum('amount');
        $realIncome = (float) $realized->where('type', 'income')->sum('total');
        $realExpense = (float) $realized->where('type', 'expense')->sum('total');
        $plannedResult = $plannedIncome - $plannedExpense;
        $realResult = $realIncome - $realExpense;

        $orgName = (auth()->user()->tenant_id == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.budget.pdf', compact(
            'year',
            'targets',
            'realized',
            'incomeCategories',
            'expenseCategories',
            'plannedIncome',
            'plannedExpense',
            'realIncome',
            'realExpense',
            'plannedResult',
            'realResult',
            'orgName',
            'generatedAt'
        ));

        $filename = 'orcamento-' . $year . '-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function store(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $items = $request->get('targets', []); 
        $year = $request->get('year', date('Y'));

        foreach ($items as $catId => $amount) {
            if ($amount === null) continue;

            $category = FinancialCategory::find($catId);
            if (!$category) continue;

            BudgetTarget::updateOrCreate(
                [
                    'tenant_id' => $tenant_id,
                    'category_id' => $catId,
                    'year' => $year,
                    'type' => $category->type
                ],
                ['amount' => $amount]
            );
        }

        return redirect()->back()->with('success', 'Orçamento atualizado com sucesso!');
    }
}
