<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao — auditoria 2026-08-29 achados #2/#3/#4 (alta).
 *
 * 3 controllers do painel NGO estavam sem gate por papel: rotas rodavam
 * apenas com auth+subscription. UI escondida no sidebar (bloco role=ngo)
 * mas endpoint aberto a common/employee/manager do mesmo tenant.
 *
 * Fix: gates manage-hr, manage-transparency, manage-sic (role=ngo|super_admin)
 * aplicados via $this->middleware(...) no __construct de cada controller.
 * TransparencyController e SicController usam ->except([...]) pra deixar as
 * rotas publicas (cidadao) fora do gate.
 */

uses(RefreshDatabase::class);

function ngoAdminTenant(): Tenant
{
    return Tenant::factory()->create([
        'subscription_status' => 'active',
        'type'                => 'ngo',
    ]);
}

function ngoAdminUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

// ── HR ────────────────────────────────────────────────────────────────────────

it('HR: role ngo acessa index', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'ngo'))
        ->get('/ngo/hr')
        ->assertOk();
});

it('HR: role employee e BLOQUEADO no index', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'employee'))
        ->get('/ngo/hr')
        ->assertForbidden();
});

it('HR: role manager e BLOQUEADO no POST storeEmployee', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'manager'))
        ->post('/ngo/hr/employees', [
            'name' => 'X', 'position' => 'Y', 'cpf' => '00000000000',
        ])->assertForbidden();
});

it('HR: role common e BLOQUEADO no DELETE employee', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'common'))
        ->delete('/ngo/hr/employees/999')
        ->assertForbidden();
});

// ── Transparencia (gestao interna) ─────────────────────────────────────────────

it('Transparency: role ngo acessa index interno', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'ngo'))
        ->get('/ngo/transparencia')
        ->assertOk();
});

it('Transparency: role employee e BLOQUEADO no index', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'employee'))
        ->get('/ngo/transparencia')
        ->assertForbidden();
});

it('Transparency: role manager NAO consegue alterar portal (POST updatePortal)', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'manager'))
        ->post('/ngo/transparencia/portal', ['is_published' => 1])
        ->assertForbidden();
});

it('Transparency: role common NAO consegue apagar membro do conselho', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'common'))
        ->delete('/ngo/transparencia/board/999')
        ->assertForbidden();
});

// ── SIC ──────────────────────────────────────────────────────────────────────

it('SIC: role ngo acessa index', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'ngo'))
        ->get('/ngo/sic')
        ->assertOk();
});

it('SIC: role employee e BLOQUEADO no index', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'employee'))
        ->get('/ngo/sic')
        ->assertForbidden();
});

it('SIC: role manager NAO consegue responder (POST respond)', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'manager'))
        ->post('/ngo/sic/999/respond', ['message' => 'x'])
        ->assertForbidden();
});

it('SIC: role common NAO consegue mudar status (PATCH)', function () {
    $t = ngoAdminTenant();
    $this->actingAs(ngoAdminUser($t, 'common'))
        ->patch('/ngo/sic/999/status', ['status' => 'closed'])
        ->assertForbidden();
});

// ── Rotas publicas nao devem ser afetadas pelo gate ─────────────────────────
// Confirma que ->except([...]) funcionou. Slug 'inexistente' vira 404 (nao 403).

it('SIC publico: publicForm de slug inexistente vira 404 (nao 403 do gate)', function () {
    $this->get('/transparencia/slug-que-nao-existe/sic')
        ->assertStatus(404);
});

it('Transparency publico: renderPortal de slug inexistente vira 404 (nao 403)', function () {
    $this->get('/transparencia/slug-que-nao-existe')
        ->assertStatus(404);
});
