<?php

use App\Models\Tenant;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Services\WhatsappOutboundPolicy;

// ── helpers ───────────────────────────────────────────────────────────────────

function makeChat(array $attrs = []): WhatsappChat
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return WhatsappChat::factory()->create(array_merge(['tenant_id' => $tenant->id], $attrs));
}

function makeConfig(WhatsappChat $chat, array $attrs = []): WhatsappConfig
{
    return WhatsappConfig::factory()->create(array_merge(['tenant_id' => $chat->tenant_id], $attrs));
}

// ── outbound_enabled = false ──────────────────────────────────────────────────

it('blocks when outbound is disabled', function () {
    $chat   = makeChat();
    $config = makeConfig($chat, ['outbound_enabled' => false]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('OUTBOUND_DISABLED');
});

it('allows AI response even when outbound is disabled', function () {
    $chat   = makeChat(['last_inbound_at' => now()->subMinutes(5)]);
    $config = makeConfig($chat, ['outbound_enabled' => false, 'enforce_24h_window' => false]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code, isAi: true);

    expect($result)->toBeTrue();
});

// ── blocked / opt-out ─────────────────────────────────────────────────────────

it('blocks when contact is blocked', function () {
    $chat   = makeChat(['blocked_at' => now()]);
    $config = makeConfig($chat);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('CONTACT_BLOCKED');
});

it('blocks when contact has opted out', function () {
    $chat   = makeChat(['opt_out_at' => now()->subDay()]);
    $config = makeConfig($chat);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('CONTACT_OPTOUT');
});

// ── blacklist ─────────────────────────────────────────────────────────────────

it('blocks when contact is on global blacklist', function () {
    $chat   = makeChat();
    $config = makeConfig($chat);

    WhatsappBlacklist::create([
        'tenant_id' => $chat->tenant_id,
        'phone'     => $chat->wa_id,
        'reason'    => 'Spam',
    ]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('CONTACT_BLACKLISTED');
});

// ── opt-in required ───────────────────────────────────────────────────────────

it('blocks when opt-in is required and not set', function () {
    $chat   = makeChat(['opt_in_at' => null]);
    $config = makeConfig($chat, ['require_opt_in' => true]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('OPTIN_REQUIRED');
});

it('allows when opt-in is required and set', function () {
    $chat   = makeChat([
        'opt_in_at'       => now()->subDay(),
        'last_inbound_at' => now()->subHour(),
    ]);
    $config = makeConfig($chat, ['require_opt_in' => true, 'enforce_24h_window' => false]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeTrue();
});

// ── 24h window ────────────────────────────────────────────────────────────────

it('blocks text message when 24h window is closed', function () {
    $chat   = makeChat(['last_inbound_at' => now()->subHours(25)]);
    $config = makeConfig($chat, [
        'enforce_24h_window'             => true,
        'allow_templates_outside_window' => false,
    ]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('OUTSIDE_24H_WINDOW');
});

it('allows template message when 24h window is closed but templates are allowed', function () {
    $chat   = makeChat(['last_inbound_at' => now()->subHours(30)]);
    $config = makeConfig($chat, [
        'enforce_24h_window'             => true,
        'allow_templates_outside_window' => true,
    ]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, isTemplate: true, reason: $reason, code: $code);

    expect($result)->toBeTrue();
});

it('allows text message within open 24h window', function () {
    $chat   = makeChat(['last_inbound_at' => now()->subHours(2)]);
    $config = makeConfig($chat, ['enforce_24h_window' => true]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeTrue();
});

// ── min delay ─────────────────────────────────────────────────────────────────

it('blocks when minimum delay between sends has not passed', function () {
    $chat   = makeChat([
        'last_inbound_at'  => now()->subHour(),
        'last_outbound_at' => now()->subSeconds(3),
    ]);
    $config = makeConfig($chat, [
        'enforce_24h_window'         => false,
        'min_outbound_delay_seconds' => 10,
    ]);

    $policy = new WhatsappOutboundPolicy();
    $result = $policy->canSend($config, $chat, false, $reason, $code);

    expect($result)->toBeFalse()
        ->and($code)->toBe('MIN_DELAY');
});

// ── recordSend updates last_outbound_at ───────────────────────────────────────

it('recordSend updates last_outbound_at on the chat', function () {
    $chat   = makeChat();
    $config = makeConfig($chat);

    expect($chat->last_outbound_at)->toBeNull();

    (new WhatsappOutboundPolicy())->recordSend($config, $chat);

    $chat->refresh();
    expect($chat->last_outbound_at)->not->toBeNull();
});
