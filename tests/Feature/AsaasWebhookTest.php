<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Jobs\HandleAsaasWebhook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AsaasWebhookTest extends TestCase
{
    // use RefreshDatabase; // Use with caution on local dev

    public function test_webhook_dispatches_job()
    {
        Queue::fake();

        $token = 'test-token';
        config(['services.asaas.webhook_token' => $token]);

        $payload = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id' => 'pay_123456',
                'customer' => 'cus_123456',
                'value' => 100.00,
                'paymentDate' => '2023-10-27',
                'invoiceNumber' => '001'
            ]
        ];

        $response = $this->postJson('/api/webhooks/asaas', $payload, [
            'asaas-access-token' => $token
        ]);

        $response->assertOk();
        Queue::assertPushed(HandleAsaasWebhook::class);
    }

    public function test_job_processes_payment_confirmed()
    {
        // 1. Setup Tenant
        $tenant = Tenant::factory()->create([
            'asaas_customer_id' => 'cus_test_123',
            'subscription_status' => 'pending'
        ]);

        $payload = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id' => 'pay_test_999',
                'customer' => 'cus_test_123',
                'value' => 150.00,
                'paymentDate' => now()->format('Y-m-d'),
                'invoiceNumber' => '999'
            ]
        ];

        // 2. Run Job
        $job = new HandleAsaasWebhook($payload);
        $job->handle();

        // 3. Assert Tenant Active
        $tenant->refresh();
        $this->assertEquals('active', $tenant->subscription_status);

        // 4. Assert Transaction Created
        $this->assertDatabaseHas('transactions', [
            'tenant_id' => $tenant->id,
            'external_id' => 'pay_test_999',
            'status' => 'paid',
            'amount' => 150.00
        ]);

        // Cleanup
        Transaction::where('external_id', 'pay_test_999')->delete();
        $tenant->delete();
    }
}
