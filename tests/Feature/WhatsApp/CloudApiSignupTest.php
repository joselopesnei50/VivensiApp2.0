<?php

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\WhatsApp\CloudApiOnboardingService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    SystemSetting::setValue('meta_cloud_app_id',     '1234567890123456');
    SystemSetting::setValue('meta_cloud_app_secret', 'test-cloud-secret-32chars-string');
    SystemSetting::setValue('meta_cloud_config_id',  'cfg_1111111111');

    $this->tenant = Tenant::factory()->create();
    // role='common' é aceito pela Gate access-whatsapp; 'admin' e 'user' não
    $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'common']);
});

// ── Service ────────────────────────────────────────────────────────────────

it('exchangeCodeForToken retorna access_token quando graph responde 200', function () {
    Http::fake([
        'graph.facebook.com/*/oauth/access_token*' => Http::response([
            'access_token' => 'EAAG_LONG_LIVED_TOKEN_abc123',
            'token_type'   => 'bearer',
        ], 200),
    ]);

    $service = new CloudApiOnboardingService();
    $token = $service->exchangeCodeForToken('code_from_fb_login');

    expect($token)->toBe('EAAG_LONG_LIVED_TOKEN_abc123');
});

it('exchangeCodeForToken joga RuntimeException quando graph retorna erro', function () {
    Http::fake([
        'graph.facebook.com/*/oauth/access_token*' => Http::response(['error' => ['message' => 'Invalid code']], 400),
    ]);

    $service = new CloudApiOnboardingService();

    expect(fn () => $service->exchangeCodeForToken('bad_code'))
        ->toThrow(RuntimeException::class);
});

it('registerPhoneNumber usa POST no phone_number_id com pin', function () {
    Http::fake([
        'graph.facebook.com/*/register' => Http::response(['success' => true], 200),
    ]);

    (new CloudApiOnboardingService())->registerPhoneNumber('phone_id_999', 'EAAG_TOKEN', '142857');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/phone_id_999/register')
            && $request->method() === 'POST'
            && ($request->data()['pin'] ?? null) === '142857'
            && ($request->data()['messaging_product'] ?? null) === 'whatsapp';
    });
});

it('subscribeAppToWaba usa POST em waba_id/subscribed_apps', function () {
    Http::fake([
        'graph.facebook.com/*/subscribed_apps' => Http::response(['success' => true], 200),
    ]);

    (new CloudApiOnboardingService())->subscribeAppToWaba('waba_id_777', 'EAAG_TOKEN');

    Http::assertSent(fn ($r) => str_contains($r->url(), '/waba_id_777/subscribed_apps') && $r->method() === 'POST');
});

it('persistInstance cria WhatsappInstance provider=cloud_api com credenciais cifradas', function () {
    $service = new CloudApiOnboardingService();

    $instance = $service->persistInstance(
        tenantId:       $this->tenant->id,
        wabaId:         'waba_ABC',
        phoneNumberId:  'phone_XYZ',
        accessToken:    'EAAG_LONG_TOKEN_zzz',
    );

    expect($instance->provider)->toBe('cloud_api');
    expect($instance->isCloudApi())->toBeTrue();
    expect($instance->waba_id)->toBe('waba_ABC');
    expect($instance->phone_number_id)->toBe('phone_XYZ');
    expect($instance->graph_access_token)->toBe('EAAG_LONG_TOKEN_zzz');

    // Token cifrado no banco (mesmo padrão de instance_token)
    $raw = \DB::table('whatsapp_instances')->where('id', $instance->id)->value('graph_access_token');
    expect($raw)->not->toBe('EAAG_LONG_TOKEN_zzz');
    expect($raw)->toStartWith('eyJ');
});

it('completeSignup executa fluxo full e cria WhatsappInstance', function () {
    Http::fake([
        'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'EAAG_FINAL_TOKEN'], 200),
        'graph.facebook.com/*/register'            => Http::response(['success' => true], 200),
        'graph.facebook.com/*/subscribed_apps'     => Http::response(['success' => true], 200),
    ]);

    $service = new CloudApiOnboardingService();

    $instance = $service->completeSignup(
        tenantId:        $this->tenant->id,
        code:            'code_xyz',
        wabaId:          'waba_END_2_END',
        phoneNumberId:   'phone_END_2_END',
        registrationPin: '999888',
    );

    expect($instance->provider)->toBe('cloud_api');
    expect($instance->waba_id)->toBe('waba_END_2_END');
    expect($instance->graph_access_token)->toBe('EAAG_FINAL_TOKEN');
});

// ── Controller callback ───────────────────────────────────────────────────

it('POST callback autenticado executa signup e retorna redirect', function () {
    Http::fake([
        'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'EAAG_TOKEN'], 200),
        'graph.facebook.com/*/register'            => Http::response(['success' => true], 200),
        'graph.facebook.com/*/subscribed_apps'     => Http::response(['success' => true], 200),
    ]);

    $response = $this->actingAs($this->user)->postJson('/whatsapp/cloud/callback', [
        'code'            => 'code_ok',
        'waba_id'         => 'waba_A',
        'phone_number_id' => 'phone_A',
        'pin'             => '123456',
    ]);

    $response->assertStatus(200)->assertJson(['ok' => true]);

    expect(WhatsappInstance::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('POST callback rejeita pin invalido (nao numerico ou tamanho errado)', function () {
    foreach (['12345', '1234567', 'abcdef', ''] as $badPin) {
        $this->actingAs($this->user)->postJson('/whatsapp/cloud/callback', [
            'code' => 'x', 'waba_id' => 'x', 'phone_number_id' => 'x', 'pin' => $badPin,
        ])->assertStatus(422);
    }
});

it('POST callback retorna 422 quando graph API falha na etapa /register', function () {
    Http::fake([
        'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'EAAG_TOKEN'], 200),
        'graph.facebook.com/*/register'            => Http::response(['error' => ['message' => 'Number not verified']], 400),
    ]);

    $response = $this->actingAs($this->user)->postJson('/whatsapp/cloud/callback', [
        'code' => 'c', 'waba_id' => 'w', 'phone_number_id' => 'p', 'pin' => '111222',
    ]);

    $response->assertStatus(422)->assertJson(['ok' => false]);
    expect(WhatsappInstance::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('POST callback exige autenticacao', function () {
    $this->postJson('/whatsapp/cloud/callback', [
        'code' => 'x', 'waba_id' => 'x', 'phone_number_id' => 'x', 'pin' => '123456',
    ])->assertStatus(401); // JSON request sem sessão → 401
});

// ── GET show ──────────────────────────────────────────────────────────────

it('GET connect page renderiza pra usuario autenticado', function () {
    $this->actingAs($this->user)->get('/whatsapp/cloud/connect')
        ->assertStatus(200)
        ->assertSee('Meta Cloud API')
        ->assertSee('Conectar com Facebook');
});
