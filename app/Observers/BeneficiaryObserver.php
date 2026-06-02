<?php

namespace App\Observers;

use App\Models\Beneficiary;
use App\Models\AuditLog;
use App\Jobs\GeocodeAddressJob;

class BeneficiaryObserver
{
    private static array $sensitiveFields = ['cpf', 'nis', 'birth_date'];

    public function saved(Beneficiary $beneficiary)
    {
        if ($beneficiary->isDirty('address') && !empty($beneficiary->address)) {
            GeocodeAddressJob::dispatch($beneficiary);
        }
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
