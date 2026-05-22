<?php

namespace App\Services;

/**
 * Pure-PHP TOTP (RFC 6238) — no external package required.
 * Compatible with Google Authenticator, Authy, 1Password.
 */
class TotpService
{
    private const DIGITS   = 6;
    private const PERIOD   = 30;
    private const WINDOW   = 1;   // accept ±1 period for clock skew
    private const ALGO     = 'sha1';

    public function generateSecret(): string
    {
        $bytes = random_bytes(20);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public function getQrCodeUrl(string $label, string $secret, string $issuer = 'Vivensi'): string
    {
        $label  = rawurlencode($issuer . ':' . $label);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        $otpauth = "otpauth://totp/{$label}?{$params}";

        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauth);
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $timestamp = (int) floor(time() / self::PERIOD);
        $key       = $this->base32Decode($secret);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if ($this->hotp($key, $timestamp + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    private function hotp(string $key, int $counter): string
    {
        $msg  = pack('J', $counter);
        $hash = hash_hmac(self::ALGO, $msg, $key, true);
        $off  = ord($hash[-1]) & 0x0F;
        $val  = ((ord($hash[$off]) & 0x7F) << 24)
              | ((ord($hash[$off + 1]) & 0xFF) << 16)
              | ((ord($hash[$off + 2]) & 0xFF) << 8)
              | (ord($hash[$off + 3]) & 0xFF);

        return str_pad((string)($val % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $input   = strtoupper(str_replace(['-', '_'], ['', ''], $input));
        $chars   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output  = '';
        $bits    = 0;
        $bitBuf  = 0;

        foreach (str_split($input) as $char) {
            $pos = strpos($chars, $char);
            if ($pos === false) continue;
            $bitBuf = ($bitBuf << 5) | $pos;
            $bits  += 5;
            if ($bits >= 8) {
                $bits  -= 8;
                $output .= chr(($bitBuf >> $bits) & 0xFF);
            }
        }

        return $output;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn() => strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4))),
            range(1, $count)
        );
    }
}
