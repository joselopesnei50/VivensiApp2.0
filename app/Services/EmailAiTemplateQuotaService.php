<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * Cota MENSAL de geracao de template de e-mail via IA por tenant.
 *
 * Diferente da AiCallQuotaService (diaria, generica) e da EmailQuotaService
 * (diaria, para disparo do Brevo), essa cota e:
 *  - Mensal (calendario do servidor, YYYY-MM)
 *  - Por tenant (compartilhada entre usuarios da mesma organizacao)
 *  - Persistente em tabela (auditavel) — nao cache
 *
 * Contador = COUNT de linhas em `email_ai_template_usage` WHERE tenant_id=? AND year_month=?.
 * Consumo = INSERT de 1 linha (audita user_id + timestamp).
 */
class EmailAiTemplateQuotaService
{
    public const DEFAULT_MONTHLY_LIMIT = 10;

    public function limitForTenant(int $tenantId): int
    {
        $override = SystemSetting::getValue('email_ai_monthly_quota');
        if ($override !== null && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }
        return self::DEFAULT_MONTHLY_LIMIT;
    }

    public function currentUsage(int $tenantId): int
    {
        return (int) DB::table('email_ai_template_usage')
            ->where('tenant_id', $tenantId)
            ->where('year_month', $this->currentYearMonth())
            ->count();
    }

    public function remaining(int $tenantId): int
    {
        return max(0, $this->limitForTenant($tenantId) - $this->currentUsage($tenantId));
    }

    public function hasQuota(int $tenantId): bool
    {
        return $this->currentUsage($tenantId) < $this->limitForTenant($tenantId);
    }

    public function consume(int $tenantId, ?int $userId = null): void
    {
        DB::table('email_ai_template_usage')->insert([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'year_month' => $this->currentYearMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function currentYearMonth(): string
    {
        return now()->format('Y-m');
    }
}
