<?php

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Services\AiCallQuotaService;
use App\Services\DeepSeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * P1.a — Hardening da integracao DeepSeek.
 *
 * Cobre:
 *  - Erros da API upstream NAO vazam body cru pro caller (retornam mensagem
 *    generica + error_code). Vazamento antigo permitia payload da Meta no HTML.
 *  - Cota diaria por tenant (AiCallQuotaService): serve como cap "financeiro"
 *    contra abuso — rate limiter web_ai e per-user, este e per-tenant e cobre
 *    jobs em background.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'sk-test-key-value', 'api');
    Cache::flush();
});

// ── Sanitizacao de erros upstream ────────────────────────────────────────────

it('erro 500 upstream NAO vaza body cru pro caller', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response(
            '{"detail":"Internal database error at server db-prod-01.deepseek.internal:5432"}',
            500
        ),
    ]);

    $res = app(DeepSeekService::class)->chat([['role' => 'user', 'content' => 'oi']]);

    expect($res)->toHaveKey('error');
    expect($res)->toHaveKey('error_code');
    expect($res['error_code'])->toBe('ai_upstream_error');
    expect($res['error'])->not->toContain('db-prod-01');
    expect($res['error'])->not->toContain('deepseek.internal');
    expect($res['error'])->not->toContain('Internal database error');
});

it('erro 401 upstream retorna mensagem generica com error_code ai_invalid_key', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response('{"error":"key_prefix_sk_abc_XYZ_leaked"}', 401),
    ]);

    $res = app(DeepSeekService::class)->chat([['role' => 'user', 'content' => 'oi']]);

    expect($res['error_code'])->toBe('ai_invalid_key');
    expect($res['error'])->not->toContain('key_prefix_sk_abc');
    expect($res['error'])->not->toContain('leaked');
});

it('sem chave configurada retorna error_code ai_not_configured', function () {
    SystemSetting::setValue('deepseek_api_key', '', 'api');
    Cache::forget('system_setting.deepseek_api_key');

    $res = app(DeepSeekService::class)->chat([['role' => 'user', 'content' => 'oi']]);

    expect($res['error_code'])->toBe('ai_not_configured');
});

// ── Cota diaria por tenant ───────────────────────────────────────────────────

it('primeira chamada com tenant_id consome cota', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);
    $tenant = Tenant::factory()->create();
    $quota  = app(AiCallQuotaService::class);

    expect($quota->currentCount($tenant->id))->toBe(0);

    app(DeepSeekService::class)->chat([['role' => 'user', 'content' => 'oi']], null, null, $tenant->id);

    expect($quota->currentCount($tenant->id))->toBe(1);
});

it('bloqueia chamada quando cota do tenant esgota', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);
    $tenant = Tenant::factory()->create();
    SystemSetting::setValue('ai_daily_quota_per_tenant', '2', 'api');
    Cache::forget('system_setting.ai_daily_quota_per_tenant');

    $svc = app(DeepSeekService::class);

    $svc->chat([['role' => 'user', 'content' => '1']], null, null, $tenant->id);
    $svc->chat([['role' => 'user', 'content' => '2']], null, null, $tenant->id);
    $res = $svc->chat([['role' => 'user', 'content' => '3']], null, null, $tenant->id);

    expect($res['error_code'])->toBe('ai_quota_exceeded');
    // Cota nao e incrementada quando ja esgotou.
    expect(app(AiCallQuotaService::class)->currentCount($tenant->id))->toBe(2);
});

it('tenants diferentes tem cotas isoladas', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $svc = app(DeepSeekService::class);
    $svc->chat([['role' => 'user', 'content' => '1']], null, null, $tenantA->id);
    $svc->chat([['role' => 'user', 'content' => '1']], null, null, $tenantA->id);
    $svc->chat([['role' => 'user', 'content' => '1']], null, null, $tenantB->id);

    $q = app(AiCallQuotaService::class);
    expect($q->currentCount($tenantA->id))->toBe(2);
    expect($q->currentCount($tenantB->id))->toBe(1);
});

it('chamada sem tenant_id (bypass) nao consome cota de ninguem', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);
    $tenant = Tenant::factory()->create();

    app(DeepSeekService::class)->chat([['role' => 'user', 'content' => 'oi']]);

    expect(app(AiCallQuotaService::class)->currentCount($tenant->id))->toBe(0);
});

it('override via SystemSetting ai_daily_quota_per_tenant substitui default', function () {
    $tenant = Tenant::factory()->create();
    SystemSetting::setValue('ai_daily_quota_per_tenant', '42', 'api');
    Cache::forget('system_setting.ai_daily_quota_per_tenant');

    expect(app(AiCallQuotaService::class)->limitForTenant($tenant->id))->toBe(42);
});
