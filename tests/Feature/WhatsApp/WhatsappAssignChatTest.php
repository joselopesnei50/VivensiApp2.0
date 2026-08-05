<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappChat;

/**
 * P0.1 (2026-08-05) — assignChat: role guard + race guard atômico.
 * O endpoint antigo só pedia Gate::authorize('access-whatsapp') e fazia
 * update sem checar dono atual, o que permitia:
 *   1) employee se auto-atribuir (inconsistente com transferChat);
 *   2) segundo agente sobrescrever silenciosamente o primeiro.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function acTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function acUser(Tenant $tenant, string $role): User
{
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

it('employee é bloqueado com 403 ao tentar assumir', function () {
    $tenant   = acTenant();
    $employee = acUser($tenant, 'employee');
    $chat     = WhatsappChat::factory()->create([
        'tenant_id'   => $tenant->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($employee)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(403);

    expect($chat->fresh()->assigned_to)->toBeNull();
});

it('agente elegível assume chat livre e desliga o bot', function () {
    $tenant = acTenant();
    $ngo    = acUser($tenant, 'ngo');
    $chat   = WhatsappChat::factory()->create([
        'tenant_id'     => $tenant->id,
        'assigned_to'   => null,
        'status'        => 'open',
        'is_bot_active' => true,
    ]);

    $this->actingAs($ngo)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $fresh = $chat->fresh();
    expect($fresh->assigned_to)->toBe($ngo->id);
    expect($fresh->is_bot_active)->toBeFalse();
    expect($fresh->status)->toBe('human_attending');

    expect(WhatsappAuditLog::where('chat_id', $chat->id)
        ->where('event', 'chat_assigned')->count())->toBe(1);
});

it('assumir chat já atribuído a outro agente retorna 409 sem sobrescrever', function () {
    $tenant   = acTenant();
    $primeiro = acUser($tenant, 'ngo');
    $segundo  = acUser($tenant, 'ngo');
    $chat     = WhatsappChat::factory()->create([
        'tenant_id'   => $tenant->id,
        'assigned_to' => $primeiro->id,
        'status'      => 'human_attending',
    ]);

    $this->actingAs($segundo)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(409)
        ->assertJson(['success' => false]);

    expect($chat->fresh()->assigned_to)->toBe($primeiro->id);
});

it('assumir chat que já é seu é idempotente (200)', function () {
    $tenant = acTenant();
    $ngo    = acUser($tenant, 'ngo');
    $chat   = WhatsappChat::factory()->create([
        'tenant_id'   => $tenant->id,
        'assigned_to' => $ngo->id,
        'status'      => 'human_attending',
    ]);

    $this->actingAs($ngo)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($chat->fresh()->assigned_to)->toBe($ngo->id);
});

it('manager consegue assumir chat livre', function () {
    $tenant  = acTenant();
    $manager = acUser($tenant, 'manager');
    $chat    = WhatsappChat::factory()->create([
        'tenant_id'   => $tenant->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($manager)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200);

    expect($chat->fresh()->assigned_to)->toBe($manager->id);
});
