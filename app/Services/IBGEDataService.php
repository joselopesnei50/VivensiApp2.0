<?php

namespace App\Services;

use App\Models\IbgeIndicatorCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IBGEDataService
{
    protected $baseUrl = 'https://servicodados.ibge.gov.br/api/v3/agregados';
    protected $localidadesUrl = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    /**
     * Get all cities for search autocomplete
     */
    public function getCities()
    {
        return \Cache::remember('ibge_cities_list', 86400 * 30, function () {
            $response = Http::get("{$this->localidadesUrl}/municipios?orderBy=nome");
            return $response->successful() ? $response->json() : [];
        });
    }

    /**
     * Get indicators for a specific city
     */
    public function getCityIndicators($cityCode)
    {
        $indicators = [
            'populacao' => ['table' => '4714', 'variable' => '93'], // População residente
            'renda'     => ['table' => '6407', 'variable' => '606'], // Rendimento mensal per capita
            'educacao'  => ['table' => '1383', 'variable' => '156'], // Taxa de escolarização 6 a 14 anos
            'saneamento'=> ['table' => '3218', 'variable' => '1000096'], // Esgotamento sanitário adequado
        ];

        $results = [];

        foreach ($indicators as $key => $config) {
            $results[$key] = $this->getIndicator($cityCode, $key, $config['table'], $config['variable']);
        }

        return $results;
    }

    protected function getIndicator($cityCode, $key, $table, $variable)
    {
        // 1. Check Cache
        $cached = IbgeIndicatorCache::where('city_ibge_code', $cityCode)
            ->where('indicator_key', $key)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->first();

        if ($cached) {
            return [
                'value' => $cached->value,
                'year'  => $cached->year,
                'cached' => true
            ];
        }

        // 2. Fetch from SIDRA
        try {
            // URL format: /agregados/{tabela}/periodos/all/variaveis/{variavel}?localidades=t6[all]|n6[{cidade}]
            $url = "{$this->baseUrl}/{$table}/periodos/last/variaveis/{$variable}?localidades=n6[{$cityCode}]";
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[0]['resultados'][0]['series'][0]['serie'])) {
                    $serie = $data[0]['resultados'][0]['series'][0]['serie'];
                    $year = key($serie);
                    $value = current($serie);

                    // Update Cache
                    IbgeIndicatorCache::updateOrCreate(
                        ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                        [
                            'value' => $value,
                            'year'  => $year,
                            'city_name' => '', // Will update if needed
                            'updated_at' => now()
                        ]
                    );

                    return ['value' => $value, 'year' => $year, 'cached' => false];
                }
            }
        } catch (\Exception $e) {
            Log::error("IBGE API Error ($key): " . $e->getMessage());
        }

        return ['value' => 'N/A', 'year' => '-', 'cached' => false];
    }
}
