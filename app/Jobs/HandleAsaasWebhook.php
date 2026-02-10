<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleAsaasWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $event = $this->payload['event'] ?? null;
        $payment = $this->payload['payment'] ?? [];

        if (!$event || empty($payment['id'])) {
            return;
        }

        \Illuminate\Support\Facades\Log::info("Processing Asaas Event: {$event}", ['payment_id' => $payment['id']]);

        // 1. Payment Confirmed
        if ($event === 'PAYMENT_CONFIRMED') {
            $this->handlePaymentConfirmed($payment);
        }

        // 2. Payment Overdue
        if ($event === 'PAYMENT_OVERDUE') {
            $this->handlePaymentOverdue($payment);
        }
    }

    protected function handlePaymentConfirmed($paymentData)
    {
        $customerId = $paymentData['customer'] ?? null;
        if (!$customerId) {
             \Illuminate\Support\Facades\Log::warning("Asaas Webhook: Payment confirmed without customer ID.", $paymentData);
             return;
        }

        $tenant = \App\Models\Tenant::where('asaas_customer_id', $customerId)->first();

        if (!$tenant) {
            \Illuminate\Support\Facades\Log::warning("Asaas Webhook: Tenant not found for customer {$customerId}");
            return;
        }

        // Check if transaction already exists (avoid duplicates)
        $transaction = \App\Models\Transaction::where('external_id', $paymentData['id'])->first();

        if (!$transaction) {
            $transaction = \App\Models\Transaction::create([
                'tenant_id' => $tenant->id,
                'external_id' => $paymentData['id'],
                'amount' => $paymentData['value'],
                'date' => $paymentData['paymentDate'] ?? $paymentData['dueDate'] ?? now(),
                'status' => 'paid',
                'type' => 'income',
                'description' => 'Mensalidade Vivensi - Fatura ' . ($paymentData['invoiceNumber'] ?? ''),
                'category_id' => null, // Or a default 'Subscription' category if available
            ]);
            \Illuminate\Support\Facades\Log::info("Transaction created via Webhook: {$transaction->id}");
        } else {
             // Just ensure it is paid
             if ($transaction->status !== 'paid') {
                 $transaction->update(['status' => 'paid']);
             }
        }

        // Activate Tenant
        if ($tenant->subscription_status !== 'active') {
            $tenant->update(['subscription_status' => 'active']);
            \Illuminate\Support\Facades\Log::info("Tenant {$tenant->id} activated via Webhook.");
        }
    }

    protected function handlePaymentOverdue($paymentData)
    {
        $customerId = $paymentData['customer'] ?? null;
        if (!$customerId) return;

        $tenant = \App\Models\Tenant::where('asaas_customer_id', $customerId)->first();

        if ($tenant) {
            // Check if user has other paid invoices? 
            // For MVP, if ANY payment is overdue, we suspend or warn.
            // But Asaas sends overdue for old slips too.
            // Let's check `subscription_status`.

            // If subscription is active, maybe we give a grace period or just mark as past_due.
            // Simple logic: Suspend.
            $tenant->update(['subscription_status' => 'suspended']);
            \Illuminate\Support\Facades\Log::info("Tenant {$tenant->id} suspended via Webhook (Overdue).");
        }
    }
}
