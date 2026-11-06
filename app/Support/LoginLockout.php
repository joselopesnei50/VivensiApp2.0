<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Limites de tentativa de login (além do throttle:10,1 da rota, por IP).
 *
 * 1. Conta + IP: 5 erros → 15 min. Pega o ataque comum, de um lugar só.
 * 2. Só a conta (qualquer IP): 15 erros em 30 min → 30 min. Pega o ataque
 *    distribuído, que troca de IP a cada tentativa. É mais alto que o (1)
 *    para que alguém de fora não consiga travar a conta de um usuário à toa
 *    com poucas tentativas; e o dono sempre destrava redefinindo a senha.
 *
 * Os contadores valem também para e-mails que não existem, para não revelar
 * quais contas existem.
 */
class LoginLockout
{
    public const IP_MAX = 5;
    public const IP_DECAY = 900;          // 15 min

    public const ACCOUNT_MAX = 15;
    public const ACCOUNT_DECAY = 1800;    // 30 min

    public static function normalize(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    public static function ipKey(string $email, ?string $ip): string
    {
        return 'login:' . sha1(self::normalize($email)) . '|' . $ip;
    }

    public static function accountKey(string $email): string
    {
        return 'login-account:' . sha1(self::normalize($email));
    }

    /** Segundos até liberar, ou 0 se pode tentar. */
    public static function lockedFor(string $email, ?string $ip): int
    {
        $wait = 0;
        if (RateLimiter::tooManyAttempts(self::ipKey($email, $ip), self::IP_MAX)) {
            $wait = max($wait, RateLimiter::availableIn(self::ipKey($email, $ip)));
        }
        if (RateLimiter::tooManyAttempts(self::accountKey($email), self::ACCOUNT_MAX)) {
            $wait = max($wait, RateLimiter::availableIn(self::accountKey($email)));
        }
        return $wait;
    }

    public static function failed(string $email, ?string $ip): void
    {
        RateLimiter::hit(self::ipKey($email, $ip), self::IP_DECAY);
        $n = RateLimiter::hit(self::accountKey($email), self::ACCOUNT_DECAY);

        if ($n === self::ACCOUNT_MAX) {
            Log::warning('Login: conta bloqueada por excesso de tentativas (vários IPs)', [
                'conta' => substr(sha1(self::normalize($email)), 0, 12),
                'ultimo_ip' => $ip,
            ]);
        }
    }

    /**
     * Login certo: zera só o contador deste IP. O da conta continua contando
     * até expirar, senão um atacante distribuído ganharia tentativas novas
     * sempre que o dono entrasse.
     */
    public static function succeeded(string $email, ?string $ip): void
    {
        RateLimiter::clear(self::ipKey($email, $ip));
    }

    /** Senha redefinida pelo dono (link no e-mail): destrava a conta. */
    public static function reset(string $email, ?string $ip): void
    {
        RateLimiter::clear(self::accountKey($email));
        RateLimiter::clear(self::ipKey($email, $ip));
    }
}
