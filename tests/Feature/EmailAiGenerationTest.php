<?php

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailAiTemplateQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'sk-test-key', 'api');
    Cache::flush();
});

// Helper: cria user vinculado a um tenant ativo.
function makeUserForAi(string $role = 'manager'): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

// Helper: monta payload de resposta do DeepSeek com JSON valido.
function fakeDeepSeekJson(array $json): array
{
    return [
        'choices' => [
            ['message' => ['content' => json_encode($json, JSON_UNESCAPED_UNICODE)]],
        ],
    ];
}

// ── EmailAiTemplateQuotaService (cota mensal) ────────────────────────────────

it('cota mensal comeca em zero para um tenant novo', function () {
    $q = app(EmailAiTemplateQuotaService::class);
    expect($q->currentUsage(999))->toBe(0);
    expect($q->remaining(999))->toBe(10);
    expect($q->hasQuota(999))->toBeTrue();
});

it('consume incrementa uso do mes corrente', function () {
    $q = app(EmailAiTemplateQuotaService::class);
    $q->consume(1, 100);
    $q->consume(1, 100);
    expect($q->currentUsage(1))->toBe(2);
    expect($q->remaining(1))->toBe(8);
});

it('hasQuota vira false apos atingir o limite (default 10)', function () {
    $q = app(EmailAiTemplateQuotaService::class);
    for ($i = 0; $i < 10; $i++) $q->consume(1, 100);
    expect($q->hasQuota(1))->toBeFalse();
    expect($q->remaining(1))->toBe(0);
});

it('mes novo reseta cota', function () {
    $q = app(EmailAiTemplateQuotaService::class);
    // Simula 10 usos em julho de 2026
    for ($i = 0; $i < 10; $i++) {
        DB::table('email_ai_template_usage')->insert([
            'tenant_id'  => 1,
            'user_id'    => 100,
            'year_month' => '2026-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    // Verifica em julho: cota cheia
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 7, 26, 12, 0, 0));
    expect($q->currentUsage(1))->toBe(10);
    expect($q->hasQuota(1))->toBeFalse();

    // Muda pra agosto: cota reseta
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 8, 1, 0, 0, 0));
    expect($q->currentUsage(1))->toBe(0);
    expect($q->hasQuota(1))->toBeTrue();

    \Carbon\Carbon::setTestNow();
});

it('tenants diferentes tem cotas mensais isoladas', function () {
    $q = app(EmailAiTemplateQuotaService::class);
    for ($i = 0; $i < 10; $i++) $q->consume(1, 100);
    $q->consume(2, 200);
    expect($q->hasQuota(1))->toBeFalse();
    expect($q->hasQuota(2))->toBeTrue();
    expect($q->currentUsage(2))->toBe(1);
});

it('SystemSetting email_ai_monthly_quota sobrescreve default', function () {
    SystemSetting::setValue('email_ai_monthly_quota', '3', 'ai');
    Cache::forget('system_setting.email_ai_monthly_quota');
    $q = app(EmailAiTemplateQuotaService::class);
    expect($q->limitForTenant(1))->toBe(3);
});

// ── POST /email-campaigns/ai/generate ────────────────────────────────────────

it('endpoint exige autenticacao', function () {
    $r = $this->postJson('/email-campaigns/ai/generate', ['brief' => 'convite para evento beneficente']);
    $r->assertStatus(401);
});

it('endpoint bloqueia role employee (403)', function () {
    // Politica: subordinados (employee) nao podem queimar cota de IA do tenant.
    // Alinhado com /manager/email-campaigns e /ngo/email-campaigns.
    $user = makeUserForAi('employee');
    $this->actingAs($user);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief' => 'Descricao valida qualquer texto pra testar gate',
    ]);
    $r->assertStatus(403);
});

it('endpoint quota bloqueia role employee (403)', function () {
    $user = makeUserForAi('employee');
    $this->actingAs($user);
    $r = $this->getJson('/email-campaigns/ai/quota');
    $r->assertStatus(403);
});

it('endpoint valida brief minimo', function () {
    $user = makeUserForAi();
    $this->actingAs($user);
    $r = $this->postJson('/email-campaigns/ai/generate', ['brief' => 'curto']);
    $r->assertStatus(422)->assertJsonValidationErrors(['brief']);
});

it('endpoint gera template com sucesso e consome cota', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(fakeDeepSeekJson([
        'subject'   => 'Vem para o nosso evento anual',
        'preheader' => 'Uma noite especial em 15/08',
        'html'      => '<!DOCTYPE html><html><body><h1>Ola</h1></body></html>',
    ]), 200)]);

    $user = makeUserForAi();
    $this->actingAs($user);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief'       => 'Convite para evento beneficente presencial no dia 15/08',
        'tone'        => 'emotivo e inspirador',
        'sender_name' => 'ONG Exemplo',
        'brand_color' => '#059669',
    ]);

    $r->assertOk()
      ->assertJsonStructure(['subject', 'preheader', 'html', 'remaining', 'limit'])
      ->assertJson([
          'subject'   => 'Vem para o nosso evento anual',
          'limit'     => 10,
          'remaining' => 9,
      ]);

    expect(app(EmailAiTemplateQuotaService::class)->currentUsage($user->tenant_id))->toBe(1);
});

it('endpoint retorna 429 quando cota mensal esgotada e NAO chama IA', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(fakeDeepSeekJson([
        'subject' => 'x', 'preheader' => 'y', 'html' => '<html></html>',
    ]), 200)]);

    $user = makeUserForAi();
    $this->actingAs($user);

    $q = app(EmailAiTemplateQuotaService::class);
    for ($i = 0; $i < 10; $i++) $q->consume($user->tenant_id, $user->id);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief' => 'Descricao valida para testar limite mensal',
    ]);

    $r->assertStatus(429)->assertJson([
        'error_code' => 'monthly_quota_exceeded',
        'remaining'  => 0,
        'limit'      => 10,
    ]);
    // Cota nao foi incrementada acima do limite
    expect($q->currentUsage($user->tenant_id))->toBe(10);
    // E a IA NAO foi chamada
    Http::assertNothingSent();
});

it('endpoint retorna 503 quando IA falha e NAO consome cota mensal', function () {
    Http::fake(['api.deepseek.com/*' => Http::response('boom', 500)]);

    $user = makeUserForAi();
    $this->actingAs($user);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief' => 'Descricao valida qualquer texto aqui',
    ]);

    $r->assertStatus(503)->assertJsonStructure(['error', 'error_code', 'remaining', 'limit']);
    expect(app(EmailAiTemplateQuotaService::class)->currentUsage($user->tenant_id))->toBe(0);
});

it('endpoint retorna 503 quando IA responde JSON invalido e nao consome cota', function () {
    Http::fake(['api.deepseek.com/*' => Http::response([
        'choices' => [['message' => ['content' => 'texto solto sem json nenhum aqui']]],
    ], 200)]);

    $user = makeUserForAi();
    $this->actingAs($user);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief' => 'Descricao valida para gerar template de email',
    ]);

    $r->assertStatus(503)->assertJson(['error_code' => 'parse_error']);
    expect(app(EmailAiTemplateQuotaService::class)->currentUsage($user->tenant_id))->toBe(0);
});

it('endpoint sanitiza subject com quebra de linha (bloqueia CRLF)', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(fakeDeepSeekJson([
        'subject'   => "Titulo\r\nBcc: attacker@evil.com",
        'preheader' => 'preheader ok',
        'html'      => '<html></html>',
    ]), 200)]);

    $user = makeUserForAi();
    $this->actingAs($user);

    $r = $this->postJson('/email-campaigns/ai/generate', [
        'brief' => 'Descricao valida para testar sanitizacao',
    ]);

    $r->assertOk();
    expect($r->json('subject'))->not->toContain("\n");
    expect($r->json('subject'))->not->toContain("\r");
});

// ── GET /email-campaigns/ai/quota ────────────────────────────────────────────

it('endpoint quota retorna estado atual', function () {
    $user = makeUserForAi();
    $this->actingAs($user);

    $q = app(EmailAiTemplateQuotaService::class);
    $q->consume($user->tenant_id, $user->id);
    $q->consume($user->tenant_id, $user->id);

    $r = $this->getJson('/email-campaigns/ai/quota');
    $r->assertOk()->assertJson(['used' => 2, 'remaining' => 8, 'limit' => 10]);
});
