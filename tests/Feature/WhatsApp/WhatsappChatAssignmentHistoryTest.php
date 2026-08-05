<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappChatAssignment;
use App\Services\Messaging\ChatAssignmentRecorder;
use App\Services\Messaging\ChatTransferService;

/**
 * P1 (2026-08-05) — tabela whatsapp_chat_assignments.
 *
 * Toda mudanca de assigned_to (take_over via assignChat, transfer, release)
 * grava um registro canonico. Assignment "aberto" tem ended_at=NULL. Ao criar
 * um novo, o anterior e fechado com duration_seconds preenchido. Release nao
 * abre novo — so fecha o aberto.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function chTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function chUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

it('assignChat cria assignment aberto com action=take_over', function () {
    $t    = chTenant();
    $agent = chUser($t, 'ngo');
    $chat = WhatsappChat::factory()->create([
        'tenant_id'   => $t->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($agent)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200);

    $assign = WhatsappChatAssignment::where('chat_id', $chat->id)->first();
    expect($assign)->not->toBeNull();
    expect($assign->action)->toBe(ChatAssignmentRecorder::ACTION_TAKE_OVER);
    expect($assign->to_user_id)->toBe($agent->id);
    expect($assign->from_user_id)->toBeNull();
    expect($assign->assigned_by_user_id)->toBe($agent->id);
    expect($assign->ended_at)->toBeNull();
    expect($assign->duration_seconds)->toBeNull();
});

it('transfer fecha o assignment anterior e abre um novo', function () {
    $t = chTenant();
    $a = chUser($t, 'ngo');
    $b = chUser($t, 'ngo');
    $manager = chUser($t, 'manager');

    $chat = WhatsappChat::factory()->create([
        'tenant_id'   => $t->id,
        'assigned_to' => null,
    ]);

    // 1) A assume
    $this->actingAs($a)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200);

    // 2) Manager transfere de A pra B
    $this->actingAs($manager)
        ->postJson("/whatsapp/chat/{$chat->id}/transfer", ['assigned_to' => $b->id])
        ->assertStatus(200);

    $all = WhatsappChatAssignment::where('chat_id', $chat->id)
        ->orderBy('id')
        ->get();

    expect($all)->toHaveCount(2);

    // Primeiro (take_over) foi fechado
    expect($all[0]->action)->toBe(ChatAssignmentRecorder::ACTION_TAKE_OVER);
    expect($all[0]->to_user_id)->toBe($a->id);
    expect($all[0]->ended_at)->not->toBeNull();
    expect($all[0]->duration_seconds)->toBeGreaterThanOrEqual(0);

    // Segundo (transfer) esta aberto, from=A to=B, actor=manager
    expect($all[1]->action)->toBe(ChatAssignmentRecorder::ACTION_TRANSFER);
    expect($all[1]->from_user_id)->toBe($a->id);
    expect($all[1]->to_user_id)->toBe($b->id);
    expect($all[1]->assigned_by_user_id)->toBe($manager->id);
    expect($all[1]->ended_at)->toBeNull();
});

it('release fecha o assignment aberto sem criar novo', function () {
    $t = chTenant();
    $a = chUser($t, 'ngo');

    $chat = WhatsappChat::factory()->create([
        'tenant_id'   => $t->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($a)
        ->postJson("/whatsapp/chat/{$chat->id}/assign")
        ->assertStatus(200);

    $this->actingAs($a)
        ->deleteJson("/whatsapp/chat/{$chat->id}/assignee")
        ->assertStatus(200);

    $all = WhatsappChatAssignment::where('chat_id', $chat->id)->get();

    // Nao virou 2 — release nao abre novo.
    expect($all)->toHaveCount(1);
    expect($all[0]->action)->toBe(ChatAssignmentRecorder::ACTION_TAKE_OVER);
    expect($all[0]->ended_at)->not->toBeNull();
    expect(WhatsappChatAssignment::open()->count())->toBe(0);
});

it('recorder retorna null em release e model em take_over/transfer', function () {
    $t = chTenant();
    $a = chUser($t, 'ngo');
    $chat = WhatsappChat::factory()->create([
        'tenant_id'   => $t->id,
        'assigned_to' => null,
    ]);

    $recorder = app(ChatAssignmentRecorder::class);

    $created = $recorder->record($chat, $a->id, $a, ChatAssignmentRecorder::ACTION_TAKE_OVER, null);
    expect($created)->toBeInstanceOf(WhatsappChatAssignment::class);
    expect($created->ended_at)->toBeNull();

    $released = $recorder->record($chat, null, $a, ChatAssignmentRecorder::ACTION_RELEASE, $a->id);
    expect($released)->toBeNull();

    expect(WhatsappChatAssignment::open()->where('chat_id', $chat->id)->count())->toBe(0);
});
