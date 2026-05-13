<?php

namespace App\Services;

use App\Models\IbgeIndicatorCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IBGEDataService
{
    protected string $indicadoresUrl = 'https://servicodados.ibge.gov.br/api/v1/pesquisas/-/indicadores';
    protected string $sidraUrl       = 'https://servicodados.ibge.gov.br/api/v3/agregados';
    protected string $localidadesUrl = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    // Indicadores via IBGE Cidades/Pesquisas — códigos verificados via API em 2026-05
    protected array $ibgeCidadesIndicadores = [
        'populacao'   => ['code' => '29166', 'label' => 'População (Censo)',    'unit' => 'hab.'],
        'pop_atual'   => ['code' => '29171', 'label' => 'Pop. Estimada',        'unit' => 'hab.'],
        'area'        => ['code' => '29167', 'label' => 'Área Territorial',     'unit' => 'km²'],
        'densidade'   => ['code' => '29168', 'label' => 'Densidade Demog.',     'unit' => 'hab/km²'],
        'pib'         => ['code' => '47001', 'label' => 'PIB per capita',       'unit' => 'R$/ano'],
        'mortalidade' => ['code' => '30279', 'label' => 'Mortalidade Infantil', 'unit' => '/1.000 nascidos'],
    ];

    // Indicadores via SIDRA (Educação, Saneamento, Óbitos do Registro Civil)
    protected array $sidraIndicadores = [
        'educacao'   => ['table' => '1383', 'variable' => '156',     'label' => 'Escolarização 6–14 anos', 'unit' => '%'],
        'saneamento' => ['table' => '3218', 'variable' => '1000096', 'label' => 'Saneamento Adequado',     'unit' => '%'],
        'obitos'     => ['table' => '2683', 'variable' => '343',     'label' => 'Óbitos Registrados',      'unit' => 'por ano'],
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

        // ── 1. IBGE Cidades — uma requisição para todos os indicadores ─────────────────
        $codes    = implode('|', array_column($this->ibgeCidadesIndicadores, 'code'));
        $cacheKey = "ibge_indicadores_v3_{$cityCode}";

        $rawData = Cache::remember($cacheKey, 86400 * 7, function () use ($codes, $cityCode) {
            $resp = Http::timeout(20)->get("{$this->indicadoresUrl}/{$codes}/resultados/{$cityCode}");
            return $resp->successful() ? $resp->json() : null;
        });

        if ($rawData) {
            foreach ($rawData as $item) {
                $indicatorId = (string) $item['id'];

                // Lookup correto: iterar pelo array associativo (nunca usar array_search+array_column)
                $key = null;
                foreach ($this->ibgeCidadesIndicadores as $k => $cfg) {
                    if ($cfg['code'] === $indicatorId) { $key = $k; break; }
                }
                if ($key === null) continue;

                $res  = $item['res'][0]['res'] ?? [];
                if (empty($res)) continue;

                // Pega o último período com valor válido (numérico)
                $filtered = array_filter($res, fn($v) => $v !== '-' && $v !== '' && $v !== null && is_numeric(str_replace(['.', ','], '', $v)));
                if (empty($filtered)) continue;

                krsort($filtered); // Sort by key (year) descending
                $year  = array_key_first($filtered);
                $value = $filtered[$year];

                $results[$key] = [
                    'value'  => $value,
                    'year'   => $year,
                    'label'  => $this->ibgeCidadesIndicadores[$key]['label'],
                    'unit'   => $this->ibgeCidadesIndicadores[$key]['unit'],
                    'cached' => false,
                ];

                // Persiste no cache local
                IbgeIndicatorCache::updateOrCreate(
                    ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                    ['value' => $value, 'year' => (int) $year, 'city_name' => $cityName]
                );
            }
        }

        // ── 2. Preenche indicadores faltantes do banco local (cache 30 dias) ──────────
        foreach ($this->ibgeCidadesIndicadores as $key => $cfg) {
            if (isset($results[$key])) continue;

            $cached = IbgeIndicatorCache::where('city_ibge_code', $cityCode)
                ->where('indicator_key', $key)
                ->where('updated_at', '>=', Carbon::now()->subDays(30))
                ->first();

            $results[$key] = $cached
                ? ['value' => $cached->value, 'year' => $cached->year,  'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => true]
                : ['value' => null,            'year' => null,           'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
        }

        // ── 3. SIDRA — Educação e Saneamento ─────────────────────────────────────────
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
                $data  = $resp->json();
                $serie = $data[0]['resultados'][0]['series'][0]['serie'] ?? [];
                $filtered = array_filter($serie, fn($v) => $v !== '-' && $v !== '' && $v !== null);

                if (!empty($filtered)) {
                    end($filtered);
                    $year  = key($filtered);
                    $value = current($filtered);

                    IbgeIndicatorCache::updateOrCreate(
                        ['city_ibge_code' => $cityCode, 'indicator_key' => $key],
                        ['value' => $value, 'year' => (int) $year, 'city_name' => $cityName]
                    );

                    return ['value' => $value, 'year' => $year, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
                }
            }
        } catch (\Exception $e) {
            Log::warning("IBGE SIDRA [{$key}/{$cityCode}]: " . $e->getMessage());
        }

        return ['value' => null, 'year' => null, 'label' => $cfg['label'], 'unit' => $cfg['unit'], 'cached' => false];
    }
}
