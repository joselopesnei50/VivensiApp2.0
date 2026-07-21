<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CnpjApiService
{
    private const BASE_URL = 'https://brasilapi.com.br/api/cnpj/v1/';
    private const TIMEOUT  = 10;

    /**
     * Consulta dados públicos de um CNPJ via BrasilAPI.
     * Retorna array com campos mapeados para o Tenant, ou null em caso de falha.
     *
     * @return array{
     *   razao_social: string,
     *   cnae_principal: string,
     *   data_fundacao: string|null,
     *   situacao_cadastral: string,
     *   logradouro: string|null,
     *   numero: string|null,
     *   complemento: string|null,
     *   bairro: string|null,
     *   municipio: string|null,
     *   uf: string|null,
     *   cep: string|null,
     * }|null
     */
    public function consultar(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get(self::BASE_URL . $cnpj);

            if (! $response->successful()) {
                Log::warning('CnpjApiService: resposta não 2xx', [
                    'cnpj'   => $cnpj,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            return $this->mapear($data);
        } catch (\Throwable $e) {
            Log::warning('CnpjApiService: falha na consulta', [
                'cnpj'  => $cnpj,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function mapear(array $data): array
    {
        $cnae = $data['cnae_fiscal'] ?? null;

        // data_inicio_atividade vem como "2010-05-20"
        $dataAbertura = $data['data_inicio_atividade'] ?? null;

        return [
            'razao_social'       => $data['razao_social'] ?? '',
            'cnae_principal'     => $cnae ? (string) $cnae : null,
            'data_fundacao'      => $dataAbertura,
            'situacao_cadastral' => $data['descricao_situacao_cadastral'] ?? 'DESCONHECIDA',
            'logradouro'         => $data['logradouro'] ?? null,
            'numero'             => $data['numero'] ?? null,
            'complemento'        => $data['complemento'] ?? null,
            'bairro'             => $data['bairro'] ?? null,
            'municipio'          => $data['municipio'] ?? null,
            'uf'                 => $data['uf'] ?? null,
            'cep'                => isset($data['cep']) ? preg_replace('/\D/', '', $data['cep']) : null,
        ];
    }
}
