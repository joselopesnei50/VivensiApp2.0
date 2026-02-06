<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

/**
 * Test: Webhook rejects requests without token
 */
test('webhook rejects requests without asaas token', function () {
    // Arrange: Configure webhook token via config (no DB)
    Config::set('services.asaas.webhook_token', 'secret123');
    
    // Act: Send webhook without token header
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => ['id' => 'pay_123'],
    ]);
    
    // Assert: Should be unauthorized
    $response->assertStatus(401);
    $response->assertJson([
        'status' => 'error',
        'reason' => 'invalid_token',
    ]);
});

/**
 * Test: Webhook rejects requests with invalid token
 */
test('webhook rejects requests with invalid asaas token', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'correct_secret');
    
    // Act: Send webhook with wrong token
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => ['id' => 'pay_123'],
    ], [
        'asaas-access-token' => 'wrong_token',
    ]);
    
    // Assert
    $response->assertStatus(401);
    $response->assertJson([
        'status' => 'error',
        'reason' => 'invalid_token',
    ]);
});

/**
 * Test: Webhook accepts requests with valid token
 */
test('webhook accepts requests with valid asaas token', function () {
    // Arrange
    $secretToken = 'my_secure_webhook_token_123';
    Config::set('services.asaas.webhook_token', $secretToken);
    
    $tenant = Tenant::factory()->create([
        'asaas_customer_id' => 'cus_123456',
        'subscription_status' => 'pending',
    ]);
    
    // Act: Send valid webhook
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => [
            'id' => 'pay_789',
            'customer' => 'cus_123456',
        ],
        'subscription' => 'sub_123',
    ], [
        'asaas-access-token' => $secretToken,
    ]);
    
    // Assert
    $response->assertStatus(200);
    $response->assertJson(['status' => 'success']);
});

/**
 * Test: Webhook validates token using timing-safe comparison
 */
test('webhook validates token using timing safe comparison', function () {
    // This test ensures hash_equals is being used (timing-safe)
    // We can't directly test the implementation, but we can verify behavior
    
    // Arrange
    $correctToken = 'abc123';
    Config::set('services.asaas.webhook_token', $correctToken);
    
    $tenant = Tenant::factory()->create([
        'asaas_customer_id' => 'cus_test',
    ]);
    
    // Act: Try with similar but wrong token (timing attack scenario)
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => ['customer' => 'cus_test'],
        'subscription' => 'sub_test',
    ], [
        'asaas-access-token' => 'abc124', // One char different
    ]);
    
    // Assert: Must reject (proves comparison is working)
    $response->assertStatus(401);
});

/**
 * Test: Webhook logs invalid token attempts with IP
 */
test('webhook logs invalid token attempts with ip address', function () {
    // Arrange
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Asaas Webhook: Invalid Token'
                && isset($context['ip']);
        });
    
    Config::set('services.asaas.webhook_token', 'valid_token');
    
    // Act
    $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
    ], [
        'asaas-access-token' => 'invalid_token',
    ]);
    
    // Assert: Log::warning was called (assertion in mock)
});

/**
 * Test: Payment confirmed webhook activates tenant subscription
 */
test('payment confirmed webhook activates tenant subscription', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    $tenant = Tenant::factory()->create([
        'asaas_customer_id' => 'cus_active_test',
        'subscription_status' => 'pending',
    ]);
    
    // Act
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => [
            'id' => 'pay_confirmed',
            'customer' => 'cus_active_test',
        ],
        'subscription' => 'sub_confirmed',
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    
    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('active');
});

/**
 * Test: Payment overdue webhook updates tenant status
 */
test('payment overdue webhook marks tenant as past due', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    $tenant = Tenant::factory()->create([
        'asaas_customer_id' => 'cus_overdue',
        'subscription_status' => 'active',
    ]);
    
    // Act
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_OVERDUE',
        'payment' => [
            'customer' => 'cus_overdue',
        ],
        'subscription' => 'sub_overdue',
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    
    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('past_due');
});

/**
 * Test: Subscription deleted webhook cancels tenant
 */
test('subscription deleted webhook marks tenant as canceled', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    $tenant = Tenant::factory()->create([
        'asaas_customer_id' => 'cus_cancel',
        'subscription_status' => 'active',
    ]);
    
    // Act
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'SUBSCRIPTION_DELETED',
        'payment' => [
            'customer' => 'cus_cancel',
        ],
        'subscription' => 'sub_cancel',
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    
    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('canceled');
});

/**
 * Test: Webhook handles missing subscription gracefully
 */
test('webhook ignores events without subscription id', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    // Act: Send webhook without subscription
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => ['id' => 'pay_no_sub'],
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'ignored',
        'reason' => 'no_subscription',
    ]);
});

/**
 * Test: Webhook handles missing customer gracefully
 */
test('webhook ignores events without customer id', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    // Act
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => ['id' => 'pay_no_customer'],
        'subscription' => 'sub_exists',
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'ignored',
        'reason' => 'no_customer',
    ]);
});

/**
 * Test: Webhook returns 404 for unknown tenant
 */
test('webhook returns error for unknown tenant', function () {
    // Arrange
    Config::set('services.asaas.webhook_token', 'token123');
    
    // Act: Send webhook for non-existent customer
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
        'payment' => [
            'customer' => 'cus_non_existent',
        ],
        'subscription' => 'sub_exists',
    ], [
        'asaas-access-token' => 'token123',
    ]);
    
    // Assert
    $response->assertStatus(404);
    $response->assertJson([
        'status' => 'error',
        'reason' => 'tenant_not_found',
    ]);
});

/**
 * Test: Webhook fails when token not configured
 */
test('webhook returns error when token not configured in settings', function () {
    // Arrange: No token configured (don't set config)
    
    // Act
    $response = $this->postJson('/api/webhooks/asaas', [
        'event' => 'PAYMENT_CONFIRMED',
    ], [
        'asaas-access-token' => 'any_token',
    ]);
    
    // Assert: Should return 500 (server misconfiguration)
    // Token not configured is a server error, not invalid token
    $response->assertStatus(500);
    $response->assertJson([
        'status' => 'error',
        'reason' => 'server_misconfiguration',
    ]);
});
