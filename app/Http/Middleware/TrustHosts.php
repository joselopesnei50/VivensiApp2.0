<?php

namespace App\Http\Middleware;

use App\Models\LandingPage;
use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Support\Facades\Cache;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        // Whitelist base: vivensi.app.br + subdominios.
        $base = [$this->allSubdomainsOfApplicationUrl()];

        // Custom domains ativos das landing pages (Fase 3 do add-on).
        // Sem esses no whitelist, Symfony retorna 404 pra Host arbitrario
        // antes de chegar no middleware ResolveCustomDomainLanding.
        // Cache 60s pra nao rodar SELECT em toda request. Novo dominio
        // ativado espera ate 1min pra propagar no cache.
        $custom = Cache::remember('trust_hosts_custom_domains', 60, function () {
            try {
                return LandingPage::withoutGlobalScopes()
                    ->where('custom_domain_status', 'active')
                    ->whereNotNull('custom_domain')
                    ->pluck('custom_domain')
                    ->all();
            } catch (\Throwable $e) {
                return []; // DB indisponivel — fail-safe pra whitelist base
            }
        });

        return array_merge($base, $custom);
    }
}
