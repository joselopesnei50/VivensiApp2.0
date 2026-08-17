<?php

use App\Jobs\ProcessCloudApiWebhook;
use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * Regressao 2026-08-17: mensagens inbound via Cloud API oficial nao
 * disparavam ProcessWhatsappAiResponse, deixando Bruno (bot vendedor) e
 * Bruce (bot atendimento) silenciosos. O Evolution job ja disparava; o
 * Cloud job so persistia. Fix: replicar dispatch com mesmos guards.
 */

function buildCloudSetup(array $configOverrides = []): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    $config = WhatsappConfig::create(array_merge([
        'tenant_id'       => $tenant->id,
        'is_active'       => true,
        'ai_enabled'      => true,
        'ai_provider'     => 'gemini',
        'outbound_enabled' => true,
        'require_opt_in'  => false,
    ], $configOverrides));

    $instance = WhatsappInstance::create([
        'tenant_id'       => $tenant->id,
        'instance_name'   => 'test_cloud_' . uniqid(),
        'instance_token'  => bin2hex(random_bytes(16)),
        'provider'        => WhatsappInstance::PROVIDER_CLOUD_API,
        'phone_number_id' => '99999000011',
        'is_active'       => true,
    ]);

    return compact('tenant', 'config', 'instance');
}

function cloudInboundPayload(string $phoneNumberId, string $text, string $wa = '5511999990000', string $mid = null): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry'  => [[
            'id'      => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata'          => ['phone_number_id' => $phoneNumberId, 'display_phone_number' => '5511111111111'],
                    'contacts'          => [['profile' => ['name' => 'Cliente'], 'wa_id' => $wa]],
                    'messages'          => [[
                        'id'   => $mid ?? ('wamid.TEST' . uniqid()),
                        'from' => $wa,
                        'type' => 'text',
                        'text' => ['body' => $text],
                    ]],
                ],
            ]],
        ]],
    ];
}

it('inbound text dispara ProcessWhatsappAiResponse quando ai_enabled=true', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup();

    (new ProcessCloudApiWebhook(cloudInboundPayload($inst->phone_number_id, 'oi Bruno')))
        ->handle();

    Queue::assertPushed(ProcessWhatsappAiResponse::class, 1);
    expect(WhatsappMessage::count())->toBe(1);
});

it('NAO dispara AI quando ai_enabled=false', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup(['ai_enabled' => false]);

    (new ProcessCloudApiWebhook(cloudInboundPayload($inst->phone_number_id, 'oi')))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
    expect(WhatsappMessage::count())->toBe(1); // ainda persiste
});

it('NAO dispara AI quando chat tem assigned_to (atendente humano)', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['tenant' => $tenant, 'instance' => $inst] = buildCloudSetup();

    // FK real no assigned_to → precisa de um user existente
    $agent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    WhatsappChat::create([
        'tenant_id'      => $tenant->id,
        'wa_id'          => '5511777770000',
        'contact_name'   => 'x',
        'contact_phone'  => '5511777770000',
        'assigned_to'    => $agent->id,
        'is_bot_active'  => true,
    ]);

    (new ProcessCloudApiWebhook(cloudInboundPayload($inst->phone_number_id, 'oi', '5511777770000')))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('NAO dispara AI quando is_bot_active=false', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['tenant' => $tenant, 'instance' => $inst] = buildCloudSetup();

    WhatsappChat::create([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511666660000',
        'contact_name'  => 'x',
        'contact_phone' => '5511666660000',
        'is_bot_active' => false,
    ]);

    (new ProcessCloudApiWebhook(cloudInboundPayload($inst->phone_number_id, 'oi', '5511666660000')))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('NAO dispara AI para tipos sem texto (sticker, reaction, location)', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup();

    $stickerPayload = cloudInboundPayload($inst->phone_number_id, '');
    $stickerPayload['entry'][0]['changes'][0]['value']['messages'][0]['type']    = 'sticker';
    $stickerPayload['entry'][0]['changes'][0]['value']['messages'][0]['sticker'] = ['id' => 'sid1'];
    unset($stickerPayload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

    (new ProcessCloudApiWebhook($stickerPayload))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('NAO dispara AI para imagem sem caption (só placeholder [imagem])', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup();

    $imgPayload = cloudInboundPayload($inst->phone_number_id, '');
    $imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['type']  = 'image';
    $imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['image'] = ['id' => 'img1']; // sem caption
    unset($imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

    (new ProcessCloudApiWebhook($imgPayload))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('DISPARA AI quando imagem vem com caption real', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup();

    $imgPayload = cloudInboundPayload($inst->phone_number_id, '');
    $imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['type']  = 'image';
    $imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['image'] = [
        'id'      => 'img2',
        'caption' => 'olha esse comprovante de pagamento',
    ];
    unset($imgPayload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

    (new ProcessCloudApiWebhook($imgPayload))->handle();

    Queue::assertPushed(ProcessWhatsappAiResponse::class, 1);
});

it('idempotencia: mesmo message_id nao dispara AI 2x', function () {
    Queue::fake([ProcessWhatsappAiResponse::class]);
    ['instance' => $inst] = buildCloudSetup();

    $payload = cloudInboundPayload($inst->phone_number_id, 'oi', '5511555550000', 'wamid.DUPE');

    (new ProcessCloudApiWebhook($payload))->handle();
    (new ProcessCloudApiWebhook($payload))->handle();

    Queue::assertPushed(ProcessWhatsappAiResponse::class, 1);
    expect(WhatsappMessage::count())->toBe(1);
});
