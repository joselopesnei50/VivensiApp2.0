<?php

namespace App\Jobs;

use App\Services\PagSeguroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\Tenant;

class HandlePagSeguroWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationCode;
    protected $notificationType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notificationCode, $notificationType)
    {
        $this->notificationCode = $notificationCode;
        $this->notificationType = $notificationType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PagSeguroService $pagSeguroService)
    {
        if ($this->notificationType !== 'transaction') {
            return; // We only care about transactions for now
        }

        // 1. Verify with PagSeguro API
        $transactionData = $pagSeguroService->checkNotification($this->notificationCode);

        if (!$transactionData) {
            Log::error("PagSeguro: Could not retrieve details for notification {$this->notificationCode}");
            return;
        }

        $status = $transactionData['status'] ?? null;
        $reference = $transactionData['reference'] ?? null; // Should contain our internal ID or Tenant ID
        $code = $transactionData['code'] ?? null; // Transaction ID in PagSeguro

        Log::info("PagSeguro Processing: Code: {$code}, Status: {$status}, Ref: {$reference}");

        // 2. Update Logic
        // Statuses: 1:Awaiting, 2:Analysis, 3:Paid, 4:Available, 5:Dispute, 6:Refunded, 7:Cancelled
        
        // Find transaction by PagSeguro Code (external_id)
        // If not found, try to find by reference (stored in external_id initially?)
        // In our Controller, we updated external_id to be the Code. So we should find it by Code.
        $transaction = Transaction::where('external_id', $code)->first();

        if (!$transaction) {
            // Fallback: Code might be different? Try reference if we stored it elsewhere.
            // For now, log warning.
            Log::warning("PagSeguro Webhook: Transaction not found for code {$code} (Ref: {$reference})");
            return;
        }

        $newStatus = $transaction->status;
        
        switch ($status) {
            case '3': // Paid
            case '4': // Available
                $newStatus = 'paid';
                break;
            case '7': // Cancelled
                $newStatus = 'failed'; // or cancelled
                break;
            case '6': // Refunded
                $newStatus = 'refunded';
                break;
            default:
                // Keep current status or set to pending/processing
                break;
        }

        if ($newStatus !== $transaction->status) {
             $transaction->update(['status' => $newStatus]);
             Log::info("Transaction {$transaction->id} updated to {$newStatus}");
             
             // Activate Tenant Subscription if Income
             if ($newStatus === 'paid' && $transaction->type === 'income' && $transaction->tenant_id) {
                 $tenant = Tenant::find($transaction->tenant_id);
                 if ($tenant) {
                     // Extend subscription logic here...
                     // For example:
                     $tenant->update(['subscription_status' => 'active', 'trial_ends_at' => now()->addMonth()]);
                 }
             }
        }
    }
}
