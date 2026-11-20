<?php

use App\Jobs\ProcessEvolutionWebhook;
use App\Models\Tenant;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/*
| Responder pelo chat: a conversa que o contato começou tem de poder ser
| respondida — pelo mesmo número por onde ele escreveu.
*/

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();
    $this->tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $this->evo = WhatsappInstance::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'open']);
    $this->config = WhatsappConfig::factory()->create([
        'tenant_id' => $this->tenant->id, 'enforce_24h_window' => true, 'require_opt_in' => true,
        'min_outbound_delay_seconds' => 0,
    ]);
    $this->inbound = function (string $text, string $jid = '5516999990000@s.whatsapp.net', array $key = []) {
        (new ProcessEvolutionWebhook($this->evo->id, 'messages.upsert', ['data' => [
            'key'      => array_merge(['remoteJid' => $jid, 'fromMe' => false, 'id' => 'wamid_' . uniqid()], $key),
            'pushName' => 'Maria',
            'message'  => ['conversation' => $text],
        ]]))->handle();
    };
});

it('"parece ótimo" não bloqueia o contato (antes "pare" dentro da palavra bloqueava)', function () {
    ($this->inbound)('Parece ótimo! Pode me mandar o endereço?');

    $chat = WhatsappChat::where('tenant_id', $this->tenant->id)->first();
    expect($chat->blocked_at)->toBeNull();
    expect($chat->opt_out_at)->toBeNull();
    expect(WhatsappBlacklist::where('tenant_id', $this->tenant->id)->count())->toBe(0);
    expect(WhatsappMessage::where('chat_id', $chat->id)->count())->toBe(1);
});

it('"parar" bloqueia; se a pessoa volta a escrever, a conversa é liberada', function () {
    ($this->inbound)('PARAR');
    $chat = WhatsappChat::where('tenant_id', $this->tenant->id)->first();
    expect($chat->blocked_at)->not->toBeNull();
    expect(WhatsappBlacklist::where('tenant_id', $this->tenant->id)->exists())->toBeTrue();

    ($this->inbound)('Oi, mudei de ideia, quero saber das oficinas');

    $chat->refresh();
    expect($chat->blocked_at)->toBeNull();
    expect($chat->opt_out_at)->toBeNull();
    expect(WhatsappBlacklist::where('tenant_id', $this->tenant->id)->exists())->toBeFalse();
    expect(WhatsappMessage::where('chat_id', $chat->id)->where('content', 'like', 'Oi, mudei%')->exists())->toBeTrue();
});

it('bloqueio manual da equipe continua valendo', function () {
    WhatsappBlacklist::create(['tenant_id' => $this->tenant->id, 'phone' => '5516999990000', 'reason' => 'Bloqueado pela equipe']);

    ($this->inbound)('Oi');

    expect(WhatsappMessage::count())->toBe(0);
});

it('contato com @lid vira o número real e a conversa antiga é aproveitada', function () {
    $old = WhatsappChat::factory()->create(['tenant_id' => $this->tenant->id, 'wa_id' => '276920536025393@lid']);

    ($this->inbound)('Bom dia', '276920536025393@lid', ['remoteJidAlt' => '5516988887777@s.whatsapp.net']);

    expect($old->fresh()->wa_id)->toBe('5516988887777');
    expect(WhatsappChat::where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('resposta sai pelo mesmo número por onde o contato escreveu, mesmo com a API oficial conectada', function () {
    Http::fake(['*' => Http::response(['key' => ['id' => 'ABC123']], 200)]);
    WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $this->tenant->id, 'status' => 'open']);

    ($this->inbound)('Olá, quero ser voluntária');
    $chat = WhatsappChat::where('tenant_id', $this->tenant->id)->first();
    expect($chat->whatsapp_instance_id)->toBe($this->evo->id);

    app(WhatsAppService::class)->sendMessage($chat->fresh(), $this->config, 'Que bom! Vou te passar as informações.');

    Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/sendText/' . $this->evo->instance_name));
    Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'graph.facebook.com'));
});

it('na Evolution a janela de 24h não impede responder conversa antiga', function () {
    Http::fake(['*' => Http::response(['key' => ['id' => 'ABC124']], 200)]);
    ($this->inbound)('Oi');
    $chat = WhatsappChat::where('tenant_id', $this->tenant->id)->first();
    $chat->forceFill(['last_inbound_at' => now()->subDays(3), 'last_outbound_at' => null])->save();

    $res = app(WhatsAppService::class)->sendMessage($chat->fresh(), $this->config, 'Desculpe a demora!');

    expect($res['provider'])->toBe('evolution');
});
