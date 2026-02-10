<?php

namespace Tests\Feature;

use Tests\TestCase;
use UnityEngine\Http\Response;

class AsaasWebhookSecurityTest extends TestCase
{
    public function test_webhook_rejects_request_without_token()
    {
        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_123']
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_request_with_invalid_token()
    {
        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_CONFIRMED',
        ], [
            'asaas-access-token' => 'invalid-token'
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_request_with_valid_token()
    {
        // Define a token for testing (mocking env if needed or setting it specifically)
        $validToken = 'test-secret-token';
        config(['services.asaas.webhook_token' => $validToken]);

        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_123']
        ], [
            'asaas-access-token' => $validToken
        ]);

        // Expect 200 OK
        $response->assertStatus(200);
    }
}
