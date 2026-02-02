<?php

namespace App\Http\Controllers;

use App\Models\BudgetTarget;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
