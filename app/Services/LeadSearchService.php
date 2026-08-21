<?php

namespace App\Services;

use App\Jobs\ProcessProspect;
use App\Models\Prospect;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadSearchService
{
    // ── Busca por Localização (Google Maps) ───────────────────────────────────

    /**
     * Busca estabelecimentos físicos no Google Maps via Serper /maps.
     * Retorna nome, endereço, telefone, site e avaliação.
     *
     * $limit: 20/40/60/80/100. Serper Maps devolve 20 por página — para
     * >20 fazemos N chamadas (page=1..N) e deduplicamos por title+address.
     *
     * @return array{new: int, updated: int}
     */
    public function search(string $term, string $location, ?int $tenantId, int $limit = 20): array
    {
        $apiKey = $this->getApiKey();
        $limit  = max(20, min(100, $limit));
        $pages  = (int) ceil($limit / 20);

        $places = [];
        $seen   = [];
        // Serper Maps exige o parametro 'll' (@lat,lng,zoom) a partir da
        // page=2 — sem ele responde 400 e ja consome o credito da chamada.
        // Capturamos o ll na resposta da page=1 e reenviamos nas seguintes.
        $ll = null;

        try {
            for ($page = 1; $page <= $pages; $page++) {
                if ($page > 1 && $ll === null) {
                    Log::warning('Serper Maps: sem ll apos page 1, paginacao abortada', [
                        'term'     => $term,
                        'location' => $location,
                    ]);
                    break;
                }

                $payload = [
                    'q'    => "$term em $location",
                    'gl'   => 'br',
                    'hl'   => 'pt-br',
                    'num'  => 20,
                    'page' => $page,
                ];
                if ($ll !== null) {
                    $payload['ll'] = $ll;
                }

                $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                    ->timeout(15)
                    ->post('https://google.serper.dev/maps', $payload);

                if ($response->failed()) {
                    throw new \Exception('Erro na busca do Serper Maps: ' . $response->body());
                }

                $data = $response->json();

                if ($ll === null) {
                    // Preferencia: searchParameters.ll devolvido pelo Serper.
                    // Fallback: procura o primeiro place com lat/lng (nem todos
                    // trazem coord — evita pegar o place[0] "vazio" e abortar).
                    $ll = $data['searchParameters']['ll'] ?? null;
                    if ($ll === null) {
                        foreach ($data['places'] ?? [] as $p) {
                            if (!empty($p['latitude']) && !empty($p['longitude'])) {
                                $ll = '@' . $p['latitude'] . ',' . $p['longitude'] . ',14z';
                                break;
                            }
                        }
                    }
                }

                $batch = $data['places'] ?? [];
                if (empty($batch)) break;

                foreach ($batch as $item) {
                    $key = mb_strtolower(($item['title'] ?? '') . '|' . ($item['address'] ?? ''));
                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;
                    $places[]   = $item;
                    if (count($places) >= $limit) break 2;
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('O serviço Serper demorou muito para responder. Tente novamente.');
        }

        $created = 0;
        $updated = 0;

        foreach ($places as $item) {
            if (empty($item['title'])) continue;

            $prospect = Prospect::updateOrCreate(
                ['company_name' => $item['title'], 'tenant_id' => $tenantId],
                [
                    'phone'         => $item['phoneNumber'] ?? null,
                    'address'       => $item['address']     ?? null,
                    'website'       => $item['website']      ?? null,
                    'google_rating' => $item['rating']       ?? 0,
                    'total_reviews' => $item['ratingCount']  ?? 0,
                    'category'      => $term,
                    'source'        => 'maps',
                    'status'        => 'raw',
                ]
            );

            if ($prospect->wasRecentlyCreated) {
                ProcessProspect::dispatch($prospect)->onQueue('default');
                $this->dispatchEmailScrapeIfNeeded($prospect);
                $created++;
            } else {
                if ($prospect->status === 'raw') {
                    ProcessProspect::dispatch($prospect)->onQueue('default');
                }
                $this->dispatchEmailScrapeIfNeeded($prospect);
                $updated++;
            }
        }

        Log::info("Serper Maps: {$created} novos, {$updated} atualizados | {$term} em {$location}");

        return ['new' => $created, 'updated' => $updated];
    }

    // ── Busca Web Orgânica (Google Search) ────────────────────────────────────

    /**
     * Busca resultados orgânicos do Google via Serper /search.
     * Ideal para encontrar empresas online, negócios digitais, e-commerces,
     * ONGs, associações e qualquer entidade sem endereço físico no Maps.
     *
     * $limit: 10..100. Serper Search aceita num até 100 numa única chamada.
     *
     * @return array{new: int, updated: int}
     */
    public function searchWeb(string $term, ?int $tenantId, int $limit = 20): array
    {
        $apiKey = $this->getApiKey();
        $limit  = max(10, min(100, $limit));

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(20)
                ->post('https://google.serper.dev/search', [
                    'q'   => $term,
                    'gl'  => 'br',
                    'hl'  => 'pt-br',
                    'num' => $limit,
                ]);

            if ($response->failed()) {
                throw new \Exception('Erro na busca Serper Web: ' . $response->body());
            }

            $results = $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('O serviço Serper demorou muito para responder. Tente novamente.');
        }

        $created = 0;
        $updated = 0;

        foreach ($results['organic'] ?? [] as $item) {
            if (empty($item['title']) || empty($item['link'])) continue;

            // Filtrar resultados que claramente não são empresas (redes sociais genéricas, gov)
            $blacklistDomains = ['wikipedia.org', 'facebook.com', 'instagram.com', 'youtube.com'];
            $domain = parse_url($item['link'], PHP_URL_HOST) ?? '';
            if (collect($blacklistDomains)->contains(fn ($d) => str_contains($domain, $d))) continue;

            $prospect = Prospect::updateOrCreate(
                ['company_name' => $item['title'], 'tenant_id' => $tenantId],
                [
                    'website'  => $item['link'],
                    'snippet'  => $item['snippet'] ?? null,
                    'category' => $term,
                    'source'   => 'web',
                    'status'   => 'raw',
                ]
            );

            if ($prospect->wasRecentlyCreated) {
                ProcessProspect::dispatch($prospect)->onQueue('default');
                $this->dispatchEmailScrapeIfNeeded($prospect);
                $created++;
            } else {
                if ($prospect->status === 'raw') {
                    ProcessProspect::dispatch($prospect)->onQueue('default');
                }
                $this->dispatchEmailScrapeIfNeeded($prospect);
                $updated++;
            }
        }

        Log::info("Serper Web: {$created} novos, {$updated} atualizados | {$term}");

        return ['new' => $created, 'updated' => $updated];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getApiKey(): string
    {
        $apiKey = SystemSetting::getValue('serper_api_key');

        if (!$apiKey) {
            throw new \Exception('Chave da API Serper não configurada. Acesse Super Admin › Configurações de API.');
        }

        return $apiKey;
    }

    /**
     * Se o prospect tem site e ainda não tem e-mail, dispara scraping
     * assíncrono na queue 'emails'. É best-effort — falha silenciosa se
     * o site estiver fora do ar ou não expuser e-mail.
     */
    private function dispatchEmailScrapeIfNeeded(Prospect $prospect): void
    {
        if (empty($prospect->website) || !empty($prospect->email)) {
            return;
        }

        \App\Jobs\ScrapeProspectEmailJob::dispatch($prospect->id)
            ->onQueue('emails');
    }
}
