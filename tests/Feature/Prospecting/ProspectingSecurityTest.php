<?php

use App\Models\AuditLog;
use App\Models\BroadcastCampaign;
use App\Models\Prospect;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;

/**
 * Auditoria 2026-07-31 — achados 1 e 2:
 * 1. broadcastWhatsapp não dispara mais direto: cria rascunho no módulo
 *    formal (anti-ban/cota/revisão) e não fabrica opt_in_at em número frio.
 * 2. bulkDestroy validado (required, cap 500) + AuditLog + throttle.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function psTenantUser(): array
{
    $tenant = Tenant::factory()->create(['type' => 'manager']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    return [$tenant, $user];
}

function psProspect(Tenant $tenant, ?string $phone = '11999990001'): Prospect
{
    return Prospect::create([
        'tenant_id'    => $tenant->id,
        'company_name' => 'Prospect ' . uniqid(),
        'phone'        => $phone,
        'status'       => 'analyzed',
    ]);
}

// ── Achado 1: broadcast vira rascunho no módulo formal ────────────────────────

it('broadcast cria rascunho com a mensagem preenchida em vez de disparar direto', function () {
    [$tenant, $user] = psTenantUser();
    $p1 = psProspect($tenant, '11999990001');
    $p2 = psProspect($tenant, '11999990002');

    $resp = $this->actingAs($user)->post('/prospecting/broadcast', [
        'prospect_ids_raw' => $p1->id . ',' . $p2->id,
        'message'          => 'Olá! Conheça nosso projeto.',
    ]);

    $resp->assertRedirect(route('whatsapp.broadcast.campaigns'));

    $camp = BroadcastCampaign::where('tenant_id', $tenant->id)->first();
    expect($camp)->not->toBeNull();
    expect($camp->status)->toBe('draft');
    expect($camp->message)->toBe('Olá! Conheça nosso projeto.');
    expect((int) $camp->actual_recipients)->toBe(2);
});

it('broadcast nao cria chat nem registra opt_in de numero frio', function () {
    [$tenant, $user] = psTenantUser();
    $p = psProspect($tenant, '11999990001');

    $this->actingAs($user)->post('/prospecting/broadcast', [
        'prospect_ids_raw' => (string) $p->id,
        'message'          => 'Mensagem de teste',
    ]);

    expect(WhatsappChat::count())->toBe(0);
});

it('broadcast marca prospects como contacted', function () {
    [$tenant, $user] = psTenantUser();
    $p = psProspect($tenant, '11999990001');

    $this->actingAs($user)->post('/prospecting/broadcast', [
        'prospect_ids_raw' => (string) $p->id,
        'message'          => 'Mensagem de teste',
    ]);

    expect($p->fresh()->status)->toBe('contacted');
});

it('broadcast exige mensagem', function () {
    [$tenant, $user] = psTenantUser();
    $p = psProspect($tenant, '11999990001');

    $resp = $this->actingAs($user)->from('/prospecting')->post('/prospecting/broadcast', [
        'prospect_ids_raw' => (string) $p->id,
    ]);

    $resp->assertSessionHasErrors('message');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('broadcast nao inclui prospects de outro tenant', function () {
    [$tA, $userA] = psTenantUser();
    [$tB, $userB] = psTenantUser();
    $pB = psProspect($tB, '11999990099');

    $this->actingAs($userA)->from('/prospecting')->post('/prospecting/broadcast', [
        'prospect_ids_raw' => (string) $pB->id,
        'message'          => 'Mensagem de teste',
    ]);

    expect(BroadcastCampaign::where('tenant_id', $tA->id)->count())->toBe(0);
    expect($pB->fresh()->status)->toBe('analyzed');
});

// ── Achado 2: bulkDestroy validado + auditado ─────────────────────────────────

it('bulk delete remove apenas do proprio tenant e grava AuditLog', function () {
    [$tA, $userA] = psTenantUser();
    [$tB, $userB] = psTenantUser();
    $p1 = psProspect($tA);
    $p2 = psProspect($tA);
    $pB = psProspect($tB);

    $resp = $this->actingAs($userA)->from('/prospecting')->delete('/prospecting/bulk-delete', [
        'prospect_ids_raw' => "{$p1->id},{$p2->id},{$pB->id}",
    ]);

    $resp->assertRedirect('/prospecting');

    expect(Prospect::withoutGlobalScope('tenant')->where('tenant_id', $tA->id)->count())->toBe(0);
    expect(Prospect::withoutGlobalScope('tenant')->where('tenant_id', $tB->id)->count())->toBe(1);

    $log = AuditLog::withoutGlobalScope('tenant')->where('tenant_id', $tA->id)->first();
    expect($log)->not->toBeNull();
    expect($log->event)->toBe('bulk_deleted');
    expect($log->auditable_type)->toBe(Prospect::class);
    expect((int) ($log->old_values['count'] ?? 0))->toBe(2);
});

it('bulk delete rejeita input vazio', function () {
    [$tenant, $user] = psTenantUser();

    $resp = $this->actingAs($user)->from('/prospecting')->delete('/prospecting/bulk-delete', [
        'prospect_ids_raw' => '',
    ]);

    $resp->assertSessionHasErrors('prospect_ids_raw');
});

it('bulk delete rejeita mais de 500 ids', function () {
    [$tenant, $user] = psTenantUser();
    $p = psProspect($tenant);

    $ids = implode(',', range(100000, 100000 + 500)); // 501 ids inexistentes

    $resp = $this->actingAs($user)->from('/prospecting')->delete('/prospecting/bulk-delete', [
        'prospect_ids_raw' => $ids,
    ]);

    $resp->assertRedirect('/prospecting');
    $resp->assertSessionHas('error');
    expect(Prospect::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count())->toBe(1);
    expect(AuditLog::count())->toBe(0);
});

it('bulk delete nao grava AuditLog quando nada foi deletado', function () {
    [$tenant, $user] = psTenantUser();

    $this->actingAs($user)->from('/prospecting')->delete('/prospecting/bulk-delete', [
        'prospect_ids_raw' => '999999',
    ]);

    expect(AuditLog::count())->toBe(0);
});
