<?php

use App\Jobs\ProcessEvolutionWebhook;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappChatAssignment;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Services\Messaging\ChatAutoAssignRouter;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function arSetup($mode = 'off')
{
    Queue::fake();
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $instance = WhatsappInstance::factory()->create([
        'tenant_id' => $tenant->id, 'status' => 'open',
    ]);
    WhatsappConfig::factory()->create([
        'tenant_id'        => $tenant->id,
        'ai_enabled'       => false,
        'auto_assign_mode' => $mode,
    ]);
    return [$tenant, $instance];
}

function arPayload($fromPhone, $body = 'oi', $pushName = 'Cliente Teste')
{
    return [
        'data' => [
            'key' => [
                'remoteJid' => $fromPhone . '@s.whatsapp.net',
                'fromMe'    => false,
                'id'        => 'wamid_' . uniqid(),
            ],
            'pushName' => $pushName,
            'message'  => ['conversation' => $body],
        ],
    ];
}

it('mode=off nao atribui automaticamente', function () {
    [$tenant, $instance] = arSetup('off');
    User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990001')))->handle();

    $chat = WhatsappChat::where('tenant_id', $tenant->id)->first();
    expect($chat->assigned_to)->toBeNull();
});

it('round_robin atribui ao unico agente disponivel', function () {
    [$tenant, $instance] = arSetup('round_robin');
    $agent = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990002')))->handle();

    $chat = WhatsappChat::where('tenant_id', $tenant->id)->first();
    expect($chat->assigned_to)->toBe($agent->id);
    expect($chat->status)->toBe('human_attending');

    // Assignment historico foi gravado com actor null (sistema).
    $a = WhatsappChatAssignment::where('chat_id', $chat->id)->first();
    expect($a)->not->toBeNull();
    expect($a->to_user_id)->toBe($agent->id);
    expect($a->assigned_by_user_id)->toBeNull();
});

it('round_robin alterna entre agentes por last_auto_assigned_at ASC', function () {
    [$tenant, $instance] = arSetup('round_robin');
    $a = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);
    $b = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    // 3 mensagens de contatos diferentes (chats novos).
    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990010')))->handle();
    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990011')))->handle();
    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990012')))->handle();

    $chats = WhatsappChat::where('tenant_id', $tenant->id)
        ->orderBy('id')->get();

    // Padrao esperado: a, b, a (round-robin sobre 2 agentes).
    expect($chats[0]->assigned_to)->toBe($a->id);
    expect($chats[1]->assigned_to)->toBe($b->id);
    expect($chats[2]->assigned_to)->toBe($a->id);
});

it('round_robin ignora agentes com availability=offline ou away', function () {
    [$tenant, $instance] = arSetup('round_robin');
    User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => 'offline',
    ]);
    User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => 'away',
    ]);
    $available = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', arPayload('5511999990020')))->handle();

    $chat = WhatsappChat::where('tenant_id', $tenant->id)->first();
    expect($chat->assigned_to)->toBe($available->id);
});

it('round_robin nao atribui grupo (chat.is_group=true)', function () {
    [$tenant, $instance] = arSetup('round_robin');
    User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'ngo',
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    $groupPayload = [
        'data' => [
            'key' => [
                'remoteJid'   => '120363000000000099@g.us',
                'fromMe'      => false,
                'id'          => 'wamid_' . uniqid(),
                'participant' => '5511999990030@s.whatsapp.net',
            ],
            'pushName' => 'Maria',
            'message'  => ['conversation' => 'oi grupo'],
        ],
    ];
    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', $groupPayload))->handle();

    $chat = WhatsappChat::where('tenant_id', $tenant->id)->first();
    expect($chat->is_group)->toBeTrue();
    expect($chat->assigned_to)->toBeNull();
});

it('router retorna null quando nao ha agente disponivel', function () {
    [$tenant] = arSetup('round_robin');
    User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'employee', // nao esta em AGENT_ROLES
        'agent_availability' => User::AVAILABILITY_AVAILABLE,
    ]);

    $chat = WhatsappChat::factory()->create([
        'tenant_id' => $tenant->id, 'assigned_to' => null,
    ]);

    $result = app(ChatAutoAssignRouter::class)->assign($chat);
    expect($result)->toBeNull();
    expect($chat->fresh()->assigned_to)->toBeNull();
});
