<?php

use App\Models\LandingPage;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Fase 1 do custom domain pra landing pages.
 *
 * Cobre o middleware ResolveCustomDomainLanding: fail-closed pra hosts nao
 * whitelistados, resolve LP quando Host bate + status active + published,
 * hosts proprios da Vivensi seguem o pipeline normal.
 */

uses(RefreshDatabase::class);

function cdEnv(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
}

function cdPublishedPage(Tenant $t, array $overrides = []): LandingPage
{
    return LandingPage::create(array_merge([
        'tenant_id'            => $t->id,
        'title'                => 'Campanha X',
        'slug'                 => 'campanha-x-' . uniqid(),
        'status'               => 'published',
        'settings'             => [],
    ], $overrides));
}

it('Host custom com status active + published resolve pra renderPage', function () {
    $t = cdEnv();
    cdPublishedPage($t, [
        'custom_domain'        => 'www.ongfulano.org.br',
        'custom_domain_status' => 'active',
    ]);

    $this->get('http://www.ongfulano.org.br/')->assertOk();
});

it('Host custom nao whitelistado retorna 404 fail-closed', function () {
    $this->get('http://evil.com/')->assertStatus(404);
});

it('Host custom com status pending NAO serve LP (ainda nao provisionado)', function () {
    $t = cdEnv();
    cdPublishedPage($t, [
        'custom_domain'        => 'www.pending.org.br',
        'custom_domain_status' => 'pending',
    ]);

    $this->get('http://www.pending.org.br/')->assertStatus(404);
});

it('Host custom com status verifying NAO serve LP', function () {
    $t = cdEnv();
    cdPublishedPage($t, [
        'custom_domain'        => 'www.verifying.org.br',
        'custom_domain_status' => 'verifying',
    ]);

    $this->get('http://www.verifying.org.br/')->assertStatus(404);
});

it('Host custom com status failed NAO serve LP', function () {
    $t = cdEnv();
    cdPublishedPage($t, [
        'custom_domain'        => 'www.failed.org.br',
        'custom_domain_status' => 'failed',
    ]);

    $this->get('http://www.failed.org.br/')->assertStatus(404);
});

it('Host custom active mas LP draft NAO serve', function () {
    $t = cdEnv();
    cdPublishedPage($t, [
        'custom_domain'        => 'www.draft.org.br',
        'custom_domain_status' => 'active',
        'status'               => 'draft',
    ]);

    $this->get('http://www.draft.org.br/')->assertStatus(404);
});

it('localhost (host proprio) passa direto pro pipeline normal', function () {
    // Se o middleware nao curto-circuitasse, GET / iria pro welcome do Vivensi (200).
    $this->get('http://localhost/')->assertOk();
});

it('CustomDomain unique bloqueia dois tenants no mesmo dominio', function () {
    $t1 = cdEnv();
    $t2 = cdEnv();
    cdPublishedPage($t1, [
        'custom_domain'        => 'www.exclusivo.org.br',
        'custom_domain_status' => 'active',
    ]);

    expect(fn() => cdPublishedPage($t2, [
        'custom_domain'        => 'www.exclusivo.org.br',
        'custom_domain_status' => 'active',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
