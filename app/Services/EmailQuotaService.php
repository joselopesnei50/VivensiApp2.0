<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * EmailQuotaService — Fase 2, item 3.3 do roadmap.
 *
 * Cota diária de e-mails por tenant. Cobre apenas envios em massa
 * (campanhas via Brevo). Transacionais críticos (2FA, reset de senha,
 * alertas) NÃO passam por aqui — continuam usando BrevoService::sendEmail
 * direto.
 *
 * Contador fica no Cache (driver Redis em prod) com chave única por
 * (tenant, dia) e TTL de 26h pra cobrir fuso horário e desligar ao final
 * do dia sem cron de reset.
 */
class EmailQuotaService
{
    public const DEFAULT_QUOTA = 50;

    public function getQuota(Tenant $tenant): int
    {
        $quota = $tenant->daily_email_quota ?? self::DEFAULT_QUOTA;
        return max(0, (int) $quota);
    }

    public function getSentToday(Tenant $tenant): int
    {
        return max(0, (int) Cache::get($this->key($tenant), 0));
    }

    public function getRemainingToday(Tenant $tenant): int
    {
        return max(0, $this->getQuota($tenant) - $this->getSentToday($tenant));
    }

    public function wouldExceed(Tenant $tenant, int $count): bool
    {
        if ($count <= 0) {
            return false;
        }
        return ($this->getSentToday($tenant) + $count) > $this->getQuota($tenant);
    }

    /**
     * Tenta consumir $count e-mails da cota do dia. Se não cabe, retorna
     * false SEM incrementar. Se cabe, incrementa e retorna true.
     *
     * Pequena race window: dois requests concorrentes podem ler o mesmo
     * "sent" antes de qualquer um incrementar — risco aceitável pra
     * disparos de campanha (humanos, raramente paralelos no mesmo segundo).
     * Pra batch paralelo evoluir pra Lua script no Redis.
     */
    public function tryConsume(Tenant $tenant, int $count = 1): bool
    {
        if ($count <= 0) {
            return true;
        }
        if ($this->wouldExceed($tenant, $count)) {
            return false;
        }

        $key = $this->key($tenant);
        // Garante a existência da chave com TTL antes de incrementar — sem
        // isso o Cache::increment não recebe TTL e a chave fica imortal.
        Cache::add($key, 0, $this->ttlSeconds());
        Cache::increment($key, $count);

        return true;
    }

    /**
     * Devolve $count e-mails à cota — usar quando o envio falha após o
     * consume (rollback compensatório, não atômico).
     */
    public function refund(Tenant $tenant, int $count): void
    {
        if ($count <= 0) {
            return;
        }
        $key  = $this->key($tenant);
        $atual = (int) Cache::get($key, 0);
        $novo  = max(0, $atual - $count);
        if ($novo === 0) {
            Cache::forget($key);
            return;
        }
        Cache::put($key, $novo, $this->ttlSeconds());
    }

    public function key(Tenant $tenant): string
    {
        return sprintf(
            'email_quota:tenant:%d:%s',
            $tenant->id,
            Carbon::now()->toDateString()
        );
    }

    private function ttlSeconds(): int
    {
        // 26h cobre fuso horário e garante reset natural sem cron.
        return 60 * 60 * 26;
    }
}
