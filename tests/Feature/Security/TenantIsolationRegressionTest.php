<?php

use App\Models\Prospect;
use App\Models\SicRequest;
use App\Models\Tenant;
use App\Models\TransparencyDocument;
use App\Models\TransparencyPortal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Regressao de isolamento de tenant nos endpoints publicos e no Prospecting.
 *
 * Endpoints publicos usam withoutGlobalScope('tenant') por design (scope
 * fail-closed sem auth) — estes testes quebram se alguem remover o filtro
 * manual por tenant_id/slug que garante o isolamento.
 * Referencia: PLANO_AUDITORIA_2026-07-12.md (P0) e AUDIT_C1_FINDINGS_2026-07-12.md.
 */

uses(RefreshDatabase::class);

function isolationSetup(): array
{
    $make = function (string $suffix) {
        $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
        $portal = TransparencyPortal::create([
            'tenant_id'    => $tenant->id,
            'slug'         => 'ong-' . $suffix . '-' . $tenant->id,
            'title'        => 'Portal ' . strtoupper($suffix),
            'is_published' => true,
        ]);
        return [$tenant, $portal];
    };

    [$tenantA, $portalA] = $make('a');
    [$tenantB, $portalB] = $make('b');

    return [$tenantA, $portalA, $tenantB, $portalB];
}

// ── Transparência: documentos ─────────────────────────────────────────────────

it('nao baixa documento do tenant B via slug do portal A', function () {
    [, $portalA, $tenantB] = isolationSetup();
    Storage::fake('public');

    $docB = TransparencyDocument::create([
        'tenant_id' => $tenantB->id,
        'title'     => 'Balanco Confidencial B',
        'type'      => 'financial_balance',
        'file_path' => 'transparency_docs/' . $tenantB->id . '/doc-b.pdf',
        'year'      => 2026,
    ]);
    Storage::disk('public')->put($docB->file_path, '%PDF-1.4 conteudo B');

    $this->get("/transparencia/{$portalA->slug}/docs/{$docB->id}")
        ->assertNotFound();
});

it('baixa documento do proprio tenant via slug do proprio portal', function () {
    [$tenantA, $portalA] = isolationSetup();
    Storage::fake('public');

    $docA = TransparencyDocument::create([
        'tenant_id' => $tenantA->id,
        'title'     => 'Balanco Publico A',
        'type'      => 'financial_balance',
        'file_path' => 'transparency_docs/' . $tenantA->id . '/doc-a.pdf',
        'year'      => 2026,
    ]);
    Storage::disk('public')->put($docA->file_path, '%PDF-1.4 conteudo A');

    $this->get("/transparencia/{$portalA->slug}/docs/{$docA->id}")
        ->assertOk();
});

it('portal nao publicado retorna 404', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $portal = TransparencyPortal::create([
        'tenant_id'    => $tenant->id,
        'slug'         => 'ong-oculta-' . $tenant->id,
        'title'        => 'Portal Oculto',
        'is_published' => false,
    ]);

    $this->get("/transparencia/{$portal->slug}/dados.csv")->assertNotFound();
    $this->get("/transparencia/{$portal->slug}/relatorio.pdf")->assertNotFound();
});

// ── SIC ───────────────────────────────────────────────────────────────────────

it('nao mostra protocolo SIC do tenant B via slug do portal A', function () {
    [, $portalA, $tenantB] = isolationSetup();

    $sicB = SicRequest::create([
        'tenant_id'       => $tenantB->id,
        'protocol'        => 'SIC-2026-CANARY1',
        'requester_name'  => 'Cidadao B',
        'requester_email' => 'cidadao@b.org',
        'subject'         => 'Pedido confidencial B',
        'message'         => 'Detalhes do pedido B',
        'status'          => 'pending',
        'deadline_at'     => now()->addWeekdays(20)->toDateString(),
    ]);

    $this->get("/transparencia/{$portalA->slug}/sic/{$sicB->protocol}")
        ->assertNotFound();
});

it('mostra protocolo SIC do proprio tenant', function () {
    [$tenantA, $portalA] = isolationSetup();

    $sicA = SicRequest::create([
        'tenant_id'       => $tenantA->id,
        'protocol'        => 'SIC-2026-PROPRIO1',
        'requester_name'  => 'Cidadao A',
        'requester_email' => 'cidadao@a.org',
        'subject'         => 'Pedido A',
        'message'         => 'Detalhes do pedido A',
        'status'          => 'pending',
        'deadline_at'     => now()->addWeekdays(20)->toDateString(),
    ]);

    $this->get("/transparencia/{$portalA->slug}/sic/{$sicA->protocol}")
        ->assertOk();
});

// ── Prospecting (autenticado) ─────────────────────────────────────────────────

function prospectingUser(Tenant $tenant): User
{
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'manager',
    ]);
}

it('user do tenant A nao deleta prospect do tenant B (destroy)', function () {
    [$tenantA, , $tenantB] = isolationSetup();

    $prospectB = Prospect::create([
        'tenant_id'    => $tenantB->id,
        'company_name' => 'Empresa do B',
        'status'       => 'raw',
    ]);

    $this->actingAs(prospectingUser($tenantA))
        ->delete("/prospecting/{$prospectB->id}")
        ->assertNotFound();

    expect(Prospect::withoutGlobalScope('tenant')->find($prospectB->id))->not->toBeNull();
});

it('bulk-delete do tenant A ignora ids do tenant B', function () {
    [$tenantA, , $tenantB] = isolationSetup();

    $prospectB1 = Prospect::create([
        'tenant_id'    => $tenantB->id,
        'company_name' => 'Empresa B1',
        'status'       => 'raw',
    ]);
    $prospectB2 = Prospect::create([
        'tenant_id'    => $tenantB->id,
        'company_name' => 'Empresa B2',
        'status'       => 'raw',
    ]);

    $this->actingAs(prospectingUser($tenantA))
        ->delete('/prospecting/bulk-delete', [
            'prospect_ids_raw' => "{$prospectB1->id},{$prospectB2->id}",
        ])
        ->assertRedirect();

    expect(Prospect::withoutGlobalScope('tenant')->whereIn('id', [$prospectB1->id, $prospectB2->id])->count())
        ->toBe(2);
});

// ── Helper forTenantUnscoped ──────────────────────────────────────────────────

it('forTenantUnscoped retorna apenas registros do tenant informado', function () {
    [$tenantA, , $tenantB] = isolationSetup();

    Prospect::create(['tenant_id' => $tenantA->id, 'company_name' => 'A1', 'status' => 'raw']);
    Prospect::create(['tenant_id' => $tenantB->id, 'company_name' => 'B1', 'status' => 'raw']);

    $rows = Prospect::forTenantUnscoped($tenantA->id)->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->company_name)->toBe('A1');
});
