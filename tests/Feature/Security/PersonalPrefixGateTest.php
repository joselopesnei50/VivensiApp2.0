<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao — auditoria 2026-08-29 P3.a (baixa).
 *
 * Bug: prefix /personal/* rodava com apenas auth+subscription. User
 * role=ngo/manager/employee do mesmo tenant common conseguia acessar
 * clientes MEI, recibos NFS-e e outros dados do painel MEI/autonomo.
 * Impacto reduzido (mesma organizacao) mas fere segregacao por painel.
 *
 * Fix: gate access-personal (role=common|super_admin) via middleware
 * can:access-personal no prefix personal.php.
 */

uses(RefreshDatabase::class);

function personalTenant(): Tenant
{
    return Tenant::factory()->create([
        'subscription_status' => 'active',
    ]);
}

function personalUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

// ── Roles autorizados ────────────────────────────────────────────────────────

it('role common acessa /personal/reconciliation', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'common'))
        ->get('/personal/reconciliation')
        ->assertOk();
});

it('role common acessa /personal/budget', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'common'))
        ->get('/personal/budget')
        ->assertOk();
});

it('role common acessa /personal/receipts', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'common'))
        ->get('/personal/receipts')
        ->assertOk();
});

// ── Roles bloqueados ─────────────────────────────────────────────────────────

it('role ngo BLOQUEADO em /personal/reconciliation', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'ngo'))
        ->get('/personal/reconciliation')
        ->assertForbidden();
});

it('role manager BLOQUEADO em /personal/budget', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'manager'))
        ->get('/personal/budget')
        ->assertForbidden();
});

it('role employee BLOQUEADO em /personal/clients', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'employee'))
        ->get('/personal/clients')
        ->assertForbidden();
});

it('role ngo BLOQUEADO em POST /personal/reconciliation/store', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'ngo'))
        ->post('/personal/reconciliation/store', [])
        ->assertForbidden();
});

it('role employee BLOQUEADO em POST /personal/receipts', function () {
    $t = personalTenant();
    $this->actingAs(personalUser($t, 'employee'))
        ->post('/personal/receipts', [])
        ->assertForbidden();
});

it('guest e redirecionado para login (nao 403)', function () {
    $this->get('/personal/reconciliation')
        ->assertRedirect('/login');
});
