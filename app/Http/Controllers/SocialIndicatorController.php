<?php

namespace App\Http\Controllers;

use App\Services\IBGEDataService;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class SocialIndicatorController extends Controller
{
    protected $ibgeService;

    public function __construct(IBGEDataService $ibgeService)
    {
        $this->ibgeService = $ibgeService;
    }

    public function index()
    {
        return view('intelligence.territorial');
    }

    public function searchCities(Request $request)
    {
        $query = $request->get('q');
        $cities = $this->ibgeService->getCities();

        if ($query) {
            $cities = array_filter($cities, function ($city) use ($query) {
                return str_contains(strtolower($city['nome']), strtolower($query));
            });
        }

        return response()->json(array_values(array_slice($cities, 0, 10)));
    }

    public function getIndicators($cityCode)
    {
        $data = $this->ibgeService->getCityIndicators($cityCode);
        
        // Integrar com Bruce AI para análise
        $analysis = $this->generateAIAnalysis($data);

        return response()->json([
            'indicators' => $data,
            'analysis'   => $analysis
        ]);
    }

    protected function generateAIAnalysis($data)
    {
        $apiKey = SystemSetting::getValue('deepseek_api_key');
        if (!$apiKey) return "Análise automática indisponível (Chave de API não configurada).";

        $prompt = "Atue como um especialista em análise socioeconômica. Analise estes dados do IBGE para uma cidade: " . json_encode($data) . ". 
        Crie um parágrafo curto e direto (máximo 4 linhas) explicando o impacto desses dados e por que projetos sociais são importantes nesta região. 
        Seja empático e profissional.";

        try {
            $response = Http::withToken($apiKey)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é o Bruce AI, assistente do Vivensi App.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
        } catch (\Exception $e) {
            return "Erro ao gerar análise via Bruce AI.";
        }

        return "Não foi possível gerar a análise no momento.";
    }
}
