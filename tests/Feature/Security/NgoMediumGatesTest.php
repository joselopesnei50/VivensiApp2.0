<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao — auditoria 2026-08-29 P2 (medium).
 *
 * 6 controllers /ngo/* estavam sem gate por papel: rotas rodavam apenas com
 * auth+subscription. common/employee/manager do mesmo tenant executavam
 * CRUD sensivel (doadores com PII, contratos, patrimonio, almoxarifado,
 * campanhas de captacao, orcamento, recibos).
 *
 * Fix: gates manage-donors (ja existia)/contracts/assets (compartilhado
 * Asset+Inventory)/campaigns/budget/receipts (role=ngo|super_admin) via
 * $this->middleware() no __construct. Controllers com rota publica usam
 * ->except([...]) pra preservar assinatura/download/pagina de campanha.
 */

uses(RefreshDatabase::class);

function medTenant(): Tenant
{
    return Tenant::factory()->create([
        'subscription_status' => 'active',
        'type'                => 'ngo',
    ]);
}

function medUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

// ── Donors ────────────────────────────────────────────────────────────────────

it('Donors: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/donors')->assertOk();
});

it('Donors: role employee BLOQUEADO no index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'employee'))->get('/ngo/donors')->assertForbidden();
});

it('Donors: role manager BLOQUEADO no POST store', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'manager'))
        ->post('/ngo/donors', ['name' => 'X'])->assertForbidden();
});

// ── Contracts ────────────────────────────────────────────────────────────────

it('Contracts: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/contracts')->assertOk();
});

it('Contracts: role employee BLOQUEADO no index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'employee'))->get('/ngo/contracts')->assertForbidden();
});

it('Contracts: role manager BLOQUEADO em regenerate-link', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'manager'))
        ->post('/ngo/contracts/999/regenerate-link')->assertForbidden();
});

// ── Assets (patrimonio) ───────────────────────────────────────────────────────

it('Assets: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/assets')->assertOk();
});

it('Assets: role employee BLOQUEADO no POST', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'employee'))
        ->post('/ngo/assets', ['name' => 'X'])->assertForbidden();
});

it('Assets: role common BLOQUEADO no DELETE', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'common'))->delete('/ngo/assets/999')->assertForbidden();
});

// ── Inventory (compartilha gate manage-assets) ────────────────────────────────

it('Inventory: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/inventory')->assertOk();
});

it('Inventory: role manager BLOQUEADO no POST movement', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'manager'))
        ->post('/ngo/inventory/999/movement', ['qty' => 1])->assertForbidden();
});

// ── Campaigns ────────────────────────────────────────────────────────────────

it('Campaigns: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/campaigns')->assertOk();
});

it('Campaigns: role employee BLOQUEADO no POST store', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'employee'))
        ->post('/ngo/campaigns', ['name' => 'X'])->assertForbidden();
});

// ── Budget ───────────────────────────────────────────────────────────────────

it('Budget: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/budget')->assertOk();
});

it('Budget: role manager BLOQUEADO no POST store', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'manager'))
        ->post('/ngo/budget', ['year' => 2026])->assertForbidden();
});

// ── Receipts ─────────────────────────────────────────────────────────────────

it('Receipts: role ngo acessa index', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'ngo'))->get('/ngo/receipts')->assertOk();
});

it('Receipts: role employee BLOQUEADO em regenerate-link', function () {
    $t = medTenant();
    $this->actingAs(medUser($t, 'employee'))
        ->post('/ngo/receipts/999/regenerate-link')->assertForbidden();
});

// ── Rotas publicas nao devem 403 (->except() funcionou) ─────────────────────

it('Public contract: /sign/{token} inexistente vira 404 (nao 403)', function () {
    $this->get('/sign/token-inexistente')->assertStatus(404);
});

it('Public receipt: /r/{token} inexistente vira 404 (nao 403)', function () {
    $this->get('/r/token-inexistente')->assertStatus(404);
});

it('Public campaign: /c/{slug} inexistente vira 404 (nao 403)', function () {
    $this->get('/c/slug-inexistente')->assertStatus(404);
});

it('Public validate-receipt: GET /validar-recibo abre pra guest', function () {
    $this->get('/validar-recibo')->assertOk();
});
