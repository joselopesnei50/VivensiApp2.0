<?php

namespace App\Services\Radar;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QueridoDiarioService
{
    private string $baseUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl = config('radar.querido_diario.base_url');
        $this->timeout = (int) config('radar.querido_diario.timeout', 15);
    }

    /**
     * Busca achados no Querido Diário para um território e palavra-chave.
     * Retorna array normalizado; array vazio em caso de falha.
     */
    public function search(string $ibgeCode, string $keyword, string $since): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/gazettes", [
                    'territory_ids'       => $ibgeCode,
                    'querystring'         => $keyword,
                    'published_since'     => $since,
                    'excerpt_size'        => config('radar.querido_diario.excerpt_size', 500),
                    'number_of_excerpts'  => config('radar.querido_diario.number_of_excerpts', 3),
                    'size'                => config('radar.querido_diario.page_size', 20),
                ]);

            if (! $response->successful()) {
                Log::warning('QueridoDiarioService: resposta não-2xx', [
                    'ibge'    => $ibgeCode,
                    'keyword' => $keyword,
                    'status'  => $response->status(),
                ]);
                return [];
            }

            $gazettes = $response->json('gazettes', []);
            return $this->normalize($gazettes, $keyword);

        } catch (\Throwable $e) {
            Log::warning('QueridoDiarioService: falha na requisição', [
                'ibge'    => $ibgeCode,
                'keyword' => $keyword,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function normalize(array $gazettes, string $keyword): array
    {
        $results = [];

        foreach ($gazettes as $gazette) {
            $excerpts = $gazette['excerpts'] ?? [];
            if (empty($excerpts)) {
                continue;
            }

            $excerpt    = implode(' [...] ', $excerpts);
            $sourceUrl  = $gazette['url'] ?? '';
            $publishedAt = $gazette['date'] ?? now()->toDateString();
            $title      = $this->extractTitle($excerpt, $gazette['territory_name'] ?? '');

            $results[] = [
                'source'          => 'querido_diario',
                'territory_ibge'  => $gazette['territory_id'] ?? null,
                'title'           => $title,
                'excerpt'         => $excerpt,
                'source_url'      => $sourceUrl,
                'published_at'    => $publishedAt,
                'keyword_matched' => $keyword,
                'raw_payload'     => $gazette,
            ];
        }

        return $results;
    }

    private function extractTitle(string $excerpt, string $territory): string
    {
        $clean = preg_replace('/\s+/', ' ', trim(strip_tags($excerpt)));
        $short = mb_substr($clean, 0, 120);
        $title = rtrim($short, '.,;:- ');

        if ($territory) {
            return "{$title} — {$territory}";
        }
        return $title ?: 'Achado sem título';
    }
}
