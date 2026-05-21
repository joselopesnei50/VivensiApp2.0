<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappAuditLog;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── helpers ───────────────────────────────────────────────────────────────────

function waUser(string $role = 'manager'): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
    return [$user, $tenant];
}

// ── auth guard ────────────────────────────────────────────────────────────────

it('guest cannot access whatsapp chat send endpoint', function () {
    $this->postJson('/whatsapp/chat/send', ['chat_id' => 1, 'message' => 'test'])
        ->assertStatus(401);
});

it('guest cannot start a whatsapp chat', function () {
    $this->postJson('/whatsapp/chat/start', ['phone' => '5511999990000'])
        ->assertStatus(401);
});

// ── startChat ─────────────────────────────────────────────────────────────────

it('startChat returns 422 for invalid phone', function () {
    [$user] = waUser();

    $this->actingAs($user)
        ->postJson('/whatsapp/chat/start', ['phone' => 'abc-xyz'])
        ->assertStatus(422)
        ->assertJsonFragment(['error' => 'Telefone inválido']);
});

it('startChat creates chat and returns chat_id', function () {
    [$user, $tenant] = waUser();

    $response = $this->actingAs($user)
        ->postJson('/whatsapp/chat/start', [
            'phone' => '5511987654321',
            'name'  => 'Teste HTTP',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['chat_id']);

    $this->assertDatabaseHas('whatsapp_chats', [
        'tenant_id' => $tenant->id,
        'wa_id'     => '5511987654321',
    ]);
});

it('startChat does not duplicate existing chat', function () {
    [$user, $tenant] = waUser();

    WhatsappChat::factory()->create([
        'tenant_id' => $tenant->id,
        'wa_id'     => '5521900001111',
    ]);

    $this->actingAs($user)
        ->postJson('/whatsapp/chat/start', ['phone' => '5521900001111'])
        ->assertStatus(200);

    $this->assertDatabaseCount('whatsapp_chats', 1);
});

// ── sendMessage — validation ──────────────────────────────────────────────────

it('sendMessage returns 404 when chat belongs to another tenant', function () {
    [$user]      = waUser();
    [$otherUser] = waUser();

    $otherChat = WhatsappChat::factory()->create(['tenant_id' => $otherUser->tenant_id]);

    $this->actingAs($user)
        ->postJson('/whatsapp/chat/send', [
            'chat_id' => $otherChat->id,
            'message' => 'Invadindo tenant alheio',
        ])
        ->assertStatus(404);
});

it('sendMessage returns 422 when chat is blocked', function () {
    [$user, $tenant] = waUser();

    WhatsappConfig::factory()->create(['tenant_id' => $tenant->id]);
    $chat = WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'blocked_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/whatsapp/chat/send', [
            'chat_id' => $chat->id,
            'message' => 'Olá bloqueado',
        ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['code' => 'CONTACT_BLOCKED']);
});

it('sendMessage records audit log for blocked send', function () {
    [$user, $tenant] = waUser();

    WhatsappConfig::factory()->create(['tenant_id' => $tenant->id]);
    $chat = WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'blocked_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson('/whatsapp/chat/send', [
            'chat_id' => $chat->id,
            'message' => 'Teste audit',
        ]);

    $this->assertDatabaseHas('whatsapp_audit_logs', [
        'tenant_id'     => $tenant->id,
        'chat_id'       => $chat->id,
        'event'         => 'outbound_blocked',
        'actor_user_id' => $user->id,
    ]);
});

it('sendMessage returns 422 when chat has opted out', function () {
    [$user, $tenant] = waUser();

    WhatsappConfig::factory()->create(['tenant_id' => $tenant->id]);
    $chat = WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'opt_out_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->postJson('/whatsapp/chat/send', [
            'chat_id' => $chat->id,
            'message' => 'Tentando após opt-out',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['code' => 'CONTACT_OPTOUT']);
});

// ── compliance ────────────────────────────────────────────────────────────────

it('compliance opt_out marks chat as closed', function () {
    [$user, $tenant] = waUser('manager');

    $chat = WhatsappChat::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->postJson("/whatsapp/chat/{$chat->id}/compliance", [
            'action' => 'opt_out',
            'reason' => 'Pedido do cliente',
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    $chat->refresh();
    expect($chat->status)->toBe('closed')
        ->and($chat->opt_out_at)->not->toBeNull();
});

it('compliance block action marks chat as closed', function () {
    [$user, $tenant] = waUser('manager');
    $chat = WhatsappChat::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->postJson("/whatsapp/chat/{$chat->id}/compliance", [
            'action' => 'block',
            'reason' => 'Spam',
        ])
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $chat->refresh();
    expect($chat->blocked_at)->not->toBeNull()
        ->and($chat->blocked_reason)->toBe('Spam');
});

it('compliance unblock clears blocked_at', function () {
    [$user, $tenant] = waUser('manager');
    $chat = WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'blocked_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/whatsapp/chat/{$chat->id}/compliance", ['action' => 'unblock'])
        ->assertOk();

    $chat->refresh();
    expect($chat->blocked_at)->toBeNull();
});

// ── multi-tenant isolation ────────────────────────────────────────────────────

it('cannot access messages of another tenant chat', function () {
    [$user]      = waUser();
    [$otherUser] = waUser();

    $otherChat = WhatsappChat::factory()->create(['tenant_id' => $otherUser->tenant_id]);

    $this->actingAs($user)
        ->getJson("/whatsapp/chat/{$otherChat->id}/messages")
        ->assertStatus(404);
});

it('cannot modify compliance of another tenant chat', function () {
    [$user]      = waUser('manager');
    [$otherUser] = waUser('manager');

    $otherChat = WhatsappChat::factory()->create(['tenant_id' => $otherUser->tenant_id]);

    $this->actingAs($user)
        ->postJson("/whatsapp/chat/{$otherChat->id}/compliance", ['action' => 'block'])
        ->assertStatus(404);
});
