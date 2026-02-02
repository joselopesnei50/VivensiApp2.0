<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery;

class AsaasWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_confirms_payment_and_activates_tenant()
    {
        // 1. Arrange: Create Tenant and User (Manager)
        // Manual creation if Factory doesn't exist
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'document' => '00000000001',
            'subdomain' => 'test',
            'asaas_customer_id' => 'cus_123456',
            'subscription_status' => 'pending',
        ]);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'manager',
            'email' => 'manager@test.com',
            'name' => 'Manager Test'
        ]);

        // 2. Setup System Settings (Token)
        SystemSetting::create([
            'key' => 'asaas_webhook_token',
            'value' => 'secret_token_123',
            'group' => 'asaas'
        ]);

        // 3. Mock BrevoService to prevent real email sending
        $this->mock(BrevoService::class, function ($mock) use ($manager) {
            $mock->shouldReceive('sendPaymentConfirmedEmail')
                 ->once();
        });

        // 4. Act: Send Webhook Payload
        $payload = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'customer' => 'cus_123456',
                'subscription' => 'sub_987654',
                'value' => 100.00,
                'netValue' => 99.00,
            ]
        ];

        $response = $this->postJson('/api/webhooks/asaas', $payload, [
            'asaas-access-token' => 'secret_token_123'
        ]);

        // 5. Assert
        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        // Assert DB state: status updated to active
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'subscription_status' => 'active'
        ]);
    }

    public function test_webhook_fails_with_invalid_token()
    {
         SystemSetting::create([
            'key' => 'asaas_webhook_token',
            'value' => 'valid_token',
            'group' => 'asaas'
        ]);

        $response = $this->postJson('/api/webhooks/asaas', [], [
            'asaas-access-token' => 'wrong_token'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error', 'reason' => 'invalid_token']);
    }
    
    public function test_webhook_ignores_if_tenant_not_found()
    {
         SystemSetting::create([
            'key' => 'asaas_webhook_token',
            'value' => 'valid_token',
            'group' => 'asaas'
        ]);

        $payload = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'customer' => 'cus_non_existent',
                'subscription' => 'sub_987654',
            ]
        ];

        $response = $this->postJson('/api/webhooks/asaas', $payload, [
            'asaas-access-token' => 'valid_token'
        ]);

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error', 'reason' => 'tenant_not_found']);
    }
}
