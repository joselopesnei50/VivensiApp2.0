<?php

use App\Models\WhatsappInstance;
use App\Services\WhatsApp\CloudApiWhatsAppSender;
use App\Services\WhatsApp\EvolutionWhatsAppSender;
use App\Services\WhatsApp\WhatsAppSenderFactory;
use Illuminate\Support\Facades\Http;

// ── Factory routing ───────────────────────────────────────────────────────────

it('resolve EvolutionWhatsAppSender quando provider=evolution', function () {
    $instance = WhatsappInstance::factory()->create();

    $sender = WhatsAppSenderFactory::forInstance($instance);

    expect($sender)->toBeInstanceOf(EvolutionWhatsAppSender::class);
    expect($sender->providerName())->toBe('evolution');
});

it('resolve CloudApiWhatsAppSender quando provider=cloud_api', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    $sender = WhatsAppSenderFactory::forInstance($instance);

    expect($sender)->toBeInstanceOf(CloudApiWhatsAppSender::class);
    expect($sender->providerName())->toBe('cloud_api');
});

it('joga InvalidArgumentException para provider desconhecido', function () {
    $instance = WhatsappInstance::factory()->create(['provider' => 'zap_zap_v2']);

    expect(fn () => WhatsAppSenderFactory::forInstance($instance))
        ->toThrow(InvalidArgumentException::class);
});

// ── isConfigured() ────────────────────────────────────────────────────────────

it('EvolutionWhatsAppSender.isConfigured retorna true com instance_name+token', function () {
    $instance = WhatsappInstance::factory()->create();
    $sender = WhatsAppSenderFactory::forInstance($instance);

    expect($sender->isConfigured())->toBeTrue();
});

it('CloudApiWhatsAppSender.isConfigured retorna false sem credenciais', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create([
        'phone_number_id'    => null,
        'graph_access_token' => null,
    ]);
    $sender = WhatsAppSenderFactory::forInstance($instance);

    expect($sender->isConfigured())->toBeFalse();
});

it('CloudApiWhatsAppSender.isConfigured retorna true com credenciais completas', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $sender = WhatsAppSenderFactory::forInstance($instance);

    expect($sender->isConfigured())->toBeTrue();
});

// ── Cloud API sendText — resposta normalizada ─────────────────────────────────

it('CloudApiWhatsAppSender.sendText retorna resposta normalizada em sucesso', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '5511999998888', 'wa_id' => '5511999998888']],
            'messages' => [['id' => 'wamid.HBgN...']],
        ], 200),
    ]);

    $result = WhatsAppSenderFactory::forInstance($instance)->sendText('5511999998888', 'Olá');

    expect($result['ok'])->toBeTrue();
    expect($result['provider'])->toBe('cloud_api');
    expect($result['provider_message_id'])->toBe('wamid.HBgN...');
    expect($result['error'])->toBeNull();
});

it('CloudApiWhatsAppSender.sendText normaliza erro Graph API', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid recipient', 'code' => 100],
        ], 400),
    ]);

    $result = WhatsAppSenderFactory::forInstance($instance)->sendText('invalido', 'oi');

    expect($result['ok'])->toBeFalse();
    expect($result['provider'])->toBe('cloud_api');
    expect($result['provider_message_id'])->toBeNull();
    expect($result['error'])->toBeString();
});

// ── Cloud API sendMedia — payload correto ────────────────────────────────────

it('CloudApiWhatsAppSender.sendMedia com URL usa campo link no payload', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [['id' => 'wamid.MEDIA123']],
        ], 200),
    ]);

    $result = WhatsAppSenderFactory::forInstance($instance)
        ->sendMedia('5511999998888', 'image', 'https://vivensi.app.br/img/logo.png', 'legenda teste');

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->method() === 'POST'
            && $body['type'] === 'image'
            && ($body['image']['link'] ?? null) === 'https://vivensi.app.br/img/logo.png'
            && ($body['image']['caption'] ?? null) === 'legenda teste';
    });
});

// ── Evolution sendTemplate substitui placeholders ─────────────────────────────

it('EvolutionWhatsAppSender.sendTemplate substitui variaveis {{1}} {{2}}', function () {
    $instance = WhatsappInstance::factory()->create();

    Http::fake([
        '*' => Http::response(['key' => ['id' => 'BAE12345']], 200),
    ]);

    $result = WhatsAppSenderFactory::forInstance($instance)
        ->sendTemplate('5511999998888', 'Olá {{1}}, tudo bem? Sua {{2}} está pronta.', 'pt_BR', ['João', 'entrega']);

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function ($request) {
        return isset($request->data()['text'])
            && $request->data()['text'] === 'Olá João, tudo bem? Sua entrega está pronta.';
    });
});

// ── Model helpers ─────────────────────────────────────────────────────────────

it('WhatsappInstance.isEvolution retorna true para provider vazio (retrocompat)', function () {
    $instance = WhatsappInstance::factory()->create(['provider' => '']);

    expect($instance->isEvolution())->toBeTrue();
    expect($instance->isCloudApi())->toBeFalse();
});

it('WhatsappInstance.isCloudApi retorna true para provider=cloud_api', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    expect($instance->isCloudApi())->toBeTrue();
    expect($instance->isEvolution())->toBeFalse();
});

it('graph_access_token e criptografado em disco e decodificado em runtime', function () {
    $token = 'EAAG_super_secret_' . str_repeat('a', 100);
    $instance = WhatsappInstance::factory()->cloudApi()->create(['graph_access_token' => $token]);

    // No banco, valor deve estar cifrado (comeca com "eyJpdiI6" — payload Laravel Crypt)
    $rawFromDb = \DB::table('whatsapp_instances')->where('id', $instance->id)->value('graph_access_token');
    expect($rawFromDb)->not->toBe($token);
    expect($rawFromDb)->toStartWith('eyJ');

    // Em runtime, accessor decripta
    $fresh = WhatsappInstance::find($instance->id);
    expect($fresh->graph_access_token)->toBe($token);
});
