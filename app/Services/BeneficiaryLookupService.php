<?php

namespace App\Services;

use App\Models\Beneficiary;
use Illuminate\Support\Collection;

/**
 * Busca de beneficiario pra features que aceitam nome livre OU CPF/NIS.
 *
 * Beneficiary.cpf/nis sao AES-encrypted em repouso; busca direta com LIKE no
 * ciphertext nao casa. Este service usa cpf_bidx/nis_bidx (HMAC-SHA256) —
 * mesmo padrao do User.phone_bidx, Contract.token_bidx, NgoDonor.portal_token.
 *
 * Bug historico: bot interno (/admin/bot) fazia
 *   Beneficiary::where('cpf', 'like', "%{$query}%")
 * e sempre retornava 0 resultados. Fix em 2026-08-19.
 */
class BeneficiaryLookupService
{
    /**
     * Busca por nome parcial + CPF/NIS exato (via bidx).
     *
     * Aceita CPF/NIS com ou sem mascara — normaliza pra digitos antes do bidx.
     * Se o cadastro guardou o valor COM mascara, calcula bidx das duas variantes
     * (digitos puros E versao mascarada padrao) pra maximizar match.
     */
    public function search(int $tenantId, string $query, int $limit = 3): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $digits = preg_replace('/\D+/', '', $query);
        $key    = (string) config('app.key');

        $q = Beneficiary::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($query, $digits, $key) {
                // Nome (LIKE — nome nao e encrypted no Beneficiary)
                $q->where('name', 'like', "%{$query}%");

                // CPF/NIS via bidx (so tenta se query parece documento)
                if (strlen($digits) === 11) {
                    $bidxDigits = hash_hmac('sha256', $digits, $key);
                    // Fallback: alguns cadastros historicos gravaram COM mascara,
                    // entao o bidx e da string mascarada. Calcula essa variante.
                    $masked      = $this->maskCpf($digits);
                    $bidxMasked  = hash_hmac('sha256', $masked, $key);

                    $q->orWhere('cpf_bidx', $bidxDigits)
                      ->orWhere('cpf_bidx', $bidxMasked)
                      ->orWhere('nis_bidx', $bidxDigits)
                      ->orWhere('nis_bidx', $bidxMasked);
                }
            })
            ->limit($limit);

        return $q->get();
    }

    /**
     * Formata 11 digitos como "123.456.789-00". Usado tambem em exibicao
     * mascarada segura ("123.***.***-00") sem vazar dados no meio.
     */
    public function maskCpf(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits);
        if (strlen($digits) !== 11) {
            return $digits;
        }
        return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
    }

    /**
     * Retorna CPF ofuscado pra exibicao publica: 123.***.***-45. Aceita entrada
     * com ou sem mascara.
     */
    public function obfuscateCpf(?string $cpf): string
    {
        if (!$cpf) return '';
        $digits = preg_replace('/\D+/', '', $cpf);
        if (strlen($digits) !== 11) return $cpf;
        return substr($digits, 0, 3) . '.***.***-' . substr($digits, 9, 2);
    }
}
