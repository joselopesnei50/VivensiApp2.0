<?php

use App\Models\Tenant;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Services\WhatsappOutboundPolicy;

/**
 * Prova de paridade: broadcast e chat individual bloqueiam exatamente os mesmos casos.
 * Ambos usam WhatsappOutboundPolicy::complianceStatus() como fonte única de verdade.
 */

function bcTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function bcChat(Tenant $tenant, array $attrs = []): WhatsappChat
{
    return WhatsappChat::factory()->create(array_merge(['tenant_id' => $tenant->id], $attrs));
}

function bcConfig(Tenant $tenant, array $attrs = []): WhatsappConfig
{
    return WhatsappConfig::factory()->create(array_merge(['tenant_id' => $tenant->id], $attrs));
}

// Simula a decisão do broadcast (sem rodar o job inteiro)
function broadcastDecision(WhatsappOutboundPolicy $policy, bool $requireOptIn, array $blacklistSet, WhatsappChat $chat): ?string
{
    return $policy->complianceStatus(
        $requireOptIn,
        $chat->opt_in_at,
        $chat->opt_out_at,
        $chat->blocked_at,
        isset($blacklistSet[$chat->wa_id])
    );
}

// Simula a decisão do chat individual (isolando compliance de throttle/janela de sessão)
function chatDecision(WhatsappOutboundPolicy $policy, WhatsappConfig $config, WhatsappChat $chat): ?string
{
    $config->enforce_24h_window = false;
    $reason = null;
    $code   = null;
    $policy->canSend($config, $chat, false, $reason, $code);
    $complianceCodes = ['CONTACT_BLOCKED', 'CONTACT_OPTOUT', 'CONTACT_BLACKLISTED', 'OPTIN_REQUIRED'];
    return in_array($code, $complianceCodes) ? $code : null;
}

it('[paridade] ambos bloqueiam contato com blocked_at', function () {
    $tenant = bcTenant();
    $chat   = bcChat($tenant, ['blocked_at' => now()]);
    $config = bcConfig($tenant);
    $policy = new WhatsappOutboundPolicy();

    $broadcast = broadcastDecision($policy, false, [], $chat);
    $chat_ind  = chatDecision($policy, $config, $chat);

    expect($broadcast)->toBe('CONTACT_BLOCKED')
        ->and($chat_ind)->toBe('CONTACT_BLOCKED')
        ->and($broadcast)->toBe($chat_ind);
});

it('[paridade] ambos bloqueiam contato com opt_out_at', function () {
    $tenant = bcTenant();
    $chat   = bcChat($tenant, ['opt_out_at' => now()->subDay()]);
    $config = bcConfig($tenant);
    $policy = new WhatsappOutboundPolicy();

    $broadcast = broadcastDecision($policy, false, [], $chat);
    $chat_ind  = chatDecision($policy, $config, $chat);

    expect($broadcast)->toBe('CONTACT_OPTOUT')
        ->and($chat_ind)->toBe('CONTACT_OPTOUT')
        ->and($broadcast)->toBe($chat_ind);
});

it('[paridade] ambos bloqueiam contato na blacklist', function () {
    $tenant = bcTenant();
    $chat   = bcChat($tenant);
    $config = bcConfig($tenant);
    WhatsappBlacklist::create(['tenant_id' => $tenant->id, 'phone' => $chat->wa_id, 'reason' => 'test']);

    $policy       = new WhatsappOutboundPolicy();
    $blacklistSet = [$chat->wa_id => true];

    $broadcast = broadcastDecision($policy, false, $blacklistSet, $chat);
    $chat_ind  = chatDecision($policy, $config, $chat);

    expect($broadcast)->toBe('CONTACT_BLACKLISTED')
        ->and($chat_ind)->toBe('CONTACT_BLACKLISTED')
        ->and($broadcast)->toBe($chat_ind);
});

it('[paridade] ambos bloqueiam sem opt_in quando require_opt_in=true', function () {
    $tenant = bcTenant();
    $chat   = bcChat($tenant, ['opt_in_at' => null]);
    $config = bcConfig($tenant, ['require_opt_in' => true]);
    $policy = new WhatsappOutboundPolicy();

    $broadcast = broadcastDecision($policy, true, [], $chat);
    $chat_ind  = chatDecision($policy, $config, $chat);

    expect($broadcast)->toBe('OPTIN_REQUIRED')
        ->and($chat_ind)->toBe('OPTIN_REQUIRED')
        ->and($broadcast)->toBe($chat_ind);
});

it('[paridade] ambos liberam contato com opt_in e sem restrições', function () {
    $tenant = bcTenant();
    $chat   = bcChat($tenant, ['opt_in_at' => now()->subDay()]);
    $config = bcConfig($tenant, ['require_opt_in' => true]);
    $policy = new WhatsappOutboundPolicy();

    $broadcast = broadcastDecision($policy, true, [], $chat);
    $chat_ind  = chatDecision($policy, $config, $chat);

    expect($broadcast)->toBeNull()->and($chat_ind)->toBeNull();
});
