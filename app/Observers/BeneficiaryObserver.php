<?php

namespace App\Observers;

use App\Models\Beneficiary;
use App\Jobs\GeocodeAddressJob;
use Illuminate\Support\Facades\Cache;

class BeneficiaryObserver
{
    public function saved(Beneficiary $beneficiary)
    {
        if ($beneficiary->isDirty('address') && !empty($beneficiary->address)) {
            GeocodeAddressJob::dispatch($beneficiary);
        }

        // Portal público de transparência cacheia 1h — invalidar para
        // refletir alteração imediatamente.
        self::forgetPortalCache((int) $beneficiary->tenant_id);
    }

    // created/updated: o log de auditoria é feito pelo trait Auditable do model
    // (com CPF, NIS e nascimento ocultos). Antes este observer gravava um
    // segundo registro para cada alteração.

    public function deleted(Beneficiary $beneficiary): void
    {
        self::forgetPortalCache((int) $beneficiary->tenant_id);
    }

    /**
     * Invalida o cache do portal público de transparência para um tenant.
     * Reusável: chamado também pelo FamilyMemberObserver.
     * Itera janela de anos (atual ± 5/+1) porque a agregação territorial
     * não é year-filtered e qualquer mudança afeta todas as views cacheadas.
     */
    public static function forgetPortalCache(int $tenantId): void
    {
        if ($tenantId <= 0) return;
        try {
            $thisYear = (int) now()->year;
            for ($y = $thisYear - 5; $y <= $thisYear + 1; $y++) {
                Cache::forget("transparency_portal_{$tenantId}_{$y}");
            }
        } catch (\Throwable) {
            // não deixar falha de cache derrubar a operação principal
        }
    }
}
