<?php

namespace App\Services;

use App\Models\IbgeIndicatorCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class IBGEDataService
{
    protected $baseUrl        = 'https://servicodados.ibge.gov.br/api/v3/agregados';
    protected $localidadesUrl = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    public function getCities(): array
    {
        return Cache::remember('ibge_cities_list', 86400 * 30, function () {
            $response = Http::timeout(15)->get("{$this->localidadesUrl}/municipios?orderBy=nome");
            return $response->successful() ? $response->json() : [];
        });
    }

    public function getCityIndicators(string $cityCode, string $cityName = ''): array
    {
        $indicators = [
            'populacao'  => ['table' => '4714',    'variable' => '93',      'label' => 'População'],
            'renda'      => ['table' => '6407',    'variable' => '606',     'label' => 'Renda per capita'],
            'educacao'   => ['table' => '1383',    'variable' => '156',     'label' => 'Taxa de Escolarização'],
            'saneamento' => ['table' => '3218',    'variable' => '1000096', 'label' => 'Saneamento Adequado'],
        ];

        $results = [];
        foreach ($indicators as $key => $config) {
            $results[$key] = $this->getIndicator($cityCode, $key, $config['table'], $config['variable'], $cityName);
        }

        return $results;
    }

    protected function getIndicator(string $cityCode, string $key, string $table, string $variable, string $cityName = ''): array
    {
        // 1. Check local cache (valid for 30 days)
        $cached = IbgeIndicatorCache::where('city_ibge_code', $cityCode)
            ->where('indicator_key', $key)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->first();

        if ($cached) {
            return [
                'value'    => $cached->value,
                'year'     => $cached->year,
                'cached'   => true,
                'city_name'=> $cached->city_name,
            ];
        }

        // 2. Fetch from IBGE SIDRA v3
        try {
            // "all" returns every period; we pick the latest non-null entry
            $url      = "{$this->baseUrl}/{$table}/periodos/all/variaveis/{$variable}?localidades=n6[{$cityCode}]";
            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data[0]['resultados'][0]['series'][0]['serie'])) {
                    $serie = $data[0]['resultados'][0]['series'][0]['serie'];

                    // Extract city name from API if not provided
                    if (!$cityName) {
                        $cityName = $data[0]['resultados'][0]['series'][0]['localidade']['nome'] ?? '';
                    }

                    // Get last period with a valid (non-dash) value
                    $filtered = array_filter($serie, fn($v) => $v !== '-' && $v !== '' && $v !== null);

                    if (!empty($filtered)) {
                        end($filtered);
                        $year  = key($filtered);
                        $value = current($filtered);

                        IbgeIndicatorCache::updateOrCreate(
                            ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                            ['value' => $value, 'year' => (int) $year, 'city_name' => $cityName]
                        );

                        return ['value' => $value, 'year' => $year, 'cached' => false, 'city_name' => $cityName];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("IBGE API Error [{$key} / city {$cityCode}]: " . $e->getMessage());
        }

        return ['value' => null, 'year' => null, 'cached' => false, 'city_name' => $cityName];
    }
}
