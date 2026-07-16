<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao: rotas /manager/email-campaigns/* devem exigir gate access-manager.
 *
 * Bug corrigido: rotas estavam apenas com auth+subscription. Um user com
 * role='employee' (subordinado, criado via storeQuick) conseguia acessar
 * campanhas de email do proprio tenant e disparar envios em massa —
 * consumindo quota do tenant sem autorizacao do gestor.
 *
 * O gate 'access-manager' permite ['manager', 'ngo', 'common', 'client']
 * (roles que sao donos/administradores do painel de gestao). Employee NAO
 * esta na lista — o middleware retorna 403 pra ele.
 */

uses(RefreshDatabase::class);

function activeTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function userWithRole(string $role): User
{
    return User::factory()->create([
        'tenant_id' => activeTenant()->id,
        'role'      => $role,
    ]);
}

// ── Roles autorizados ────────────────────────────────────────────────────────

it('role manager acessa index de email-campaigns', function () {
    $this->actingAs(userWithRole('manager'))
        ->get('/manager/email-campaigns')
        ->assertOk();
});

it('role ngo acessa index de email-campaigns', function () {
    $this->actingAs(userWithRole('ngo'))
        ->get('/manager/email-campaigns')
        ->assertOk();
});

it('role common acessa index de email-campaigns', function () {
    $this->actingAs(userWithRole('common'))
        ->get('/manager/email-campaigns')
        ->assertOk();
});

// ── Roles bloqueados ─────────────────────────────────────────────────────────

it('role employee e BLOQUEADO com 403', function () {
    $this->actingAs(userWithRole('employee'))
        ->get('/manager/email-campaigns')
        ->assertForbidden();
});

it('employee nao consegue disparar campanha (POST send)', function () {
    // Cria user manager pra criar a campanha
    $tenant  = activeTenant();
    $manager = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    // Employee tenta acessar create, store, send — todos bloqueados
    $this->actingAs($employee)->get('/manager/email-campaigns/create')->assertForbidden();
    $this->actingAs($employee)->post('/manager/email-campaigns', [])->assertForbidden();
});

it('usuario nao autenticado e redirecionado para login', function () {
    $this->get('/manager/email-campaigns')
        ->assertRedirect('/login');
});
