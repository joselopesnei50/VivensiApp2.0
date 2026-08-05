<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\Messaging\ChatTransferService;

/**
 * P2 (2026-08-05) — endpoint POST /me/agent-availability + presenca do
 * agent_availability em eligibleAgents.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function avTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function avUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

it('agente elegivel atualiza propria disponibilidade', function () {
    $t = avTenant();
    $u = avUser($t, 'ngo');

    // default do banco e 'available' — confirma.
    expect($u->fresh()->agent_availability)->toBe(User::AVAILABILITY_AVAILABLE);

    $this->actingAs($u)
        ->postJson('/me/agent-availability', ['availability' => 'away'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'agent_availability' => 'away']);

    $fresh = $u->fresh();
    expect($fresh->agent_availability)->toBe('away');
    expect($fresh->availability_changed_at)->not->toBeNull();
});

it('endpoint rejeita valor invalido com 422', function () {
    $t = avTenant();
    $u = avUser($t, 'ngo');

    $this->actingAs($u)
        ->postJson('/me/agent-availability', ['availability' => 'banana'])
        ->assertStatus(422);

    expect($u->fresh()->agent_availability)->toBe(User::AVAILABILITY_AVAILABLE);
});

it('endpoint rejeita role fora de AGENT_ROLES com 403', function () {
    $t = avTenant();
    $emp = avUser($t, 'employee');

    $this->actingAs($emp)
        ->postJson('/me/agent-availability', ['availability' => 'away'])
        ->assertStatus(403);
});

it('eligibleAgents inclui agent_availability nos dados', function () {
    $t = avTenant();
    $a = avUser($t, 'ngo');
    $b = avUser($t, 'ngo');

    // b esta away
    $b->forceFill(['agent_availability' => 'away'])->save();

    $svc     = app(ChatTransferService::class);
    $agents  = $svc->eligibleAgents($t->id);
    $byId    = $agents->keyBy('id');

    expect($byId[$a->id]->agent_availability)->toBe('available');
    expect($byId[$b->id]->agent_availability)->toBe('away');
});
