<?php

namespace App\Services;

use App\Models\IbgeIndicatorCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IBGEDataService
{
    // Endpoint "Pesquisas/Indicadores" — mais confiável que SIDRA para dados municipais
    protected $indicadoresUrl = 'https://servicodados.ibge.gov.br/api/v1/pesquisas/-/indicadores';
    protected $sidraUrl       = 'https://servicodados.ibge.gov.br/api/v3/agregados';
    protected $localidadesUrl = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    // Indicadores IBGE Cidades (confiáveis para todos os municípios)
    protected array $ibgeCidadesIndicadores = [
        'populacao'    => ['code' => '29167', 'label' => 'População',          'unit' => 'habitantes'],
        'area'         => ['code' => '77861', 'label' => 'Área Territorial',   'unit' => 'km²'],
        'densidade'    => ['code' => '29168', 'label' => 'Densidade Demog.',   'unit' => 'hab/km²'],
        'pib'          => ['code' => '30279', 'label' => 'PIB per capita',     'unit' => 'R$/ano'],
        'idhm'         => ['code' => '30255', 'label' => 'IDHM',               'unit' => 'índice'],
        'mortalidade'  => ['code' => '29987', 'label' => 'Mortalidade Infantil','unit' => 'por 1.000'],
    ];

    // Indicadores SIDRA (específicos — usado como complemento)
    protected array $sidraIndicadores = [
        'educacao'   => ['table' => '1383', 'variable' => '156',     'label' => 'Escolarização 6–14 anos', 'unit' => '%'],
        'saneamento' => ['table' => '3218', 'variable' => '1000096', 'label' => 'Saneamento Adequado',     'unit' => '%'],
    ];

    public function getCities(): array
    {
        return Cache::remember('ibge_cities_list', 86400 * 30, function () {
            $resp = Http::timeout(15)->get("{$this->localidadesUrl}/municipios?orderBy=nome");
            return $resp->successful() ? $resp->json() : [];
        });
    }

    public function getCityIndicators(string $cityCode, string $cityName = ''): array
    {
        $results = [];

        // ── 1. IBGE Cidades / Indicadores endpoint (uma única requisição para 6 indicadores) ─
        $codes   = implode('|', array_column($this->ibgeCidadesIndicadores, 'code'));
        $cacheKey = "ibge_indicadores_{$cityCode}";

        $rawData = Cache::remember($cacheKey, 86400 * 7, function () use ($codes, $cityCode) {
            $resp = Http::timeout(20)->get("{$this->indicadoresUrl}/{$codes}/resultados/{$cityCode}");
            return $resp->successful() ? $resp->json() : null;
        });

        if ($rawData) {
            foreach ($rawData as $item) {
                $indicatorId = (string) $item['id'];
                $key = array_search(
                    $indicatorId,
                    array_column($this->ibgeCidadesIndicadores, 'code')
                );
                if ($key === false) {
                    // find by numeric id
                    foreach ($this->ibgeCidadesIndicadores as $k => $cfg) {
                        if ($cfg['code'] === $indicatorId) { $key = $k; break; }
                    }
                }
                if ($key === false) continue;

                $res  = $item['res'][0]['res'] ?? [];
                $year = !empty($res) ? max(array_keys($res)) : null;
                $val  = $year ? ($res[$year] ?? null) : null;

                // Skip invalid
                if ($val === '-' || $val === '' || $val === null) continue;

                $results[$key] = [
                    'value'  => $val,
                    'year'   => $year,
                    'label'  => $this->ibgeCidadesIndicadores[$key]['label'],
                    'unit'   => $this->ibgeCidadesIndicadores[$key]['unit'],
                    'cached' => false,
                ];

                // Persist to local cache
                IbgeIndicatorCache::updateOrCreate(
                    ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                    ['value' => $val, 'year' => (int)$year, 'city_name' => $cityName]
                );
            }
        }

        // Fill missing main indicators from local DB cache
        foreach ($this->ibgeCidadesIndicadores as $key => $cfg) {
            if (isset($results[$key])) continue;
            $cached = IbgeIndicatorCache::where('city_ibge_code', $cityCode)
                ->where('indicator_key', $key)->first();
            if ($cached) {
                $results[$key] = [
                    'value'  => $cached->value,
                    'year'   => $cached->year,
                    'label'  => $cfg['label'],
                    'unit'   => $cfg['unit'],
                    'cached' => true,
                ];
            } else {
                $results[$key] = ['value' => null, 'year' => null, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
            }
        }

        // ── 2. SIDRA — Educação e Saneamento ─────────────────────────────────────────────────
        foreach ($this->sidraIndicadores as $key => $cfg) {
            $results[$key] = $this->getSidraIndicator($cityCode, $key, $cfg, $cityName);
        }

        return $results;
    }

    protected function getSidraIndicator(string $cityCode, string $key, array $cfg, string $cityName): array
    {
        $cached = IbgeIndicatorCache::where('city_ibge_code', $cityCode)
            ->where('indicator_key', $key)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->first();

        if ($cached) {
            return ['value' => $cached->value, 'year' => $cached->year, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => true];
        }

        try {
            $url  = "{$this->sidraUrl}/{$cfg['table']}/periodos/all/variaveis/{$cfg['variable']}?localidades=n6[{$cityCode}]";
            $resp = Http::timeout(15)->get($url);

            if ($resp->successful()) {
                $data = $resp->json();
                $serie = $data[0]['resultados'][0]['series'][0]['serie'] ?? [];
                $filtered = array_filter($serie, fn($v) => $v !== '-' && $v !== '' && $v !== null);

                if (!empty($filtered)) {
                    end($filtered);
                    $year  = key($filtered);
                    $value = current($filtered);

                    IbgeIndicatorCache::updateOrCreate(
                        ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                        ['value' => $value, 'year' => (int)$year, 'city_name' => $cityName]
                    );

                    return ['value' => $value, 'year' => $year, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
                }
            }
        } catch (\Exception $e) {
            Log::error("IBGE SIDRA Error [{$key} / {$cityCode}]: " . $e->getMessage());
        }

        return ['value' => null, 'year' => null, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
    }
}
