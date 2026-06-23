<?php

namespace App\Observers;

use App\Models\RegraCompliance;
use App\Services\RegrasComplianceService;

/**
 * Invalida o cache do RegrasComplianceService em qualquer alteração na regra.
 * Cache TTL é curto (5 min), mas alterações jurídicas precisam refletir
 * imediato — não vamos esperar TTL pra valor de teto entrar em vigor.
 */
class RegraComplianceObserver
{
    public function __construct(private RegrasComplianceService $service)
    {
    }

    public function saved(RegraCompliance $regra): void
    {
        $this->service->invalidarCache($regra->chave);
    }

    public function deleted(RegraCompliance $regra): void
    {
        $this->service->invalidarCache($regra->chave);
    }
}
