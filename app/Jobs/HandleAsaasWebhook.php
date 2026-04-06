<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleAsaasWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $event = data_get($this->payload, 'event');
        $paymentId = data_get($this->payload, 'payment.id');
        $customerId = data_get($this->payload, 'payment.customer');
        $value = (float) data_get($this->payload, 'payment.value', 0);
        $paymentDate = data_get($this->payload, 'payment.paymentDate');

        if (!$event || !$paymentId || !$customerId) {
            return;
        }

        if (!in_array($event, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'], true)) {
            return;
        }

        $tenant = Tenant::where('asaas_customer_id', $customerId)->first();
        if (!$tenant) {
            return;
        }

        $tenant->update([
            'subscription_status' => 'active',
        ]);

        Transaction::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'external_id' => $paymentId,
            ],
            [
                'amount' => $value,
                'description' => 'Pagamento Asaas (' . $paymentId . ')',
                'type' => 'income',
                'status' => 'paid',
                'date' => $paymentDate ? \Carbon\Carbon::parse($paymentDate) : now(),
            ]
        );
    }
}

