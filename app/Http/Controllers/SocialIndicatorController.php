<?php

namespace App\Http\Controllers;

use App\Services\IBGEDataService;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $norm   = $this->normalize($query);
            $cities = array_values(array_filter($cities, fn($c) => str_contains($this->normalize($c['nome']), $norm)));
        }

        return response()->json(array_slice($cities, 0, 12));
    }

    public function getIndicators(Request $request, string $cityCode)
    {
        $cityName = $request->get('city_name', '');
        $raw      = $this->ibgeService->getCityIndicators($cityCode, $cityName);

        // ── Indicadores derivados (calculados a partir dos brutos) ─────────────────────
        $derived = $this->computeDerived($raw);

        // ── Agrupa por tema para a view ────────────────────────────────────────────────
        $themed = [
            'demografia' => $this->pick($raw, ['populacao', 'pop_atual', 'area', 'densidade']),
            'infancia'   => $this->pick($raw, ['educacao', 'saneamento']) + $this->pick($derived, ['fora_escola_pct', 'fora_escola_est']),
            'saude'      => $this->pick($raw, ['mortalidade', 'obitos']),
            'economia'   => $this->pick($raw, ['pib', 'densidade', 'area']),
        ];

        $analysis = $this->generateAIAnalysis($raw, $derived, $cityName ?: $cityCode);

        return response()->json([
            'raw'      => $raw,
            'derived'  => $derived,
            'themed'   => $themed,
            'analysis' => $analysis,
        ]);
    }

    // ── Calcula indicadores derivados ────────────────────────────────────────────────
    protected function computeDerived(array $raw): array
    {
        $derived = [];

        // Crianças fora da escola (% e estimativa absoluta)
        $enrollVal = $raw['educacao']['value'] ?? null;
        $popVal    = $raw['populacao']['value'] ?? ($raw['pop_atual']['value'] ?? null);

        if ($enrollVal !== null) {
            $enrollRate = (float) str_replace(',', '.', $enrollVal);
            $pctFora    = round(100 - $enrollRate, 2);

            $derived['fora_escola_pct'] = [
                'value' => $pctFora,
                'year'  => $raw['educacao']['year'] ?? null,
                'label' => 'Fora da Escola (6–14 anos)',
                'unit'  => '%',
                'derived' => true,
            ];

            // Estimativa absoluta (13% da população está na faixa 6-14 anos — média nacional)
            if ($popVal !== null) {
                $pop  = (float) str_replace(['.', ','], ['', '.'], $popVal);
                $est  = (int) round($pop * 0.13 * ($pctFora / 100));
                $derived['fora_escola_est'] = [
                    'value'   => $est,
                    'year'    => $raw['educacao']['year'] ?? null,
                    'label'   => 'Crianças Fora da Escola',
                    'unit'    => 'crianças (estimativa)',
                    'derived' => true,
                ];
            }
        }

        // Taxa de mortalidade infantil — contexto (ideal OMS: < 10)
        $mortVal = $raw['mortalidade']['value'] ?? null;
        if ($mortVal !== null) {
            $mort = (float) str_replace(',', '.', $mortVal);
            $derived['mortalidade_contexto'] = $mort > 10
                ? 'Acima da meta OMS (< 10/mil). Projetos de saúde materno-infantil são prioritários.'
                : 'Dentro da meta OMS (< 10/mil). Manutenção e prevenção são a chave.';
        }

        return $derived;
    }

    protected function pick(array $arr, array $keys): array
    {
        return array_intersect_key($arr, array_flip($keys));
    }

    // ── Análise Bruce AI ──────────────────────────────────────────────────────────────
    protected function generateAIAnalysis(array $raw, array $derived, string $cityName): string
    {
        $apiKey = SystemSetting::getValue('deepseek_api_key');
        if (!$apiKey) {
            return 'Análise automática indisponível. Configure a DeepSeek API Key no Painel Admin.';
        }

        $itens = [];
        foreach ($raw as $key => $info) {
            if (!empty($info['value'])) $itens[] = "{$info['label']}: {$info['value']} {$info['unit']}";
        }
        if (!empty($derived['fora_escola_pct'])) {
            $itens[] = "Crianças fora da escola: {$derived['fora_escola_pct']['value']}% da faixa 6–14 anos";
        }
        if (!empty($derived['fora_escola_est'])) {
            $itens[] = "Estimativa absoluta: {$derived['fora_escola_est']['value']} crianças sem acesso à escola";
        }

        if (empty($itens)) return 'Dados insuficientes para gerar análise.';

        $prompt = "Você é o Bruce AI, especialista em diagnóstico social para o terceiro setor brasileiro. "
            . "Analise esses dados do IBGE para {$cityName}: " . implode('; ', $itens) . ". "
            . "Escreva 4 a 6 linhas identificando: (1) os principais vulnerabilidades sociais do município, "
            . "(2) qual população precisa de mais atenção, (3) que tipo de projeto social teria maior impacto. "
            . "Seja específico, use os números apresentados e conecte-os a oportunidades reais para ONGs. "
            . "Linguagem empática e profissional.";

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}", 'Content-Type' => 'application/json'])
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model'       => 'deepseek-chat',
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens'  => 400,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? 'Análise não disponível.';
            }
        } catch (\Exception $e) {
            Log::error('DeepSeek AI Error (territorial): ' . $e->getMessage());
        }

        return 'Não foi possível gerar a análise no momento.';
    }

    private function normalize(string $str): string
    {
        $str  = mb_strtolower($str, 'UTF-8');
        $from = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ'];
        $to   = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        return str_replace($from, $to, $str);
    }
}
