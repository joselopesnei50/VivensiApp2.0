<?php

use App\Models\BroadcastCampaign;
use App\Models\Prospect;
use App\Models\Tenant;
use App\Models\User;

/**
 * Prospecção IA → Disparo em Massa (Opção B).
 * sendToBroadcast cria BroadcastCampaign rascunho com phones desduplicados.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function stbTenantUser(): array
{
    $tenant = Tenant::factory()->create(['type' => 'manager']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    return [$tenant, $user];
}

function stbProspect(Tenant $tenant, ?string $phone = '11999990001'): Prospect
{
    return Prospect::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Prospect ' . uniqid(),
        'phone'     => $phone,
        'status'    => 'analyzed',
    ]);
}

it('cria broadcast rascunho com phones normalizados', function () {
    [$tenant, $user] = stbTenantUser();
    $p1 = stbProspect($tenant, '11999990001');
    $p2 = stbProspect($tenant, '11999990002');

    $resp = $this->actingAs($user)->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => $p1->id . ',' . $p2->id,
    ]);

    $resp->assertRedirect(route('whatsapp.broadcast.campaigns'));

    $camp = BroadcastCampaign::where('tenant_id', $tenant->id)->first();
    expect($camp)->not->toBeNull();
    expect($camp->status)->toBe('draft');
    expect($camp->audience_type)->toBe('selected');
    expect($camp->actual_recipients)->toBe(2);

    $phones = json_decode($camp->phones, true);
    expect($phones)->toHaveCount(2);
    foreach ($phones as $p) {
        expect($p)->toStartWith('55');
    }
});

it('desduplica phones repetidos', function () {
    [$tenant, $user] = stbTenantUser();
    $p1 = stbProspect($tenant, '11999990001');
    $p2 = stbProspect($tenant, '11999990001'); // mesmo phone

    $this->actingAs($user)->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => $p1->id . ',' . $p2->id,
    ]);

    $camp = BroadcastCampaign::first();
    expect(json_decode($camp->phones, true))->toHaveCount(1);
});

it('ignora prospects sem telefone', function () {
    [$tenant, $user] = stbTenantUser();
    $p1 = stbProspect($tenant, '11999990001');
    $p2 = Prospect::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Sem fone',
        'phone'     => null,
        'status'    => 'analyzed',
    ]);

    $this->actingAs($user)->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => $p1->id . ',' . $p2->id,
    ]);

    $camp = BroadcastCampaign::first();
    expect($camp->actual_recipients)->toBe(1);
});

it('marca prospects como contacted', function () {
    [$tenant, $user] = stbTenantUser();
    $p1 = stbProspect($tenant, '11999990001');

    $this->actingAs($user)->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => (string) $p1->id,
    ]);

    expect($p1->fresh()->status)->toBe('contacted');
});

it('rejeita quando nenhum lead selecionado', function () {
    [$tenant, $user] = stbTenantUser();

    $resp = $this->actingAs($user)->from('/prospecting')->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => '',
    ]);

    $resp->assertSessionHasErrors();
});

it('rejeita quando todos prospects nao tem telefone', function () {
    [$tenant, $user] = stbTenantUser();
    $p = Prospect::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Sem fone',
        'phone'     => null,
        'status'    => 'analyzed',
    ]);

    $resp = $this->actingAs($user)->from('/prospecting')->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => (string) $p->id,
    ]);

    $resp->assertRedirect('/prospecting');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('nao envia prospects de outro tenant', function () {
    [$tA, $userA] = stbTenantUser();
    [$tB, $userB] = stbTenantUser();
    $pB = stbProspect($tB, '11999990099');

    $this->actingAs($userA)->from('/prospecting')->post('/prospecting/send-to-broadcast', [
        'prospect_ids_raw' => (string) $pB->id,
    ]);

    // Nenhuma campanha no tenant A — prospect de B nao apareceu no scope
    expect(BroadcastCampaign::where('tenant_id', $tA->id)->count())->toBe(0);
});
