<?php

use App\Jobs\ProcessCloudApiWebhook;
use App\Models\WhatsappChat;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;

/**
 * Fase 5.1 — Captura conversation+pricing do webhook Meta e cria
 * WhatsappConversation faturavel linkada a WhatsappMessage.
 */

// ── Helper ────────────────────────────────────────────────────────────────────

function makeStatusPayload(WhatsappInstance $instance, array $statusOverride = []): array
{
    return [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'statuses' => [array_merge([
                        'id'           => 'wamid.OUT_BILL_1',
                        'status'       => 'sent',
                        'recipient_id' => '5511999998888',
                        'conversation' => [
                            'id'                    => 'conv_meta_abc123',
                            'expiration_timestamp'  => (string) now()->addDay()->timestamp,
                            'origin'                => ['type' => 'utility'],
                        ],
                        'pricing' => [
                            'billable'      => true,
                            'pricing_model' => 'CBP',
                            'category'      => 'utility',
                        ],
                    ], $statusOverride)],
                ],
            ]],
        ]],
    ];
}

function makeOutboundMessage(WhatsappInstance $instance, string $messageId = 'wamid.OUT_BILL_1'): WhatsappMessage
{
    $chat = WhatsappChat::factory()->create(['tenant_id' => $instance->tenant_id]);
    return WhatsappMessage::create([
        'tenant_id'  => $instance->tenant_id,
        'chat_id'    => $chat->id,
        'message_id' => $messageId,
        'content'    => 'msg',
        'direction'  => 'outbound',
        'type'       => 'text',
        'status'     => 'sent',
    ]);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

it('cria WhatsappConversation quando webhook traz conversation+pricing', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $msg      = makeOutboundMessage($instance);

    (new ProcessCloudApiWebhook(makeStatusPayload($instance)))->handle();

    $conv = WhatsappConversation::withoutGlobalScope('tenant')
        ->where('meta_conversation_id', 'conv_meta_abc123')
        ->first();

    expect($conv)->not->toBeNull();
    expect($conv->category)->toBe('utility');
    expect($conv->origin_type)->toBe('utility');
    expect($conv->pricing_model)->toBe('CBP');
    expect($conv->is_billable)->toBeTrue();
    expect($conv->contact_wa_id)->toBe('5511999998888');
    expect($conv->country_code)->toBe('BR');
    // Utility BR = $0.0080 = 8000 micros
    expect($conv->cost_usd_micros)->toBe(8000);
});

it('linka WhatsappMessage ao WhatsappConversation criado', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $msg      = makeOutboundMessage($instance);

    (new ProcessCloudApiWebhook(makeStatusPayload($instance)))->handle();

    $msg->refresh();
    expect($msg->whatsapp_conversation_id)->not->toBeNull();
    expect($msg->meta_pricing_category)->toBe('utility');
});

it('reutiliza conversa existente (idempotente por meta_conversation_id)', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    makeOutboundMessage($instance, 'wamid.OUT_BILL_1');
    makeOutboundMessage($instance, 'wamid.OUT_BILL_2');

    // Primeiro status cria a conversa
    (new ProcessCloudApiWebhook(makeStatusPayload($instance)))->handle();

    // Segundo status (mesma conversa, outra mensagem)
    (new ProcessCloudApiWebhook(makeStatusPayload($instance, [
        'id' => 'wamid.OUT_BILL_2',
    ])))->handle();

    expect(WhatsappConversation::withoutGlobalScope('tenant')
        ->where('meta_conversation_id', 'conv_meta_abc123')
        ->count())->toBe(1);
});

it('categoria marketing custa mais que utility para BR', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    makeOutboundMessage($instance, 'wamid.OUT_MKT');

    $payload = makeStatusPayload($instance, [
        'id' => 'wamid.OUT_MKT',
        'conversation' => [
            'id'                   => 'conv_meta_mkt',
            'expiration_timestamp' => (string) now()->addDay()->timestamp,
            'origin'               => ['type' => 'marketing'],
        ],
        'pricing' => ['billable' => true, 'pricing_model' => 'CBP', 'category' => 'marketing'],
    ]);

    (new ProcessCloudApiWebhook($payload))->handle();

    $conv = WhatsappConversation::withoutGlobalScope('tenant')
        ->where('meta_conversation_id', 'conv_meta_mkt')
        ->first();

    // Marketing BR = $0.0625 = 62_500 micros
    expect($conv->cost_usd_micros)->toBe(62_500);
});

it('billable=false zera o custo mesmo com categoria paga', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    makeOutboundMessage($instance, 'wamid.OUT_FREE');

    $payload = makeStatusPayload($instance, [
        'id' => 'wamid.OUT_FREE',
        'conversation' => [
            'id'                   => 'conv_free',
            'expiration_timestamp' => (string) now()->addDay()->timestamp,
            'origin'               => ['type' => 'service'],
        ],
        'pricing' => ['billable' => false, 'pricing_model' => 'CBP', 'category' => 'service'],
    ]);

    (new ProcessCloudApiWebhook($payload))->handle();

    $conv = WhatsappConversation::withoutGlobalScope('tenant')
        ->where('meta_conversation_id', 'conv_free')
        ->first();

    expect($conv->is_billable)->toBeFalse();
    expect($conv->cost_usd_micros)->toBe(0);
});

it('nao cria conversation quando webhook nao traz pricing (retrocompatibilidade)', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $msg      = makeOutboundMessage($instance);

    // Status sem conversation/pricing (formato antigo)
    $payload = [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'statuses' => [['id' => 'wamid.OUT_BILL_1', 'status' => 'delivered']],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();

    expect(WhatsappConversation::withoutGlobalScope('tenant')->count())->toBe(0);
    // Mas status ainda deve atualizar
    expect($msg->fresh()->status)->toBe('delivered');
});

it('numero US derivado corretamente do recipient_id', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    makeOutboundMessage($instance, 'wamid.OUT_US');

    $payload = makeStatusPayload($instance, [
        'id'           => 'wamid.OUT_US',
        'recipient_id' => '14155551212',
    ]);

    (new ProcessCloudApiWebhook($payload))->handle();

    $conv = WhatsappConversation::withoutGlobalScope('tenant')
        ->where('meta_conversation_id', 'conv_meta_abc123')
        ->first();

    expect($conv->country_code)->toBe('US');
    // Utility US = $0.0085 = 8500 micros
    expect($conv->cost_usd_micros)->toBe(8500);
});
