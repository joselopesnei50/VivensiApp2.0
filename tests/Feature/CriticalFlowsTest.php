<?php

namespace Tests\Feature;

use Tests\TestCase;

class CriticalFlowsTest extends TestCase
{
    public function test_webhook_asaas_should_reject_requests_without_token()
    {
        // Precisamos configurar um token no sistema para testar a rejeição de tokens inválidos
        \App\Models\SystemSetting::setValue('asaas_webhook_token', 'my-secret-token');

        // Teste 1: Sem token (ou token errado) -> Deve retornar 401
        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['subscription' => 'sub_123']
        ]);
        
        $response->assertStatus(401);
    }
}
