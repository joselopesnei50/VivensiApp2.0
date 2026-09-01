<?php

use App\Jobs\ProvisionLandingDomainJob;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

/**
 * Fase 3 do custom domain — endpoints do builder + gate + toggle admin.
 */

uses(RefreshDatabase::class);

function cdaEnv(bool $addon = true): array
{
    $t = Tenant::factory()->create([
        'subscription_status'         => 'active',
        'type'                        => 'ngo',
        'custom_domain_addon_active'  => $addon,
    ]);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    return [$t, $u];
}

function cdaLp(Tenant $t, array $overrides = []): LandingPage
{
    return LandingPage::create(array_merge([
        'tenant_id' => $t->id,
        'title'     => 'X',
        'slug'      => 'x-' . uniqid(),
        'status'    => 'published',
        'settings'  => [],
    ], $overrides));
}

// ── Gate use-custom-domain ──────────────────────────────────────────────────

it('sem add-on ativo: setCustomDomain retorna 403', function () {
    [$t, $u] = cdaEnv(false);
    $lp = cdaLp($t);

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/custom-domain", ['custom_domain' => 'www.x.com.br'])
        ->assertForbidden();
});

it('com add-on ativo: setCustomDomain aceita', function () {
    [$t, $u] = cdaEnv(true);
    $lp = cdaLp($t);

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/custom-domain", ['custom_domain' => 'www.suaong.org.br'])
        ->assertOk()
        ->assertJson(['success' => true]);

    $lp->refresh();
    expect($lp->custom_domain)->toBe('www.suaong.org.br');
    expect($lp->custom_domain_status)->toBe('pending');
});

it('role manager (nao ngo) e BLOQUEADO mesmo com addon ativo', function () {
    [$t] = cdaEnv(true);
    $manager = User::factory()->create(['tenant_id' => $t->id, 'role' => 'manager']);
    $lp = cdaLp($t);

    $this->actingAs($manager)
        ->post("/ngo/landing-pages/{$lp->id}/custom-domain", ['custom_domain' => 'www.x.com.br'])
        ->assertForbidden();
});

// ── Validação de domínio ────────────────────────────────────────────────────

it('rejeita dominio invalido', function () {
    [, $u] = cdaEnv(true);
    $lp = cdaLp(User::find($u->id)->tenant);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/custom-domain", ['custom_domain' => 'nao-e-dominio'])
        ->assertStatus(422);
});

it('bloqueia dominios da propria Vivensi', function () {
    [, $u] = cdaEnv(true);
    $lp = cdaLp(User::find($u->id)->tenant);
    $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/custom-domain", [
            'custom_domain' => 'evil.' . $appHost,
        ])
        ->assertStatus(422);
});

it('bloqueia dominio ja em uso por outra LP', function () {
    [$t, $u] = cdaEnv(true);
    cdaLp($t, ['custom_domain' => 'www.duplicado.org.br', 'custom_domain_status' => 'active']);
    $lp2 = cdaLp($t);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp2->id}/custom-domain", ['custom_domain' => 'www.duplicado.org.br'])
        ->assertStatus(422);
});

// ── Provision enfileira job ─────────────────────────────────────────────────

it('provision enfileira ProvisionLandingDomainJob', function () {
    Queue::fake();
    [$t, $u] = cdaEnv(true);
    $lp = cdaLp($t, ['custom_domain' => 'www.aprovar.org.br', 'custom_domain_status' => 'pending']);

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/custom-domain/provision")
        ->assertOk();

    Queue::assertPushed(ProvisionLandingDomainJob::class, fn ($job) => $job->landingId === $lp->id);

    $lp->refresh();
    expect($lp->custom_domain_status)->toBe('verifying');
});

it('provision retorna 422 se sem custom_domain', function () {
    [$t, $u] = cdaEnv(true);
    $lp = cdaLp($t); // sem custom_domain

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/custom-domain/provision")
        ->assertStatus(422);
});

// ── Remove ─────────────────────────────────────────────────────────────────

it('remove zera colunas do LP', function () {
    [$t, $u] = cdaEnv(true);
    $lp = cdaLp($t, ['custom_domain' => 'www.remover.org.br', 'custom_domain_status' => 'active']);

    $mock = $this->mock(\App\Services\LandingDomainProvisioner::class);
    $mock->shouldReceive('removeNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'ok']);
    $mock->shouldReceive('reloadNginx')->once()->andReturn(['ok' => true, 'message' => 'ok']);

    $this->actingAs($u)
        ->delete("/ngo/landing-pages/{$lp->id}/custom-domain")
        ->assertOk();

    $lp->refresh();
    expect($lp->custom_domain)->toBeNull();
    expect($lp->custom_domain_status)->toBeNull();
});

// ── Toggle addon admin ─────────────────────────────────────────────────────

it('super_admin toggle addon on tenant', function () {
    $t = Tenant::factory()->create(['custom_domain_addon_active' => false]);
    $admin = User::factory()->create(['tenant_id' => $t->id, 'role' => 'super_admin']);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->post("/admin/tenants/{$t->id}/custom-domain-addon", ['active' => 1])
        ->assertRedirect();

    $t->refresh();
    expect($t->custom_domain_addon_active)->toBeTrue();
});

it('user comum (nao super_admin) NAO consegue togglar addon', function () {
    [$t, $u] = cdaEnv(false);

    $this->actingAs($u)
        ->post("/admin/tenants/{$t->id}/custom-domain-addon", ['active' => 1])
        ->assertForbidden();
});
