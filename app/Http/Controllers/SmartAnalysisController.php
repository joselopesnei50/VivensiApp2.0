<?php

namespace App\Http\Controllers;

use App\Services\AiFinancialAdvisor;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Database\QueryException;

class SmartAnalysisController extends Controller
{
    private function buildDeepAnalysisPrompt(array $metrics, $transactions, ?string $tenantType, ?string $role): string
    {
        $balance = (float) ($metrics['balance'] ?? 0);
        $avgBurn = (float) ($metrics['avg_monthly_burn'] ?? 0);
        $monthsLeft = (float) ($metrics['months_left'] ?? 0);
        $txJson = json_encode($transactions);

        $isNgoContext = ($tenantType === 'ngo') || ($role === 'ngo');

        if ($isNgoContext) {
            return "ATUAÇÃO/CONTEXTO (obrigatório)\n"
                . "- Você é um(a) consultor(a) financeiro(a) sênior especializado(a) em **Terceiro Setor (ONG/OSC)**.\n"
                . "- Você está analisando exclusivamente o **painel ONG** (mesmo que o usuário seja gestor).\n"
                . "- **Proibido** mencionar: SaaS, pré-venda, rodada seed/anjo, aceleração, MRR/ARR, churn ou qualquer estratégia típica de software como serviço.\n"
                . "- Use linguagem de ONG: doadores, campanhas, editais, patrocínios, convênios, prestação de contas, transparência, custeio vs. projeto.\n\n"
                . "REGRAS DE RESPOSTA (obrigatório)\n"
                . "- Responda **sem saudação** (não comece com 'Prezados').\n"
                . "- Não assine/encerre com 'Atenciosamente' ou cargo.\n"
                . "- Se os dados forem insuficientes (ex.: burn=0, poucas transações), diga isso de forma objetiva e proponha próximos passos de coleta/organização.\n"
                . "- Use Markdown e seja direto.\n\n"
                . "DADOS (tenant atual)\n"
                . "- Saldo em Caixa: R$ " . number_format($balance, 2, ',', '.') . "\n"
                . "- Queima Mensal Média (despesas pagas, últimos 6 meses): R$ " . number_format($avgBurn, 2, ',', '.') . "\n"
                . "- Autonomia (runway): " . number_format($monthsLeft, 1, ',', '.') . " meses\n"
                . "- Últimas transações pagas (mais recente primeiro): " . $txJson . "\n\n"
                . "ENTREGA\n"
                . "1) **Diagnóstico em 3-5 linhas** (contextualize os números; se houver datas futuras, aponte como inconsistência de cadastro).\n"
                . "2) **3 Recomendações estratégicas curtas e acionáveis** (cada uma com: Ação, Por quê, Como medir em 30 dias).\n"
                . "3) **Riscos imediatos** (até 3 bullets).\n"
                . "4) **Próximos passos no painel** (até 5 bullets do que registrar/organizar para a próxima análise).\n"
                . "Idioma: Português (Brasil).";
        }

        // Default: neutral financial advisor prompt (still forbids talking about SaaS itself).
        $persona = ($role === 'manager') ? 'consultor(a) financeiro(a) sênior para uma organização com projetos' : 'consultor(a) financeiro(a) sênior';

        return "ATUAÇÃO/CONTEXTO (obrigatório)\n"
            . "- Você é um(a) {$persona}.\n"
            . "- Analise apenas os dados financeiros do tenant atual.\n"
            . "- **Proibido** mencionar: SaaS, pré-venda, rodada seed/anjo, aceleração, MRR/ARR, churn.\n\n"
            . "REGRAS DE RESPOSTA (obrigatório)\n"
            . "- Responda sem saudação e sem assinatura.\n"
            . "- Se os dados forem insuficientes, diga isso e proponha próximos passos.\n"
            . "- Use Markdown.\n\n"
            . "DADOS (tenant atual)\n"
            . "- Saldo em Caixa: R$ " . number_format($balance, 2, ',', '.') . "\n"
            . "- Despesas médias mensais (pagas, últimos 6 meses): R$ " . number_format($avgBurn, 2, ',', '.') . "\n"
            . "- Autonomia (runway): " . number_format($monthsLeft, 1, ',', '.') . " meses\n"
            . "- Últimas transações pagas (mais recente primeiro): " . $txJson . "\n\n"
            . "ENTREGA\n"
            . "1) Diagnóstico (3-5 linhas)\n"
            . "2) 3 Recomendações (Ação, Por quê, Como medir em 30 dias)\n"
            . "3) Riscos imediatos (até 3 bullets)\n"
            . "4) Próximos passos no painel (até 5 bullets)\n"
            . "Idioma: Português (Brasil).";
    }

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        // Fail-safe: Smart Analysis should never crash the panel.
        $metrics = [
            'balance' => 0,
            'avg_monthly_burn' => 0,
            'months_left' => 99,
            'burn_trend' => 0,
            'last_month_burn' => 0,
        ];
        $insights = [];
        $prediction = ['labels' => [], 'values' => []];
        $monthly = ['labels' => [], 'income' => [], 'expense' => []];
        $categories = ['labels' => [], 'values' => []];
        $topDonors = [];

        try {
            $advisor = new AiFinancialAdvisor($tenantId);
            $metrics = $advisor->getSurvivalMetrics() ?: $metrics;
            $insights = $advisor->getInsights() ?: [];
            $prediction = $advisor->getPredictiveData() ?: $prediction;
        } catch (\Throwable $e) {
            // Keep defaults. (We don't expose errors to UI.)
        }

        try {
            $labels = [];
            $incomeSeries = [];
            $expenseSeries = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $labels[] = ucfirst($date->translatedFormat('M/y'));
                $incomeSeries[] = (float) \App\Models\Transaction::where('tenant_id', $tenantId)
                    ->where('type', 'income')
                    ->whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('amount');
                $expenseSeries[] = (float) \App\Models\Transaction::where('tenant_id', $tenantId)
                    ->where('type', 'expense')
                    ->whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('amount');
            }
            $monthly = ['labels' => $labels, 'income' => $incomeSeries, 'expense' => $expenseSeries];
        } catch (\Throwable $e) {
            $monthly = ['labels' => [], 'income' => [], 'expense' => []];
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('financial_categories')) {
                $since = \Carbon\Carbon::now()->subMonths(6)->startOfDay();
                $rows = \App\Models\Transaction::query()
                    ->selectRaw('financial_categories.name as name, SUM(transactions.amount) as total')
                    ->join('financial_categories', 'transactions.category_id', '=', 'financial_categories.id')
                    ->where('transactions.tenant_id', $tenantId)
                    ->where('transactions.type', 'expense')
                    ->where('transactions.date', '>=', $since)
                    ->groupBy('financial_categories.name')
                    ->orderByDesc(\Illuminate\Support\Facades\DB::raw('SUM(transactions.amount)'))
                    ->limit(8)
                    ->get();
                $categories = [
                    'labels' => $rows->pluck('name')->toArray(),
                    'values' => $rows->pluck('total')->map(fn($v) => (float)$v)->toArray(),
                ];
            }
        } catch (\Throwable $e) {
            $categories = ['labels' => [], 'values' => []];
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ngo_donors')) {
                $since = \Carbon\Carbon::now()->subMonths(6)->startOfDay();
                $rows = \App\Models\Transaction::query()
                    ->selectRaw('ngo_donors.name as name, SUM(transactions.amount) as total')
                    ->join('ngo_donors', 'transactions.ngo_donor_id', '=', 'ngo_donors.id')
                    ->where('transactions.tenant_id', $tenantId)
                    ->where('transactions.type', 'income')
                    ->whereNotNull('transactions.ngo_donor_id')
                    ->where('transactions.date', '>=', $since)
                    ->groupBy('ngo_donors.name')
                    ->orderByDesc(\Illuminate\Support\Facades\DB::raw('SUM(transactions.amount)'))
                    ->limit(5)
                    ->get()
                    ->map(fn($r) => ['name' => $r->name, 'total' => (float)$r->total])
                    ->toArray();
                $topDonors = $rows;
            }
        } catch (\Throwable $e) {
            $topDonors = [];
        }

        return view('ngo.smart_analysis.index', compact('metrics', 'insights', 'prediction', 'monthly', 'categories', 'topDonors'));
    }

    public function generateDeepAnalysis(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tenantType = auth()->user()->tenant->type ?? null;
        $role = auth()->user()->role ?? null;
        
        // 1. Gather recent data to feed the AI
        try {
            $transactions = Transaction::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->orderBy('date', 'desc')
                ->limit(20)
                ->get(['description', 'amount', 'type', 'status', 'date']);
        } catch (QueryException $e) {
            $transactions = collect();
        }

        try {
            $advisor = new AiFinancialAdvisor($tenantId);
            $metrics = $advisor->getSurvivalMetrics();
        } catch (\Throwable $e) {
            $metrics = [
                'balance' => 0,
                'avg_monthly_burn' => 0,
                'months_left' => 99,
            ];
        }

        $prompt = $this->buildDeepAnalysisPrompt($metrics, $transactions, $tenantType, $role);

        $ai         = new DeepSeekService();
        $resultDeep = $ai->chat([['role' => 'user', 'content' => $prompt]]);

        if (isset($resultDeep['choices'][0]['message']['content'])) {
            $analysis = $resultDeep['choices'][0]['message']['content'];
        } else {
            \Log::error('DeepSeek falhou na análise financeira: ' . ($resultDeep['error'] ?? 'Desconhecido'));
            // FALLBACK: Bruce AI v1 (motor local quando DeepSeek indisponível)
            $analysis = "### 🧠 Insights Estratégicos (Bruce AI v1)"
                . "\n\n*Nota: O motor de análise cognitiva está temporariamente em modo local.*"
                . "\n\n1. **Autonomia (Runway):** Seu tempo de sobrevivência de **" . number_format($metrics['months_left'], 1) . " meses** é " . ($metrics['months_left'] < 6 ? 'crítico. Recomenda-se contenção de custos imediatos.' : 'saudável. Há margem para investimentos planejados.') . "."
                . "\n2. **Caixa Disponível:** Status de **R$ " . number_format($metrics['balance'], 2, ',', '.') . "** em conta."
                . "\n3. **Bruce AI Recomenda:** Com base nas suas últimas " . $transactions->count() . " transações, foque em " . ($transactions->where('type', 'income')->count() > 0 ? 'fidelizar os doadores atuais.' : 'diversificar as fontes de captação de recursos.') . "."
                . "\n\n**O relatório detalhado retornará assim que a conexão com DeepSeek for normalizada.**";
        }

        return response()->json(['analysis' => $analysis]);
    }
}
