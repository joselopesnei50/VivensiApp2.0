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
     * @return array{new: int, updated: int}
     */
    public function search(string $term, string $location, ?int $tenantId): array
    {
        $apiKey = $this->getApiKey();

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(15)
                ->post('https://google.serper.dev/maps', [
                    'q'   => "$term em $location",
                    'gl'  => 'br',
                    'hl'  => 'pt-br',
                    'num' => 20,
                ]);

            if ($response->failed()) {
                throw new \Exception('Erro na busca do Serper Maps: ' . $response->body());
            }

            $results = $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('O serviço Serper demorou muito para responder. Tente novamente.');
        }

        $created = 0;
        $updated = 0;

        foreach ($results['places'] ?? [] as $item) {
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
     * @return array{new: int, updated: int}
     */
    public function searchWeb(string $term, ?int $tenantId): array
    {
        $apiKey = $this->getApiKey();

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(15)
                ->post('https://google.serper.dev/search', [
                    'q'   => $term,
                    'gl'  => 'br',
                    'hl'  => 'pt-br',
                    'num' => 10,
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
