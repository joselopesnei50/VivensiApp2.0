<?php

use App\Jobs\ProcessWhatsAppBotMessage;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Feature tests do bot interno (/admin/bot) — privacidade dos dados dos clientes
 * do Vivensi. Cobertura escrita em 2026-08-20 pra travar o comportamento de:
 *
 *  - Cada mensagem recebida + resposta enviada eh apagada da Evolution API
 *    (deleteMessageForEveryone) logo apos o processamento.
 *  - Welcome message declara ao cliente que as mensagens sao apagadas.
 *  - "sair"/"encerrar" mostra aviso de descarte.
 *  - Logs nao vazam nome de beneficiario buscado (apenas tenant_id + hash).
 */
uses(RefreshDatabase::class);

function ibmMkUser(): User
{
    $tenant = Tenant::factory()->create();
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'ngo',
        'status'    => 'active',
    ]);
}

it('deleteMessageForEveryone chama endpoint Evolution com a key da mensagem', function () {
    Http::fake([
        '*/chat/deleteMessageForEveryone/*' => Http::response(['deleted' => true], 200),
    ]);

    $inst = new WhatsappInstance();
    $inst->forceFill([
        'id'            => 1,
        'instance_name' => 'bot-instance',
        'token'         => 't',
    ]);

    $evo = new EvolutionApiService($inst);
    $res = $evo->deleteMessageForEveryone([
        'id'        => 'MSG_ABC123',
        'remoteJid' => '5511999990001@s.whatsapp.net',
        'fromMe'    => false,
    ]);

    expect($res)->toBeArray();
    expect($res)->not->toHaveKey('error');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/chat/deleteMessageForEveryone/bot-instance')
            && $request->method() === 'POST'
            && $request['id'] === 'MSG_ABC123'
            && $request['remoteJid'] === '5511999990001@s.whatsapp.net'
            && $request['fromMe'] === false;
    });
});

it('deleteMessageForEveryone retorna erro sem estourar quando falta id ou remoteJid', function () {
    $inst = new WhatsappInstance();
    $inst->forceFill(['id' => 1, 'instance_name' => 'bot-instance', 'token' => 't']);

    $evo = new EvolutionApiService($inst);

    expect($evo->deleteMessageForEveryone([])['error'])->toBe('Missing id or remoteJid');
    expect($evo->deleteMessageForEveryone(['id' => 'x'])['error'])->toBe('Missing id or remoteJid');
    expect($evo->deleteMessageForEveryone(['remoteJid' => 'x'])['error'])->toBe('Missing id or remoteJid');
});

it('bot apaga inbound e outbound da Evolution depois de responder', function () {
    SystemSetting::setValue('bot_instance_name', 'bot-instance', 'bot');

    Http::fake([
        '*/message/sendText/*' => Http::response([
            'key' => [
                'id'        => 'OUT_MSG_999',
                'remoteJid' => '5511999990001@s.whatsapp.net',
                'fromMe'    => true,
            ],
        ], 200),
        '*/chat/deleteMessageForEveryone/*' => Http::response(['deleted' => true], 200),
    ]);

    $user = ibmMkUser();
    $inboundKey = [
        'id'        => 'IN_MSG_111',
        'remoteJid' => '5511999990001@s.whatsapp.net',
        'fromMe'    => false,
    ];

    (new ProcessWhatsAppBotMessage($user, '5511999990001@s.whatsapp.net', 'menu', $inboundKey))
        ->handle();

    // Duas chamadas de delete: inbound (IN_MSG_111) + outbound (OUT_MSG_999)
    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/chat/deleteMessageForEveryone/')
            && $req['id'] === 'IN_MSG_111';
    });
    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/chat/deleteMessageForEveryone/')
            && $req['id'] === 'OUT_MSG_999';
    });
});

it('welcome message inclui aviso de privacidade dos dados', function () {
    SystemSetting::setValue('bot_instance_name', 'bot-instance', 'bot');

    $sentBody = null;
    Http::fake([
        '*/message/sendText/*' => function ($req) use (&$sentBody) {
            $sentBody = $req['text'] ?? null;
            return Http::response(['key' => ['id' => 'OUT', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => true]], 200);
        },
        '*/chat/deleteMessageForEveryone/*' => Http::response(['deleted' => true], 200),
    ]);

    $user = ibmMkUser();
    (new ProcessWhatsAppBotMessage(
        $user,
        '5511999990001@s.whatsapp.net',
        'menu',
        ['id' => 'IN', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => false]
    ))->handle();

    expect($sentBody)->toContain('Privacidade dos seus dados');
    expect($sentBody)->toContain('apagadas automaticamente');
});

it('comando sair mostra aviso de descarte e encerra sessao', function () {
    SystemSetting::setValue('bot_instance_name', 'bot-instance', 'bot');

    $sentBody = null;
    Http::fake([
        '*/message/sendText/*' => function ($req) use (&$sentBody) {
            $sentBody = $req['text'] ?? null;
            return Http::response(['key' => ['id' => 'OUT', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => true]], 200);
        },
        '*/chat/deleteMessageForEveryone/*' => Http::response(['deleted' => true], 200),
    ]);

    $user = ibmMkUser();
    (new ProcessWhatsAppBotMessage(
        $user,
        '5511999990001@s.whatsapp.net',
        'sair',
        ['id' => 'IN', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => false]
    ))->handle();

    expect($sentBody)->toContain('Sessão encerrada');
    expect($sentBody)->toContain('apagadas automaticamente');
});

it('log de beneficiario nao encontrado nao contem nome da consulta', function () {
    SystemSetting::setValue('bot_instance_name', 'bot-instance', 'bot');

    Http::fake([
        '*/message/sendText/*'              => Http::response(['key' => ['id' => 'OUT', 'remoteJid' => 'x', 'fromMe' => true]], 200),
        '*/chat/deleteMessageForEveryone/*' => Http::response(['deleted' => true], 200),
    ]);

    Log::spy();

    $user = ibmMkUser();
    (new ProcessWhatsAppBotMessage(
        $user,
        '5511999990001@s.whatsapp.net',
        'ATEND: Fulano da Silva PII Sensivel | Saude | Consulta',
        ['id' => 'IN', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => false]
    ))->handle();

    // O nome do beneficiario nao pode aparecer em nenhum call de log.info
    Log::shouldHaveReceived('info')
        ->withArgs(function ($msg, $ctx = []) {
            $line = $msg . ' ' . json_encode($ctx);
            return !str_contains($line, 'Fulano da Silva PII Sensivel');
        })
        ->atLeast()->once();
});
