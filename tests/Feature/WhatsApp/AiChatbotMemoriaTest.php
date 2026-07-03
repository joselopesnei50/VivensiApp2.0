<?php

use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;

/**
 * Memória conversacional do chatbot (fix do "bot repete apresentação").
 * Sem isso o LLM recebia só a mensagem atual e cumprimentava a cada turno.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function chatMemTenant(): Tenant
{
    return Tenant::factory()->create();
}

function chatMemChat(Tenant $tenant): WhatsappChat
{
    return WhatsappChat::factory()->create([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511999990001',
        'contact_phone' => '5511999990001',
    ]);
}

function chatMemMsg(WhatsappChat $chat, string $direction, string $content): WhatsappMessage
{
    return WhatsappMessage::create([
        'tenant_id'  => $chat->tenant_id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(), // NOT NULL UNIQUE no schema
        'content'    => $content,
        'direction'  => $direction,
        'type'       => 'conversation',
    ]);
}

function chatMemInvokeHistory(int $chatId, string $currentMsg): array
{
    $job = new ProcessWhatsappAiResponse(0, $chatId, $currentMsg);
    $chat = WhatsappChat::find($chatId);
    $method = new \ReflectionMethod($job, 'buildHistoryMessages');
    $method->setAccessible(true);
    return $method->invoke($job, $chat, $currentMsg);
}

it('historico vem em ordem cronologica ascendente', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    chatMemMsg($chat, 'inbound', 'Oi');
    chatMemMsg($chat, 'outbound', 'Olá! Sou o Bruce');
    chatMemMsg($chat, 'inbound', 'Quero doar');
    chatMemMsg($chat, 'outbound', 'Ótimo! Pode usar este PIX');

    $hist = chatMemInvokeHistory($chat->id, 'Quanto vai pra alimentação?');

    expect($hist)->toHaveCount(4);
    expect($hist[0]['role'])->toBe('user');
    expect($hist[0]['content'])->toBe('Oi');
    expect($hist[1]['role'])->toBe('assistant');
    expect($hist[3]['content'])->toContain('PIX');
});

it('exclui a mensagem atual (evita duplicar quando caller adiciona depois)', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    chatMemMsg($chat, 'inbound', 'Oi');
    chatMemMsg($chat, 'outbound', 'Olá!');
    chatMemMsg($chat, 'inbound', 'Quero doar');

    $hist = chatMemInvokeHistory($chat->id, 'Quero doar');

    // A última inbound foi 'Quero doar' — deve sumir do histórico.
    expect($hist)->toHaveCount(2);
    expect(end($hist)['content'])->toBe('Olá!');
});

it('filtra marcadores de midia sem conteudo legivel', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    chatMemMsg($chat, 'inbound', 'Oi');
    chatMemMsg($chat, 'inbound', '[áudio]');
    chatMemMsg($chat, 'inbound', '[mensagem não suportada]');
    chatMemMsg($chat, 'outbound', 'Olá!');

    $hist = chatMemInvokeHistory($chat->id, '...');

    expect($hist)->toHaveCount(2);
    foreach ($hist as $m) {
        expect($m['content'])->not->toContain('[áudio]');
        expect($m['content'])->not->toContain('[mensagem não suportada]');
    }
});

it('trunca mensagem isolada gigante em 500 chars', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    chatMemMsg($chat, 'inbound', str_repeat('A', 1500));
    chatMemMsg($chat, 'outbound', 'ok');

    $hist = chatMemInvokeHistory($chat->id, '?');

    expect(mb_strlen($hist[0]['content']))->toBeLessThanOrEqual(501); // 500 + …
    expect($hist[0]['content'])->toEndWith('…');
});

it('limita historico a 20 turnos mesmo com 50 mensagens', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    for ($i = 1; $i <= 50; $i++) {
        $dir = $i % 2 === 0 ? 'outbound' : 'inbound';
        chatMemMsg($chat, $dir, "msg #{$i}");
    }

    $hist = chatMemInvokeHistory($chat->id, 'pergunta final');

    expect(count($hist))->toBeLessThanOrEqual(ProcessWhatsappAiResponse::HISTORY_TURNS_LIMIT);
});

it('chat novo retorna historico vazio', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);

    $hist = chatMemInvokeHistory($chat->id, 'Oi');

    expect($hist)->toBe([]);
});

it('mapeia direction outbound como assistant e inbound como user', function () {
    $tenant = chatMemTenant();
    $chat = chatMemChat($tenant);
    chatMemMsg($chat, 'inbound', 'pergunta');
    chatMemMsg($chat, 'outbound', 'resposta');

    $hist = chatMemInvokeHistory($chat->id, '...');

    expect($hist[0]['role'])->toBe('user');
    expect($hist[1]['role'])->toBe('assistant');
});
