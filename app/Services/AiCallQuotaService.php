<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Cota diaria de chamadas de IA por tenant.
 *
 * Contador vive em Cache com TTL alinhado ao fim do dia UTC. Serve para
 * proteger a conta DeepSeek/Gemini de abuso: um tenant nao consegue drenar
 * o saldo do controlador se um usuario/webhook malicioso disparar em loop.
 *
 * Diferente do rate limiter web_ai (10/min por USUARIO): esse teto e por
 * TENANT, cobre jobs em background (qualificacao automatica de conversas),
 * e conta chamada por chamada — nao por request HTTP.
 *
 * Bypass: se $tenantId === null (contexto sem tenant, ex.: Bot Bruno em
 * sandbox super admin), a cota nao e verificada.
 */
class AiCallQuotaService
{
    public const DEFAULT_DAILY_LIMIT = 500;

    public function limitForTenant(int $tenantId): int
    {
        $override = SystemSetting::getValue('ai_daily_quota_per_tenant');
        if ($override !== null && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }
        return self::DEFAULT_DAILY_LIMIT;
    }

    /**
     * Retorna true se o tenant AINDA tem cota disponivel para MAIS uma chamada.
     * NAO consome — quem consome e consume().
     */
    public function hasQuota(int $tenantId): bool
    {
        return $this->currentCount($tenantId) < $this->limitForTenant($tenantId);
    }

    /**
     * Consome 1 chamada. Retorna o novo contador. Deve ser chamado antes
     * de disparar a request pra API — se retornar valor > limite, aborta.
     */
    public function consume(int $tenantId): int
    {
        $key = $this->cacheKey($tenantId);
        // TTL ate fim do dia + margem — Cache::increment nao aceita TTL direto,
        // por isso a inicializacao vai via add().
        Cache::add($key, 0, $this->secondsUntilEndOfDay() + 60);
        return (int) Cache::increment($key);
    }

    public function currentCount(int $tenantId): int
    {
        return (int) Cache::get($this->cacheKey($tenantId), 0);
    }

    private function cacheKey(int $tenantId): string
    {
        return 'ai_quota:tenant:' . $tenantId . ':day:' . now()->utc()->format('Y-m-d');
    }

    private function secondsUntilEndOfDay(): int
    {
        return (int) now()->utc()->endOfDay()->diffInSeconds(now()->utc());
    }
}
