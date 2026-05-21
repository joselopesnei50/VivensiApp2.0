<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Services\WhatsAppService;
use App\Services\WhatsappOutboundPolicy;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('startChat creates a new chat for unknown phone', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    $service = new WhatsAppService();
    $chat = $service->startChat($tenant->id, '+55 11 98765-4321', 'João Teste');

    expect($chat->wa_id)->toBe('5511987654321')
        ->and($chat->contact_name)->toBe('João Teste')
        ->and($chat->tenant_id)->toBe($tenant->id);
});

it('startChat reuses existing chat', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $existing = WhatsappChat::factory()->create([
        'tenant_id' => $tenant->id,
        'wa_id'     => '5511999990000',
    ]);

    $service = new WhatsAppService();
    $chat = $service->startChat($tenant->id, '5511999990000', 'Outro Nome');

    expect($chat->id)->toBe($existing->id);
});

it('startChat registers opt-in when consent is true', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    $service = new WhatsAppService();
    $chat = $service->startChat($tenant->id, '5521900000001', 'Maria', true);

    expect($chat->opt_in_at)->not->toBeNull();
});

it('startChat throws for empty phone', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    $service = new WhatsAppService();
    expect(fn() => $service->startChat($tenant->id, 'abc-xyz'))->toThrow(\InvalidArgumentException::class);
});

it('sendMessage throws RuntimeException when policy blocks send', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $chat   = WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'blocked_at' => now(),
    ]);
    $config = WhatsappConfig::factory()->create(['tenant_id' => $tenant->id]);

    $service = new WhatsAppService();
    expect(fn() => $service->sendMessage($chat, $config, 'Olá!'))
        ->toThrow(\RuntimeException::class);
});
