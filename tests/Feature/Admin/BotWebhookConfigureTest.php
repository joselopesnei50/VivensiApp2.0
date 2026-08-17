<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'whatsapp.evolution_api_url'     => 'https://evo.test',
        'whatsapp.evolution_global_key'  => 'testkey123',
        'services.whatsapp.bot_secret'   => 'sec-abc-999',
        'app.url'                        => 'https://vivensi.test',
    ]);
    SystemSetting::setValue('bot_instance_name', 'VIVENSI-BOT', 'bot');
});

it('POST /webhook/set com URL contendo bot_token', function () {
    Http::fake([
        'evo.test/webhook/find/*' => Http::response(['url' => 'old'], 200),
        'evo.test/webhook/set/*'  => Http::response(['webhook' => ['url' => 'https://vivensi.test/api/whatsapp/bot?bot_token=sec-abc-999']], 200),
    ]);

    $this->artisan('bot:webhook-configure')->assertSuccessful();

    Http::assertSent(function ($req) {
        if (!str_contains((string) $req->url(), '/webhook/set/VIVENSI-BOT')) return false;
        $data = $req->data();
        return isset($data['webhook']['url'])
            && str_contains($data['webhook']['url'], 'bot_token=sec-abc-999')
            && ($data['webhook']['enabled'] ?? null) === true;
    });
});

it('--show nao chama webhook/set', function () {
    Http::fake([
        'evo.test/webhook/find/*' => Http::response(['url' => 'x'], 200),
    ]);

    $this->artisan('bot:webhook-configure', ['--show' => true])->assertSuccessful();

    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/webhook/set/'));
});

it('falha quando WHATSAPP_BOT_SECRET esta vazio', function () {
    config(['services.whatsapp.bot_secret' => null]);
    $this->artisan('bot:webhook-configure')->assertFailed();
});

it('falha quando EVOLUTION_API_URL esta vazio', function () {
    config(['whatsapp.evolution_api_url' => null, 'whatsapp.evolution_global_key' => null]);
    // env fallback tambem
    putenv('EVOLUTION_API_URL=');
    putenv('EVOLUTION_GLOBAL_KEY=');
    $this->artisan('bot:webhook-configure')->assertFailed();
});

it('--instance sobrepoe SystemSetting', function () {
    Http::fake([
        'evo.test/webhook/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('bot:webhook-configure', ['--instance' => 'CUSTOM-INSTANCE'])->assertSuccessful();

    Http::assertSent(fn ($r) => str_contains($r->url(), '/webhook/find/CUSTOM-INSTANCE')
        || str_contains($r->url(), '/webhook/set/CUSTOM-INSTANCE'));
});
