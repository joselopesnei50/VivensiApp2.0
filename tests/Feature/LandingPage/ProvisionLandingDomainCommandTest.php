<?php

use App\Models\LandingPage;
use App\Models\Tenant;
use App\Services\LandingDomainProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Fase 2 do custom domain — orquestracao do comando landing:provision-domain.
 *
 * Mocka o LandingDomainProvisioner (que faz shell exec real) pra testar
 * so a logica de estado/transicao/rollback. Testes de integracao ficam pra
 * validacao manual no VPS Vivensi (dry-run em domain de teste).
 */

uses(RefreshDatabase::class);

function pdEnv(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
}

function pdLp(Tenant $t, array $overrides = []): LandingPage
{
    return LandingPage::create(array_merge([
        'tenant_id' => $t->id,
        'title'     => 'X',
        'slug'      => 'x-' . uniqid(),
        'status'    => 'published',
        'settings'  => [],
    ], $overrides));
}

it('falha quando LP nao tem custom_domain', function () {
    $t = pdEnv();
    $lp = pdLp($t); // sem custom_domain

    $this->artisan('landing:provision-domain', ['id' => $lp->id])
        ->assertFailed();
});

it('falha quando LP nao existe', function () {
    $this->artisan('landing:provision-domain', ['id' => 99999])
        ->assertFailed();
});

it('para com sucesso se LP ja esta active (sem force)', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'        => 'www.ativo.org.br',
        'custom_domain_status' => 'active',
    ]);

    $this->artisan('landing:provision-domain', ['id' => $lp->id])
        ->assertSuccessful()
        ->expectsOutputToContain('ja provisionada');
});

it('marca status verifying entao failed quando DNS nao aponta', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'        => 'www.dns-errado.org.br',
        'custom_domain_status' => 'pending',
    ]);

    $mock = $this->mock(LandingDomainProvisioner::class);
    $mock->shouldReceive('validateDns')
        ->once()
        ->andReturn(['ok' => false, 'resolved' => '1.2.3.4', 'message' => 'DNS aponta pra 1.2.3.4']);

    $this->artisan('landing:provision-domain', ['id' => $lp->id])
        ->assertFailed();

    $lp->refresh();
    expect($lp->custom_domain_status)->toBe('failed');
    expect($lp->custom_domain_error)->toContain('DNS');
});

it('dry-run valida DNS e para sem chamar certbot', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'        => 'www.dryrun.org.br',
        'custom_domain_status' => null,
    ]);

    $mock = $this->mock(LandingDomainProvisioner::class);
    $mock->shouldReceive('validateDns')->once()->andReturn(['ok' => true, 'resolved' => LandingDomainProvisioner::VPS_IP, 'message' => 'ok']);
    $mock->shouldNotReceive('issueCertificate');
    $mock->shouldNotReceive('writeNginxConfig');

    $this->artisan('landing:provision-domain', ['id' => $lp->id, '--dry-run' => true])
        ->assertSuccessful();

    $lp->refresh();
    expect($lp->custom_domain_status)->toBe('pending');
});

it('fluxo happy: DNS + cert + nginx + reload + HTTPS -> active', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'        => 'www.happy.org.br',
        'custom_domain_status' => null,
    ]);

    $mock = $this->mock(LandingDomainProvisioner::class);
    $mock->shouldReceive('validateDns')->once()->andReturn(['ok' => true, 'message' => 'ok']);
    $mock->shouldReceive('issueCertificate')->once()->andReturn(['ok' => true, 'message' => 'cert issued']);
    $mock->shouldReceive('writeNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'written']);
    $mock->shouldReceive('testNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'syntax ok']);
    $mock->shouldReceive('reloadNginx')->once()->andReturn(['ok' => true, 'message' => 'reloaded']);
    $mock->shouldReceive('testHttps')->once()->andReturn(['ok' => true, 'httpCode' => '200', 'message' => 'HTTPS retornou 200']);

    $this->artisan('landing:provision-domain', ['id' => $lp->id])
        ->assertSuccessful();

    $lp->refresh();
    expect($lp->custom_domain_status)->toBe('active');
    expect($lp->custom_domain_ssl_expires_at)->not->toBeNull();
    expect($lp->custom_domain_error)->toBeNull();
});

it('rollback: nginx -t falha -> remove config + status failed', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'        => 'www.badconfig.org.br',
        'custom_domain_status' => null,
    ]);

    $mock = $this->mock(LandingDomainProvisioner::class);
    $mock->shouldReceive('validateDns')->once()->andReturn(['ok' => true, 'message' => 'ok']);
    $mock->shouldReceive('issueCertificate')->once()->andReturn(['ok' => true, 'message' => 'cert ok']);
    $mock->shouldReceive('writeNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'written']);
    $mock->shouldReceive('testNginxConfig')->once()->andReturn(['ok' => false, 'message' => 'nginx: syntax error']);
    $mock->shouldReceive('removeNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'rollback ok']);

    $this->artisan('landing:provision-domain', ['id' => $lp->id])
        ->assertFailed();

    $lp->refresh();
    expect($lp->custom_domain_status)->toBe('failed');
    expect($lp->custom_domain_error)->toContain('nginx -t');
});

it('unprovision zera colunas do LP', function () {
    $t = pdEnv();
    $lp = pdLp($t, [
        'custom_domain'                => 'www.remover.org.br',
        'custom_domain_status'         => 'active',
        'custom_domain_ssl_expires_at' => now()->addDays(60),
    ]);

    $mock = $this->mock(LandingDomainProvisioner::class);
    $mock->shouldReceive('removeNginxConfig')->once()->andReturn(['ok' => true, 'message' => 'ok']);
    $mock->shouldReceive('reloadNginx')->once()->andReturn(['ok' => true, 'message' => 'ok']);

    $this->artisan('landing:unprovision-domain', ['id' => $lp->id])
        ->assertSuccessful();

    $lp->refresh();
    expect($lp->custom_domain)->toBeNull();
    expect($lp->custom_domain_status)->toBeNull();
});
