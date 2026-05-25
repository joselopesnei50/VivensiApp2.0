<?php

use App\Jobs\HandlePagSeguroWebhook;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->psToken = 'pagseguro-test-token-abc';
    SystemSetting::setValue('pagseguro_webhook_token', $this->psToken);
});

// ── Authentication ────────────────────────────────────────────────────────────

it('returns 401 when no token provided', function () {
    $this->postJson('/api/webhooks/pagseguro', [
        'notificationCode' => 'ABC123',
        'notificationType' => 'transaction',
    ])->assertStatus(401);
});

it('returns 401 when token is invalid', function () {
    $this->postJson('/api/webhooks/pagseguro', [
        'notificationCode' => 'ABC123',
        'notificationType' => 'transaction',
    ], ['x-pagseguro-token' => 'wrong-token'])
        ->assertStatus(401);
});

it('accepts token via x-pagseguro-token header', function () {
    Queue::fake();

    $this->postJson('/api/webhooks/pagseguro', [
        'notificationCode' => 'CODE1',
        'notificationType' => 'transaction',
    ], ['x-pagseguro-token' => $this->psToken])
        ->assertStatus(200);
});

it('accepts token via query string', function () {
    Queue::fake();

    $this->postJson('/api/webhooks/pagseguro?token=' . $this->psToken, [
        'notificationCode' => 'CODE2',
        'notificationType' => 'transaction',
    ])->assertStatus(200);
});

// ── Payload validation ────────────────────────────────────────────────────────

it('returns 400 when notificationCode is missing', function () {
    $this->postJson('/api/webhooks/pagseguro', [
        'notificationType' => 'transaction',
    ], ['x-pagseguro-token' => $this->psToken])
        ->assertStatus(400);
});

it('returns 400 when notificationType is missing', function () {
    $this->postJson('/api/webhooks/pagseguro', [
        'notificationCode' => 'ABC123',
    ], ['x-pagseguro-token' => $this->psToken])
        ->assertStatus(400);
});

it('returns 400 when both fields are missing', function () {
    $this->postJson('/api/webhooks/pagseguro', [], ['x-pagseguro-token' => $this->psToken])
        ->assertStatus(400);
});

// ── Job dispatch ──────────────────────────────────────────────────────────────

it('dispatches HandlePagSeguroWebhook job with valid payload', function () {
    Queue::fake();

    $this->postJson('/api/webhooks/pagseguro', [
        'notificationCode' => 'TXN-XYZ-999',
        'notificationType' => 'transaction',
    ], ['x-pagseguro-token' => $this->psToken])
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);

    Queue::assertPushed(HandlePagSeguroWebhook::class);
});
