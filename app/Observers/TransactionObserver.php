<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    private function clearTenantCache(Transaction $transaction): void
    {
        if ($transaction->tenant_id) {
            Cache::forget("ngo_stats_{$transaction->tenant_id}");
        }
    }

    public function created(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('created', $transaction, null, $transaction->getAttributes());
    }

    public function updated(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $dirty = $transaction->getDirty();
        if (empty($dirty)) return;
        $this->log('updated', $transaction, $transaction->getOriginal(), $dirty);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('deleted', $transaction, $transaction->getAttributes(), null);
    }

    public function restored(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('restored', $transaction, null, $transaction->getAttributes());
    }

    public function forceDeleted(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('force_deleted', $transaction, $transaction->getAttributes(), null);
    }

    private function log(string $event, Transaction $transaction, ?array $old, ?array $new): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $transaction->tenant_id,
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => Transaction::class,
                'auditable_id'   => $transaction->id,
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
