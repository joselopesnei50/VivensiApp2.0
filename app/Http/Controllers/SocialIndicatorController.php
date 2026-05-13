<?php

namespace App\Http\Controllers;

use App\Services\IBGEDataService;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SocialIndicatorController extends Controller
{
    public function __construct(protected IBGEDataService $ibgeService) {}

    public function index()
    {
        return view('intelligence.territorial');
    }

    public function searchCities(Request $request)
    {
        $query  = trim($request->get('q', ''));
        $cities = $this->ibgeService->getCities();

        if ($query) {
            $normalized = $this->normalize($query);
            $cities = array_values(array_filter($cities, function ($city) use ($normalized) {
                return str_contains($this->normalize($city['nome']), $normalized);
            }));
        }

        return response()->json(array_slice($cities, 0, 12));
    }

    public function getIndicators(Request $request, string $cityCode)
    {
        $cityName  = $request->get('city_name', '');
        $data      = $this->ibgeService->getCityIndicators($cityCode, $cityName);
        $analysis  = $this->generateAIAnalysis($data, $cityName ?: $cityCode);

        return response()->json([
            'indicators' => $data,
            'analysis'   => $analysis,
        ]);
    }

    protected function generateAIAnalysis(array $data, string $cityName): string
    {
        $apiKey = SystemSetting::getValue('deepseek_api_key');
        if (!$apiKey) {
            return 'Análise automática indisponível. Configure a DeepSeek API Key no Painel Admin.';
        }

        $resumo = [];
        foreach ($data as $key => $info) {
            if ($info['value'] !== null) {
                $resumo[] = "{$key}: {$info['value']} ({$info['year']})";
            }
        }

        $prompt = "Atue como especialista em análise socioeconômica brasileira. "
            . "Com base nos dados do IBGE para o município de {$cityName}: " . implode(', ', $resumo) . ". "
            . "Escreva um parágrafo curto (máximo 5 linhas) apontando os principais desafios sociais "
            . "e como projetos do terceiro setor podem atuar nessa realidade. Seja objetivo e empático.";

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}", 'Content-Type' => 'application/json'])
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model'    => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Você é o Bruce AI, assistente estratégico do Vivensi App.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature'=> 0.7,
                    'max_tokens' => 300,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? 'Análise não disponível.';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DeepSeek AI Error (territorial): ' . $e->getMessage());
        }

        return 'Não foi possível gerar a análise no momento. Tente novamente.';
    }

    private function normalize(string $str): string
    {
        return strtolower(
            preg_replace('/[\x{0300}-\x{036f}]/u', '',
                \Normalizer::normalize($str, \Normalizer::FORM_D)
            )
        );
    }
}
