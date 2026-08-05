<?php

use App\Jobs\ProcessWhatsappAiResponse;
use App\Jobs\ProcessWhatsappWebhook;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Queue;

/**
 * P0.2 (2026-08-05) — regressão da condição de auto-reply do bot.
 * Antes: `!$chat->assigned_to || $chat->status === 'open'` — o "|| status=open"
 * deixava o bot voltar a responder após um release (que zera assigned_to
 * mas mantém status=open) ou até se o chat foi atribuído mas o status não
 * foi promovido. Agora: `!$chat->assigned_to && $chat->is_bot_active`.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bcSetup(array $chatOverrides = []): array
{
    Queue::fake();

    // Desliga o bot de atendimento genérico (SystemSetting) — o teste foca
    // no fluxo de dispatch da AI da tenant, não no FAQ/off-hours global.
    SystemSetting::updateOrCreate(['key' => 'atend_enabled'], ['value' => '0']);

    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $config = WhatsappConfig::factory()->create([
        'tenant_id'            => $tenant->id,
        'is_active'            => true,
        'ai_enabled'           => true,
        'meta_phone_number_id' => 'PHN_' . $tenant->id,
    ]);

    $chat = WhatsappChat::factory()->create(array_merge([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511999990000',
        'contact_phone' => '5511999990000',
        'status'        => 'open',
        'is_bot_active' => true,
        'assigned_to'   => null,
        'opt_in_at'     => now(),
    ], $chatOverrides));

    return [$config, $chat];
}

function bcPayload(WhatsappConfig $config, WhatsappChat $chat, string $text = 'oi'): array
{
    return [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'metadata' => ['phone_number_id' => $config->meta_phone_number_id],
                    'contacts' => [['profile' => ['name' => 'Cliente Teste']]],
                    'messages' => [[
                        'from' => $chat->wa_id,
                        'id'   => 'wamid.' . uniqid(),
                        'type' => 'text',
                        'text' => ['body' => $text],
                    ]],
                ],
            ]],
        ]],
    ];
}

it('dispatch AI quando chat está livre e bot ativo', function () {
    [$config, $chat] = bcSetup();

    (new ProcessWhatsappWebhook($config->id, bcPayload($config, $chat)))->handle();

    Queue::assertPushed(ProcessWhatsappAiResponse::class);
});

it('NÃO dispatch AI quando chat tem assigned_to (mesmo com status=open)', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $agente = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    Queue::fake();
    SystemSetting::updateOrCreate(['key' => 'atend_enabled'], ['value' => '0']);

    $config = WhatsappConfig::factory()->create([
        'tenant_id'            => $tenant->id,
        'is_active'            => true,
        'ai_enabled'           => true,
        'meta_phone_number_id' => 'PHN_' . $tenant->id,
    ]);

    $chat = WhatsappChat::factory()->create([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511988880000',
        'contact_phone' => '5511988880000',
        'status'        => 'open', // status ambíguo pós-release
        'is_bot_active' => true,
        'assigned_to'   => $agente->id,
        'opt_in_at'     => now(),
    ]);

    (new ProcessWhatsappWebhook($config->id, bcPayload($config, $chat)))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('NÃO dispatch AI quando is_bot_active=false (kill switch manual)', function () {
    [$config, $chat] = bcSetup([
        'is_bot_active' => false,
    ]);

    (new ProcessWhatsappWebhook($config->id, bcPayload($config, $chat)))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});
