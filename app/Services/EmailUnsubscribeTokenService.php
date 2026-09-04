<?php

namespace App\Services;

/**
 * Gera e valida tokens opacos pro fluxo de descadastro de campanhas de e-mail.
 *
 * Formato: base64url(email|tenant_id) . '.' . base64url(HMAC-SHA256)
 * Deterministico — mesmo email/tenant sempre gera o mesmo token. Nao expira
 * (LGPD art. 18: revogar consentimento nao pode ter prazo).
 */
class EmailUnsubscribeTokenService
{
    public function generate(string $email, int $tenantId): string
    {
        $payload = mb_strtolower(trim($email)) . '|' . $tenantId;
        $sig     = hash_hmac('sha256', $payload, $this->secret(), true);

        return $this->b64url($payload) . '.' . $this->b64url($sig);
    }

    /**
     * @return array{email: string, tenant_id: int}|null
     */
    public function parse(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return null;

        $payload = $this->b64urlDecode($parts[0]);
        $sig     = $this->b64urlDecode($parts[1]);
        if ($payload === null || $sig === null) return null;

        $expected = hash_hmac('sha256', $payload, $this->secret(), true);
        if (!hash_equals($expected, $sig)) return null;

        $chunks = explode('|', $payload, 2);
        if (count($chunks) !== 2) return null;

        $email    = mb_strtolower(trim($chunks[0]));
        $tenantId = (int) $chunks[1];
        if ($tenantId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return null;

        return ['email' => $email, 'tenant_id' => $tenantId];
    }

    private function secret(): string
    {
        $key = config('app.key');
        if (str_starts_with((string) $key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        return (string) $key;
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $encoded): ?string
    {
        $padded = strtr($encoded, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $decoded = base64_decode($padded, true);
        return $decoded === false ? null : $decoded;
    }
}
