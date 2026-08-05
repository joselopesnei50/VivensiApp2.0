<?php

use App\Jobs\ProcessEvolutionWebhook;
use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function wgroupPayload($remoteJid, $participant, $pushName, $body)
{
    return [
        'data' => [
            'key' => [
                'remoteJid'   => $remoteJid,
                'fromMe'      => false,
                'id'          => 'wamid_' . uniqid(),
                'participant' => $participant,
            ],
            'pushName' => $pushName,
            'message'  => ['conversation' => $body],
        ],
    ];
}

function wgroupInstance()
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return WhatsappInstance::factory()->create([
        'tenant_id' => $tenant->id,
        'status'    => 'open',
    ]);
}

it('inbound de grupo cria chat com is_group=true e nao usa pushName como nome do chat', function () {
    Queue::fake();
    $instance = wgroupInstance();
    WhatsappConfig::factory()->create(['tenant_id' => $instance->tenant_id, 'ai_enabled' => true]);

    $payload = wgroupPayload(
        '120363000000000001@g.us',
        '5511988887777@s.whatsapp.net',
        'Maria',
        'Bom dia grupo!'
    );

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', $payload))->handle();

    $chat = WhatsappChat::where('tenant_id', $instance->tenant_id)->first();
    expect($chat)->not->toBeNull();
    expect($chat->is_group)->toBeTrue();
    expect($chat->wa_id)->toBe('120363000000000001@g.us');
    expect($chat->contact_name)->toBe('Grupo WhatsApp');

    $msg = WhatsappMessage::where('chat_id', $chat->id)->first();
    expect($msg->sender_wa_id)->toBe('5511988887777');
    expect($msg->sender_name)->toBe('Maria');
    expect($msg->content)->toBe('Bom dia grupo!');
});

it('duas mensagens de participantes diferentes no mesmo grupo criam UM unico chat', function () {
    Queue::fake();
    $instance = wgroupInstance();

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', wgroupPayload(
        '120363000000000002@g.us', '5511911111111@s.whatsapp.net', 'Ana', 'oi'
    )))->handle();

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', wgroupPayload(
        '120363000000000002@g.us', '5511922222222@s.whatsapp.net', 'Bruno', 'ola'
    )))->handle();

    $chats = WhatsappChat::where('tenant_id', $instance->tenant_id)->get();
    expect($chats)->toHaveCount(1);
    expect($chats->first()->contact_name)->toBe('Grupo WhatsApp');

    $msgs = WhatsappMessage::where('chat_id', $chats->first()->id)->orderBy('id')->get();
    expect($msgs)->toHaveCount(2);
    expect($msgs[0]->sender_name)->toBe('Ana');
    expect($msgs[1]->sender_name)->toBe('Bruno');
});

it('bot NAO dispara em mensagem de grupo (mesmo com ai_enabled)', function () {
    Queue::fake();
    $instance = wgroupInstance();
    WhatsappConfig::factory()->create(['tenant_id' => $instance->tenant_id, 'ai_enabled' => true]);

    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', wgroupPayload(
        '120363000000000003@g.us', '5511933333333@s.whatsapp.net', 'Carla', 'oi bot'
    )))->handle();

    Queue::assertNotPushed(ProcessWhatsappAiResponse::class);
});

it('mensagem individual (nao grupo) continua criando chat sem is_group', function () {
    Queue::fake();
    $instance = wgroupInstance();

    $payload = [
        'data' => [
            'key' => [
                'remoteJid' => '5511944444444@s.whatsapp.net',
                'fromMe'    => false,
                'id'        => 'wamid_' . uniqid(),
            ],
            'pushName' => 'Diego',
            'message'  => ['conversation' => 'oi'],
        ],
    ];
    (new ProcessEvolutionWebhook($instance->id, 'messages.upsert', $payload))->handle();

    $chat = WhatsappChat::where('tenant_id', $instance->tenant_id)->first();
    expect($chat->is_group)->toBeFalse();
    expect($chat->wa_id)->toBe('5511944444444');
    expect($chat->contact_name)->toBe('Diego');
});
