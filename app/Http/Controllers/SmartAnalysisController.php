<?php

namespace App\Http\Controllers;

use App\Services\AiFinancialAdvisor;
use App\Services\GeminiService;
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

        try {
            $advisor = new AiFinancialAdvisor($tenantId);
            $metrics = $advisor->getSurvivalMetrics() ?: $metrics;
            $insights = $advisor->getInsights() ?: [];
            $prediction = $advisor->getPredictiveData() ?: $prediction;
        } catch (\Throwable $e) {
            // Keep defaults. (We don't expose errors to UI.)
        }

        return view('ngo.smart_analysis.index', compact('metrics', 'insights', 'prediction'));
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

        // Attempt Gemini first, then DeepSeek as fallback
        $ai = new GeminiService();
        $result = $ai->callGemini([['text' => $prompt]]);

        $geminiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        // Treat missing/empty candidates as failure too.
        if (isset($result['error']) || !$geminiText) {
            $ai = new DeepSeekService();
            $messages = [['role' => 'user', 'content' => $prompt]];
            $resultDeep = $ai->chat($messages);
            
            if (isset($resultDeep['choices'][0]['message']['content'])) {
                $analysis = $resultDeep['choices'][0]['message']['content'];
            } else {
                \Log::error('Ambas as AIs falharam. Erro DeepSeek: ' . ($resultDeep['error'] ?? 'Desconhecido'));
                // FALLBACK ESTRATÉGICO: Bruce AI v1 (Motor Local)
                $analysis = "### 🧠 Insights Estratégicos (Bruce AI v1) 
                            \n\n*Nota: O motor de análise cognitiva está temporariamente processando em modo local.*
                            \n\n1. **Autonomia (Runway):** Seu tempo de sobrevivência de **" . number_format($metrics['months_left'], 1) . " meses** é " . ($metrics['months_left'] < 6 ? 'crítico. Recomenda-se contenção de custos imediatos.' : 'saudável. Há margem para investimentos planejados.') . "
                            \n2. **Caixa Disponível:** Status de **R$ " . number_format($metrics['balance'], 2, ',', '.') . "** em conta.
                            \n3. **Bruce AI Recomenda:** Com base nas suas últimas " . $transactions->count() . " transações, foque em " . ($transactions->where('type', 'income')->count() > 0 ? 'fidelizar os doadores atuais.' : 'diversificar as fontes de captação de recursos.') . "
                            \n\n**O relatório cognitivo detalhado será reativado assim que a comunicação com os clusters Gemini/DeepSeek for normalizada.**";
            }
        } else {
            \Log::info('Sucesso com Gemini');
            $analysis = $geminiText;
        }

        return response()->json(['analysis' => $analysis]);
    }
}
