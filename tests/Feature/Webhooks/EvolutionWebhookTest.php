<?php

use App\Jobs\ProcessEvolutionWebhook;
use App\Models\Tenant;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function evoInstance(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $token  = Str::random(32);
    WhatsappInstance::create([
        'tenant_id'      => $tenant->id,
        'instance_name'  => 'test-instance',
        'instance_token' => $token,
        'status'         => 'open',
    ]);
    return [$tenant, $token];
}

// ── Token validation ──────────────────────────────────────────────────────────

it('returns 200 ignored for unknown token', function () {
    $this->postJson('/api/evo/webhook/totally-invalid-token-000', [])
        ->assertStatus(200)
        ->assertJson(['status' => 'ignored']);
});

it('dispatches job and returns 200 queued for valid token', function () {
    Queue::fake();
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", [
        'event' => 'messages.upsert',
        'data'  => ['key' => ['remoteJid' => '5511999999999@s.whatsapp.net']],
    ])
        ->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushedOn('whatsapp', ProcessEvolutionWebhook::class);
});

it('dispatches job on whatsapp queue for connection.update event', function () {
    Queue::fake();
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", ['event' => 'connection.update'])
        ->assertStatus(200);

    Queue::assertPushedOn('whatsapp', ProcessEvolutionWebhook::class);
});

it('dispatches job when payload uses type field instead of event', function () {
    Queue::fake();
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", ['type' => 'qrcode.updated'])
        ->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushedOn('whatsapp', ProcessEvolutionWebhook::class);
});

it('dispatches job even when neither event nor type present', function () {
    Queue::fake();
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", ['data' => []])
        ->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushedOn('whatsapp', ProcessEvolutionWebhook::class);
});

// ── HMAC validation (optional secret) ────────────────────────────────────────

it('returns 401 when secret configured and signature header missing', function () {
    config(['whatsapp.evolution_webhook_secret' => 'test-secret']);
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", ['event' => 'test'])
        ->assertStatus(401);
});

it('returns 401 when HMAC signature is invalid', function () {
    config(['whatsapp.evolution_webhook_secret' => 'test-secret']);
    [, $token] = evoInstance();

    $this->postJson("/api/evo/webhook/{$token}", ['event' => 'test'], [
        'x-webhook-hmac' => 'sha256=totally-wrong-signature',
    ])->assertStatus(401);
});

it('dispatches job when HMAC signature via x-webhook-hmac is valid', function () {
    Queue::fake();
    $secret = 'test-secret';
    config(['whatsapp.evolution_webhook_secret' => $secret]);
    [, $token] = evoInstance();

    $body      = json_encode(['event' => 'messages.upsert']);
    $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

    $this->call('POST', "/api/evo/webhook/{$token}", [], [], [], [
        'CONTENT_TYPE'        => 'application/json',
        'HTTP_X-WEBHOOK-HMAC' => $signature,
    ], $body)
        ->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushedOn('whatsapp', ProcessEvolutionWebhook::class);
});

it('accepts signature via x-hub-signature-256 header', function () {
    Queue::fake();
    $secret = 'test-secret';
    config(['whatsapp.evolution_webhook_secret' => $secret]);
    [, $token] = evoInstance();

    $body      = json_encode(['event' => 'test']);
    $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

    $this->call('POST', "/api/evo/webhook/{$token}", [], [], [], [
        'CONTENT_TYPE'               => 'application/json',
        'HTTP_X-HUB-SIGNATURE-256'   => $signature,
    ], $body)
        ->assertStatus(200);
});
