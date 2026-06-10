<?php

namespace App\Observers;

use App\Models\Beneficiary;
use App\Models\AuditLog;
use App\Jobs\GeocodeAddressJob;
use Illuminate\Support\Facades\Cache;

class BeneficiaryObserver
{
    private static array $sensitiveFields = ['cpf', 'nis', 'birth_date'];

    public function saved(Beneficiary $beneficiary)
    {
        if ($beneficiary->isDirty('address') && !empty($beneficiary->address)) {
            GeocodeAddressJob::dispatch($beneficiary);
        }

        // Portal público de transparência cacheia 1h — invalidar para
        // refletir alteração imediatamente.
        self::forgetPortalCache((int) $beneficiary->tenant_id);
    }

    public function created(Beneficiary $beneficiary): void
    {
        $this->log('created', $beneficiary, null, $this->sanitize($beneficiary->getAttributes()));
    }

    public function updated(Beneficiary $beneficiary): void
    {
        $dirty = $beneficiary->getDirty();
        if (empty($dirty)) return;
        $this->log('updated', $beneficiary, $this->sanitize($beneficiary->getOriginal()), $this->sanitize($dirty));
    }

    public function deleted(Beneficiary $beneficiary): void
    {
        $this->log('deleted', $beneficiary, $this->sanitize($beneficiary->getAttributes()), null);
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

    private function sanitize(array $data): array
    {
        return array_diff_key($data, array_flip(self::$sensitiveFields));
    }

    private function log(string $event, Beneficiary $beneficiary, ?array $old, ?array $new): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $beneficiary->tenant_id,
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => Beneficiary::class,
                'auditable_id'   => $beneficiary->id,
                'old_values'     => $old,
                'new_values'     => $new,
                'ip_address'     => optional(request())->ip(),
                'url'            => optional(request())->fullUrl(),
            ]);
        } catch (\Throwable) {
            // não deixar falha de audit derrubar a operação principal
        }
    }
}
