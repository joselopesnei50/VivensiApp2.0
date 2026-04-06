<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;
use App\Services\DeepSeekService;

class PersonalBudgetController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();
        $year = date('Y');

        $budget = DB::table('personal_budgets')
                    ->where('user_id', $userId)
                    ->where('year', $year)
                    ->first();

        $items = [];
        if ($budget) {
            $items = DB::table('personal_budget_items')
                        ->where('personal_budget_id', $budget->id)
                        ->pluck('planned_amount', 'category_name')
                        ->toArray();
        }

        // Default categories if none exist
        $defaultCategories = ['Moradia', 'Transporte', 'Lazer', 'Cultura', 'Filhos', 'Saúde', 'Educação', 'Alimentação', 'Reservas'];

        return view('dashboards.common.budget', compact('budget', 'year', 'items', 'defaultCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'target_income' => 'required|numeric',
            'max_expense' => 'required|numeric',
            'categories' => 'array'
        ]);

        DB::transaction(function() use ($request) {
            $budgetId = DB::table('personal_budgets')->updateOrInsert(
                ['user_id' => auth()->id(), 'year' => $request->year],
                [
                    'tenant_id' => auth()->user()->tenant_id,
                    'target_income' => $request->target_income,
                    'max_expense' => $request->max_expense,
                    'notes' => $request->notes,
                    'updated_at' => now(),
                ]
            );

            // Get the ID (updateOrInsert doesn't return ID if updated)
            $budget = DB::table('personal_budgets')
                        ->where('user_id', auth()->id())
                        ->where('year', $request->year)
                        ->first();

            if ($request->has('categories')) {
                foreach ($request->categories as $name => $amount) {
                    DB::table('personal_budget_items')->updateOrInsert(
                        ['personal_budget_id' => $budget->id, 'category_name' => $name],
                        ['planned_amount' => $amount ?: 0, 'updated_at' => now()]
                    );
                }
            }
        });

        return back()->with('success', 'Planejamento detalhado atualizado com sucesso!');
    }

    public function getAiTips(DeepSeekService $deepSeek)
    {
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();
        $year = date('Y');
        
        // 1. Get Monthly Totals
        $incomeMonth = (float) DB::table('transactions')
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'income')
                    ->whereMonth('date', now()->month)
                    ->sum('amount');

        $expenseMonth = (float) DB::table('transactions')
                     ->where('tenant_id', $tenantId)
                     ->where('type', 'expense')
                     ->whereMonth('date', now()->month)
                     ->sum('amount');

        // 2. Get Annual Budget Info
        $budget = DB::table('personal_budgets')
                    ->where('user_id', $userId)
                    ->where('year', $year)
                    ->first();

        $budgetItems = collect();
        if ($budget) {
            $budgetItems = DB::table('personal_budget_items')
                            ->where('personal_budget_id', $budget->id)
                            ->get();
        }

        // 3. Get Spending by Category (Real vs Planned)
        $categoryReport = "";
        if ($budgetItems->isNotEmpty()) {
            $categoryReport = "\nComparativo de categorias (Mês Atual):\n";
            foreach ($budgetItems as $item) {
                // Tentativa de buscar gastos reais para essa categoria (usando LIKE para flexibilidade)
                $realSpent = (float) DB::table('transactions')
                    ->join('financial_categories', 'transactions.category_id', '=', 'financial_categories.id')
                    ->where('transactions.tenant_id', $tenantId)
                    ->where('transactions.type', 'expense')
                    ->whereMonth('transactions.date', now()->month)
                    ->where('financial_categories.name', 'LIKE', '%' . $item->category_name . '%')
                    ->sum('transactions.amount');

                $plannedMonthly = $item->planned_amount / 12;
                $percent = ($plannedMonthly > 0) ? ($realSpent / $plannedMonthly) * 100 : 0;
                
                $categoryReport .= "- {$item->category_name}: Gasto R$ ".number_format($realSpent, 2). " de R$ ".number_format($plannedMonthly, 2)." planejado (".round($percent, 1)."%)\n";
            }
        }

        $maxExpense = $budget->max_expense ?? 0;
        $statusGastos = ($maxExpense > 0 && $expenseMonth > ($maxExpense / 12)) ? "ACIMA DO LIMITE" : "DENTRO DO LIMITE";

        $prompt = "Aja como um Personal Finance Coach de Elite. 
        DADOS REAIS:
        - Saldo Mês: R$ ".number_format($incomeMonth - $expenseMonth, 2, ',', '.')."
        - Status Gastos: {$statusGastos}
        {$categoryReport}
        
        REGRAS DE RESPOSTA (CRÍTICO):
        1. Gere APENAS 3 dicas EXTREMAMENTE CURTAS (máximo 15 palavras cada).
        2. Comece cada dica com um EMOJI relevante.
        3. Use tom motivacional e direto ao ponto.
        4. NÃO faça introduções nem conclusões. 
        5. Formate exatamente assim: EMOJI | Título curto: Texto rápido.";

        $messages = [
            ['role' => 'system', 'content' => 'Você é o motor de IA do Vivensi. Você fala de forma ultra-concisa, moderna e visual.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $deepSeek->chat($messages);

        if (isset($response['error'])) {
            \Log::error("DeepSeek Error: " . $response['error']);
            return response()->json(['error' => $response['error']]);
        }

        $tips = $response['choices'][0]['message']['content'] ?? 'Sem dados suficientes para análise.';

        return response()->json(['tips' => $tips]);
    }
}
