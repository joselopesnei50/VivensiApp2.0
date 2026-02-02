<?php

namespace App\Http\Controllers;

use App\Services\AiFinancialAdvisor;
use App\Services\GeminiService;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use App\Models\Transaction;

class SmartAnalysisController extends Controller
{
    public function index()
    {
        $advisor = new AiFinancialAdvisor(auth()->user()->tenant_id);
        $metrics = $advisor->getSurvivalMetrics();
        $insights = $advisor->getInsights();
        $prediction = $advisor->getPredictiveData();

        return view('ngo.smart_analysis.index', compact('metrics', 'insights', 'prediction'));
    }

    public function generateDeepAnalysis(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // 1. Gather recent data to feed the AI
        $transactions = Transaction::where('tenant_id', $tenantId)
                                   ->orderBy('date', 'desc')
                                   ->limit(20)
                                   ->get(['description', 'amount', 'type', 'date']);
        
        $advisor = new AiFinancialAdvisor($tenantId);
        $metrics = $advisor->getSurvivalMetrics();

        $prompt = "Atue como um CFO (Diretor Financeiro) altamente experiente em SaaS e ONGs. 
                   Analise os seguintes dados financeiros e forneça 3 recomendações estratégicas curtas e acionáveis.
                   
                   Dados Atuais:
                   - Saldo em Caixa: R$ " . number_format($metrics['balance'], 2) . "
                   - Queima Mensal Média (Burn): R$ " . number_format($metrics['avg_monthly_burn'], 2) . "
                   - Autonomia (Runway): " . number_format($metrics['months_left'], 1) . " meses.
                   
                   Últimas transações: " . json_encode($transactions) . "
                   
                   Responda em tom profissional, focado em sobrevivência e crescimento. 
                   Use Markdown para formatação. Responda em Português.";

        // Attempt Gemini first, then DeepSeek as fallback
        $ai = new GeminiService();
        $result = $ai->callGemini([['text' => $prompt]]);

        if (isset($result['error'])) {
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
            // Sucesso com Gemini
            $analysis = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Falha ao processar resposta do Gemini.";
        }

        return response()->json(['analysis' => $analysis]);
    }
}
