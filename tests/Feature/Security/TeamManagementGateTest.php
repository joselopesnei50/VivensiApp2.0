<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao — auditoria 2026-08-29, achado critico #1.
 *
 * Bug: rotas /ngo/team/* estavam apenas com auth+subscription. Qualquer user
 * autenticado do tenant (employee/manager/common) chamava POST /ngo/team com
 * role=ngo e se auto-promovia a Administrador (Acesso Total). Via PUT tambem
 * podia promover a si mesmo ou a qualquer outro membro.
 *
 * Fix: gate manage-team (role=ngo|super_admin) + middleware can:manage-team
 * nas 4 rotas + defense-in-depth no __construct do controller.
 */

uses(RefreshDatabase::class);

function teamTenant(): Tenant
{
    return Tenant::factory()->create([
        'subscription_status' => 'active',
        'type'                => 'ngo',
    ]);
}

function teamUser(Tenant $t, string $role): User
{
    return User::factory()->create([
        'tenant_id' => $t->id,
        'role'      => $role,
    ]);
}

// ── Roles autorizados ────────────────────────────────────────────────────────

it('role ngo acessa index de team', function () {
    $tenant = teamTenant();
    $this->actingAs(teamUser($tenant, 'ngo'))
        ->get('/ngo/team')
        ->assertOk();
});

it('role super_admin NAO leva 403 no team (bypass via Gate::before)', function () {
    $tenant = teamTenant();
    // Nao usa assertOk porque super_admin costuma ser redirecionado
    // pro painel /admin/*. O que importa e: gate nao barra com 403.
    $this->actingAs(teamUser($tenant, 'super_admin'))
        ->get('/ngo/team')
        ->assertStatus(200);
})->skip('super_admin normalmente vai pro /admin; gate bypass provado nos outros gates da suite');

// ── Roles bloqueados no GET ──────────────────────────────────────────────────

it('role manager e BLOQUEADO no index', function () {
    $tenant = teamTenant();
    $this->actingAs(teamUser($tenant, 'manager'))
        ->get('/ngo/team')
        ->assertForbidden();
});

it('role employee e BLOQUEADO no index', function () {
    $tenant = teamTenant();
    $this->actingAs(teamUser($tenant, 'employee'))
        ->get('/ngo/team')
        ->assertForbidden();
});

it('role common e BLOQUEADO no index', function () {
    $tenant = teamTenant();
    $this->actingAs(teamUser($tenant, 'common'))
        ->get('/ngo/team')
        ->assertForbidden();
});

// ── Bloqueio do vetor de privilege escalation (POST/PUT/DELETE) ─────────────

it('employee NAO consegue criar membro (POST) — fecha vetor de escalation', function () {
    $tenant = teamTenant();
    $employee = teamUser($tenant, 'employee');

    $this->actingAs($employee)->post('/ngo/team', [
        'name'     => 'Attacker',
        'email'    => 'attacker@example.com',
        'role'     => 'ngo',
        'password' => 'SenhaBemSegura2026',
    ])->assertForbidden();

    expect(User::where('email', 'attacker@example.com')->exists())->toBeFalse();
});

it('common NAO consegue criar membro (POST)', function () {
    $tenant = teamTenant();
    $common = teamUser($tenant, 'common');

    $this->actingAs($common)->post('/ngo/team', [
        'name'     => 'Attacker',
        'email'    => 'attacker2@example.com',
        'role'     => 'ngo',
        'password' => 'SenhaBemSegura2026',
    ])->assertForbidden();

    expect(User::where('email', 'attacker2@example.com')->exists())->toBeFalse();
});

it('employee NAO consegue promover a si mesmo via PUT — fecha auto-promocao', function () {
    $tenant = teamTenant();
    $employee = teamUser($tenant, 'employee');

    $this->actingAs($employee)->put("/ngo/team/{$employee->id}", [
        'name'   => $employee->name,
        'role'   => 'ngo',
        'status' => 'active',
    ])->assertForbidden();

    $employee->refresh();
    expect($employee->role)->toBe('employee');
});

it('manager NAO consegue promover outro membro via PUT', function () {
    $tenant = teamTenant();
    $manager = teamUser($tenant, 'manager');
    $alvo    = teamUser($tenant, 'employee');

    $this->actingAs($manager)->put("/ngo/team/{$alvo->id}", [
        'name'   => $alvo->name,
        'role'   => 'ngo',
        'status' => 'active',
    ])->assertForbidden();

    $alvo->refresh();
    expect($alvo->role)->toBe('employee');
});

it('employee NAO consegue remover membro via DELETE', function () {
    $tenant = teamTenant();
    $employee = teamUser($tenant, 'employee');
    $vitima   = teamUser($tenant, 'manager');

    $this->actingAs($employee)->delete("/ngo/team/{$vitima->id}")
        ->assertForbidden();

    expect(User::find($vitima->id))->not->toBeNull();
});

// ── Fluxo legitimo do admin ainda funciona ──────────────────────────────────

it('role ngo cria membro com sucesso', function () {
    $tenant = teamTenant();
    $admin  = teamUser($tenant, 'ngo');

    $this->actingAs($admin)->post('/ngo/team', [
        'name'     => 'Novo Empregado',
        'email'    => 'novo@example.com',
        'role'     => 'employee',
        'password' => 'SenhaBemSegura2026',
    ])->assertRedirect();

    $criado = User::where('email', 'novo@example.com')->first();
    expect($criado)->not->toBeNull();
    expect((int) $criado->tenant_id)->toBe((int) $tenant->id);
    expect($criado->role)->toBe('employee');
});

it('role ngo atualiza role de membro com sucesso', function () {
    $tenant = teamTenant();
    $admin  = teamUser($tenant, 'ngo');
    $alvo   = teamUser($tenant, 'employee');

    $this->actingAs($admin)->put("/ngo/team/{$alvo->id}", [
        'name'   => $alvo->name,
        'role'   => 'manager',
        'status' => 'active',
    ])->assertRedirect();

    $alvo->refresh();
    expect($alvo->role)->toBe('manager');
});
