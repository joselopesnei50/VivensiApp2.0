<?php

namespace App\Observers;

use App\Jobs\RecalcularConformidadeJob;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    private function clearTenantCache(Transaction $transaction): void
    {
        if ($transaction->tenant_id) {
            Cache::forget("ngo_stats_{$transaction->tenant_id}");
            $year = now()->year;
            Cache::forget("transparency_portal_{$transaction->tenant_id}_{$year}");
            Cache::forget("transparency_portal_{$transaction->tenant_id}_" . ($year - 1));
        }
    }

    public function created(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('created', $transaction, null, $transaction->getAttributes());
        $this->dispatchConformidade($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $dirty = $transaction->getDirty();
        if (empty($dirty)) return;
        $this->log('updated', $transaction, $transaction->getOriginal(), $dirty);
        $this->dispatchConformidade($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
        $this->log('deleted', $transaction, $transaction->getAttributes(), null);
        $this->dispatchConformidade($transaction);
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

    private function dispatchConformidade(Transaction $transaction): void
    {
        if ($transaction->tenant_id) {
            RecalcularConformidadeJob::dispatch($transaction->tenant_id);
        }
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
