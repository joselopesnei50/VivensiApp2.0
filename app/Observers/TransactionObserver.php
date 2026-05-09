<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    /**
     * Clear the dashboard stats cache for the tenant.
     */
    private function clearTenantCache(Transaction $transaction)
    {
        if ($transaction->tenant_id) {
            Cache::forget("ngo_stats_{$transaction->tenant_id}");
        }
    }

    /**
     * Handle the Transaction "saved" event.
     * (Triggered on created and updated)
     */
    public function saved(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        $this->clearTenantCache($transaction);
    }
}
