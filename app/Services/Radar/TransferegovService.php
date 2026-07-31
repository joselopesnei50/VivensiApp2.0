<?php

namespace App\Services\Radar;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransferegovService
{
    private string $baseUrl;
    private string $endpoint;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl  = config('radar.transferegov.base_url');
        $this->endpoint = config('radar.transferegov.endpoint');
        $this->timeout  = (int) config('radar.transferegov.timeout', 15);
    }

    /**
     * Busca chamamentos públicos abertos no Transferegov (PostgREST).
     * A API não filtra por palavra-chave — a query traz todos os abertos do
     * período, então uma única chamada basta. keyword_matched fixo em
     * 'transferegov' pra não contaminar as estatísticas de qualidade das
     * keywords do Querido Diário (que alimentam a auto-aprovação).
     * Retorna array normalizado; array vazio em caso de falha.
     */
    public function fetchChamamentos(string $since): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}{$this->endpoint}", [
                    'situacao_chamamento' => 'eq.ABERTO',
                    'dt_abertura_chamamento' => "gte.{$since}",
                    'order'  => 'dt_abertura_chamamento.desc',
                    'limit'  => 50,
                ]);

            if (! $response->successful()) {
                Log::warning('TransferegovService: resposta não-2xx', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $items = $response->json() ?? [];

            if (! is_array($items)) {
                return [];
            }

            return $this->normalize($items);

        } catch (\Throwable $e) {
            Log::warning('TransferegovService: falha na requisição', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function normalize(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $title   = $item['ds_objeto_chamamento'] ?? $item['nr_chamamento'] ?? 'Chamamento público';
            $excerpt = $item['ds_objeto_chamamento'] ?? $item['ds_justificativa'] ?? '';
            $url     = $item['link_edital'] ?? $item['link_chamamento']
                    ?? "{$this->baseUrl}/chamamentos/{$item['id_chamamento']}";

            if (empty($excerpt)) {
                continue;
            }

            $publishedAt = isset($item['dt_abertura_chamamento'])
                ? substr($item['dt_abertura_chamamento'], 0, 10)
                : now()->toDateString();

            $results[] = [
                'source'          => 'transferegov',
                'territory_ibge'  => null,
                'title'           => mb_substr($title, 0, 255),
                'excerpt'         => $excerpt,
                'source_url'      => $url,
                'published_at'    => $publishedAt,
                'keyword_matched' => 'transferegov',
                'raw_payload'     => $item,
            ];
        }

        return $results;
    }
}
