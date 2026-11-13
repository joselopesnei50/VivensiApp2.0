<?php

use App\Jobs\ProcessAbacatePayWebhook;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->abSecret = 'abacate-webhook-secret-test';
    $this->abHmac   = 'abacate-hmac-key-test';
    SystemSetting::setValue('abacatepay_webhook_secret', $this->abSecret);
    config(['services.abacatepay.hmac_key' => $this->abHmac]);
    // Webhook assinado como a AbacatePay faz (HMAC-SHA256 base64 do corpo bruto).
    $this->signed = function (array $payload) {
        $body = json_encode($payload);
        return $this->call('POST', '/api/abacatepay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X-WEBHOOK-SECRET' => $this->abSecret,
            'HTTP_X-WEBHOOK-SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $this->abHmac, true)),
        ], $body);
    };
});

// ── Secret validation ─────────────────────────────────────────────────────────

it('returns 401 when secret header is missing', function () {
    $this->postJson('/api/abacatepay/webhook', ['event' => 'BILLING_PAID'])
        ->assertStatus(401);
});

it('returns 401 when secret is invalid', function () {
    $this->postJson('/api/abacatepay/webhook', ['event' => 'BILLING_PAID'], [
        'X-Webhook-Secret' => 'wrong-secret',
    ])->assertStatus(401);
});

it('accepts secret via X-Webhook-Secret header (com assinatura)', function () {
    Queue::fake();
    $body = json_encode(['event' => 'BILLING_PAID']);
    $this->call('POST', '/api/abacatepay/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X-WEBHOOK-SECRET' => $this->abSecret,
        'HTTP_X-WEBHOOK-SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $this->abHmac, true)),
    ], $body)->assertStatus(200);
});

it('accepts secret via webhookSecret query string (com assinatura)', function () {
    Queue::fake();
    $body = json_encode(['event' => 'BILLING_PAID']);
    $this->call('POST', '/api/abacatepay/webhook?webhookSecret=' . $this->abSecret, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-WEBHOOK-SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $this->abHmac, true)),
    ], $body)->assertStatus(200);
});

// ── HMAC validation (obrigatória) ─────────────────────────────────────────────

it('returns 401 when HMAC signature is present but invalid', function () {
    $this->postJson('/api/abacatepay/webhook', ['event' => 'BILLING_PAID'], [
        'X-Webhook-Secret'    => $this->abSecret,
        'X-Webhook-Signature' => 'invalid-base64-signature',
    ])->assertStatus(401);
});

it('recusa (401) quando chega sem assinatura, mesmo com o secret certo', function () {
    Queue::fake();

    $this->postJson('/api/abacatepay/webhook', ['event' => 'BILLING_PAID', 'id' => 'bill_1'], [
        'X-Webhook-Secret' => $this->abSecret,
    ])->assertStatus(401);

    Queue::assertNothingPushed();
});

it('aceita a assinatura feita com a chave pública oficial, mesmo com valor de exemplo no .env', function () {
    Queue::fake();
    config(['services.abacatepay.hmac_key' => 'gere_uma_chave_aqui_exemplo']);
    $k = \App\Services\AbacatePayService::PUBLIC_HMAC_KEY;
    expect(strlen($k))->toBe(256);

    $body = json_encode(['event' => 'checkout.completed', 'id' => 'bill_pub']);
    $this->call('POST', '/api/abacatepay/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X-WEBHOOK-SECRET' => $this->abSecret,
        'HTTP_X-WEBHOOK-SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $k, true)),
    ], $body)->assertOk();
    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});

it('dispatches job when HMAC signature is valid', function () {
    Queue::fake();

    $body      = json_encode(['event' => 'BILLING_PAID', 'id' => 'bill_abc123']);
    $signature = base64_encode(hash_hmac('sha256', $body, $this->abHmac, true));

    $this->call('POST', '/api/abacatepay/webhook', [], [], [], [
        'CONTENT_TYPE'             => 'application/json',
        'HTTP_X-WEBHOOK-SECRET'    => $this->abSecret,
        'HTTP_X-WEBHOOK-SIGNATURE' => $signature,
    ], $body)
        ->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});

// ── Job dispatch ──────────────────────────────────────────────────────────────

it('dispatches job with BILLING_PAID event', function () {
    Queue::fake();

    ($this->signed)(['event' => 'BILLING_PAID', 'id' => 'bill_999', 'amount' => 15000])->assertStatus(200);

    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});

it('dispatches job with devMode flag', function () {
    Queue::fake();

    ($this->signed)(['event' => 'BILLING_PAID', 'devMode' => true, 'id' => 'dev_bill_1']);

    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});

it('handles missing event field without error', function () {
    Queue::fake();

    ($this->signed)(['id' => 'bill_unknown'])->assertStatus(200);

    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});
