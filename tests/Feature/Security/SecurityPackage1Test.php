<?php

use App\Jobs\DispatchWebhook;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
use App\Support\SafeOutboundUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Auditoria 05/11/2026 — pacote 1: SSRF em webhooks, upload com extensão
 * forjada, bot sem chave, AbacatePay sem assinatura, XSS nas etapas,
 * senha das contas criadas pelo admin.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const SP1_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

afterEach(fn () => SafeOutboundUrl::$resolver = null);

function sp1Ngo(): User
{
    $t = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

function sp1Upload(string $name, string $content): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'up');
    file_put_contents($tmp, $content);
    return new UploadedFile($tmp, $name, null, null, true);
}

// ── SSRF ──────────────────────────────────────────────────────────────────

it('bloqueia destinos internos e aceita endereços públicos', function () {
    SafeOutboundUrl::$resolver = fn ($h) => match ($h) {
        'cliente.com.br' => ['93.184.216.34'],
        'aponta-pra-dentro.com' => ['127.0.0.1'],
        'misto.com' => ['93.184.216.34', '10.0.0.5'],
        default => [],
    };

    foreach ([
        'http://127.0.0.1:6379/', 'http://localhost/x', 'http://169.254.169.254/latest/meta-data/',
        'http://10.0.0.1/', 'http://192.168.1.10/', 'http://172.16.0.1/', 'http://[::1]/', 'http://0.0.0.0/',
        'http://100.64.0.1/', 'http://aponta-pra-dentro.com/', 'http://misto.com/', 'http://servico.internal/',
        'ftp://cliente.com.br/', 'http://user:pass@cliente.com.br/', 'http://nao-existe.com/',
    ] as $url) {
        expect(SafeOutboundUrl::check($url)['ok'])->toBeFalse("deveria bloquear {$url}");
    }

    $ok = SafeOutboundUrl::check('https://cliente.com.br/webhook');
    expect($ok)->toMatchArray(['ok' => true, 'host' => 'cliente.com.br', 'port' => 443, 'ip' => '93.184.216.34']);
    expect(SafeOutboundUrl::check('https://93.184.216.34:8443/x')['ok'])->toBeTrue();
});

it('não deixa cadastrar webhook para endereço interno', function () {
    SafeOutboundUrl::$resolver = fn ($h) => $h === 'cliente.com.br' ? ['93.184.216.34'] : [];
    $user = sp1Ngo();

    $this->withoutMiddleware()->actingAs($user)
        ->post(route('settings.webhooks.store'), ['name' => 'x', 'url' => 'http://127.0.0.1:6379/', 'events' => ['*']])
        ->assertSessionHasErrors('url');
    session()->forget('errors');
    $this->withoutMiddleware()->actingAs($user)
        ->post(route('settings.webhooks.store'), ['name' => 'ok', 'url' => 'https://cliente.com.br/hook', 'events' => ['*']])
        ->assertSessionHasNoErrors();

    expect(Webhook::pluck('url')->all())->toBe(['https://cliente.com.br/hook']);
});

it('webhook já salvo com destino interno é bloqueado na hora do envio', function () {
    Http::fake();
    $user = sp1Ngo();
    $hook = Webhook::create(['tenant_id' => $user->tenant_id, 'name' => 'antigo', 'url' => 'http://169.254.169.254/latest/', 'secret' => 's', 'events' => ['*'], 'active' => true]);

    (new DispatchWebhook($hook->id, 'test', ['a' => 1]))->handle();

    Http::assertNothingSent();
    $log = WebhookLog::first();
    expect($log->success)->toBeFalse()->and($log->response_body)->toContain('Bloqueado');
});

it('webhook para endereço público continua sendo enviado', function () {
    Http::fake(['*' => Http::response('ok', 200)]);
    SafeOutboundUrl::$resolver = fn ($h) => ['93.184.216.34'];
    $user = sp1Ngo();
    $hook = Webhook::create(['tenant_id' => $user->tenant_id, 'name' => 'ok', 'url' => 'https://cliente.com.br/hook', 'secret' => 's', 'events' => ['*'], 'active' => true]);

    (new DispatchWebhook($hook->id, 'test', ['a' => 1]))->handle();

    Http::assertSent(fn ($r) => $r->url() === 'https://cliente.com.br/hook' && $r->hasHeader('X-Vivensi-Signature'));
    expect(WebhookLog::first()->success)->toBeTrue();
});

// ── Upload ────────────────────────────────────────────────────────────────

it('upload de campanha grava a extensão real do conteúdo, nunca a do nome', function () {
    Storage::fake('public');
    $user = sp1Ngo();
    $png = base64_decode(SP1_PNG);

    foreach (['x.html', 'x.svg', 'foto.png'] as $name) {
        $this->withoutMiddleware()->actingAs($user)
            ->postJson(route('ngo.email_campaigns.upload_image'), ['image' => sp1Upload($name, $png . ($name === 'foto.png' ? '' : '<script>alert(1)</script>'))])
            ->assertOk();
    }

    $files = Storage::disk('public')->allFiles('email_campaigns_uploads');
    expect($files)->toHaveCount(3);
    foreach ($files as $f) {
        expect(pathinfo($f, PATHINFO_EXTENSION))->toBe('png');
    }

    // Conteúdo que não é imagem é recusado
    $this->withoutMiddleware()->actingAs($user)
        ->postJson(route('ngo.email_campaigns.upload_image'), ['image' => sp1Upload('x.png', '<html><script>alert(1)</script></html>')])
        ->assertStatus(422);
});

it('upload do admin também usa a extensão real', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'role' => 'super_admin', 'two_factor_confirmed_at' => now()]);

    $this->withoutMiddleware()->actingAs($admin)
        ->postJson(route('admin.email_campaigns.upload_image'), ['image' => sp1Upload('x.html', base64_decode(SP1_PNG) . '<script>alert(1)</script>')])
        ->assertOk();

    expect(collect(Storage::disk('public')->allFiles('email_campaigns_uploads'))->map(fn ($f) => pathinfo($f, PATHINFO_EXTENSION))->unique()->all())->toBe(['png']);
});

it('comando lista e só apaga com --apagar os arquivos que não são imagem', function () {
    Storage::fake('public');
    Storage::disk('public')->put('email_campaigns_uploads/1_a.html', '<script>');
    Storage::disk('public')->put('email_campaigns_uploads/1_b.svg', '<svg>');
    Storage::disk('public')->put('email_campaigns_uploads/1_c.png', 'png');

    $this->artisan('security:uploads-campanha')->expectsOutputToContain('2 arquivo(s)')->assertSuccessful();
    expect(Storage::disk('public')->allFiles('email_campaigns_uploads'))->toHaveCount(3);

    $this->artisan('security:uploads-campanha --apagar')->assertSuccessful();
    expect(Storage::disk('public')->allFiles('email_campaigns_uploads'))->toBe(['email_campaigns_uploads/1_c.png']);
});

// ── Bot interno ───────────────────────────────────────────────────────────

it('bot sem WHATSAPP_BOT_SECRET recusa tudo; com a chave exige o token', function () {
    \App\Models\SystemSetting::setValue('bot_enabled', '1');
    $payload = ['event' => 'messages.upsert', 'data' => ['key' => ['remoteJid' => '5516999999999@s.whatsapp.net', 'fromMe' => false, 'id' => 'X'], 'message' => ['conversation' => 'oi']]];

    config(['services.whatsapp.bot_secret' => null]);
    $this->postJson('/api/whatsapp/bot', $payload)->assertStatus(503);

    config(['services.whatsapp.bot_secret' => 'segredo-123']);
    $this->postJson('/api/whatsapp/bot', $payload)->assertStatus(401);
    $this->postJson('/api/whatsapp/bot?bot_token=errado', $payload)->assertStatus(401);
    $res = $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload);
    expect($res->status())->not->toBe(401)->and($res->status())->not->toBe(503);
});

// ── AbacatePay ────────────────────────────────────────────────────────────

it('AbacatePay sem assinatura é recusado e fica registrado no log', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \App\Models\SystemSetting::setValue('abacatepay_webhook_secret', 'wh-secret');
    Log::spy();

    $this->withHeaders(['X-Webhook-Secret' => 'wh-secret'])
        ->postJson('/api/abacatepay/webhook', ['event' => 'billing.paid', 'data' => []])
        ->assertStatus(401);

    Log::shouldHaveReceived('error')->withArgs(fn ($msg) => str_contains($msg, 'SEM assinatura'))->once();
});

// ── XSS nas etapas ────────────────────────────────────────────────────────

it('título de etapa com &quot; não escapa do onclick', function () {
    $payload = json_encode(['title' => '&quot;);alert(1);//'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
    $dom = new DOMDocument();
    @$dom->loadHTML("<button onclick='stageModalOpen({$payload})'>");
    $onclick = $dom->getElementsByTagName('button')->item(0)->getAttribute('onclick');

    expect($onclick)->not->toContain('"title":"");')
        ->and(json_decode(substr($onclick, strlen('stageModalOpen('), -1), true)['title'])->toBe('&quot;);alert(1);//');

    expect(file_get_contents(resource_path('views/projects/stages/index.blade.php')))->toContain('JSON_HEX_AMP');
});

// ── Senha das contas criadas pelo admin ───────────────────────────────────

it('conta de equipe criada pelo admin exige senha forte', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'two_factor_confirmed_at' => now()]);
    $data = ['name' => 'Equipe', 'email' => 'eq@vivensi.app.br', 'role' => 'support', 'department' => 'Suporte'];

    $this->withoutMiddleware()->actingAs($admin)->post(route('admin.team.store'), $data + ['password' => 'senha123'])
        ->assertSessionHasErrors('password');
    session()->forget('errors');
    $this->withoutMiddleware()->actingAs($admin)->post(route('admin.team.store'), $data + ['password' => 'SenhaForte2026'])
        ->assertSessionHasNoErrors();
});
