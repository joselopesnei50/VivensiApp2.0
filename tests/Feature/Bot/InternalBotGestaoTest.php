<?php

use App\Jobs\ProcessWhatsAppBotMessage;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Bot de gestão interna (2026-11-22): beneficiário sem ambiguidade,
 * permissões do painel, bloqueio da conta, lançamentos pelo FinanceService
 * e telefone em mais de uma conta.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    config(['whatsapp.evolution_global_key' => 'chave-teste', 'whatsapp.evolution_api_url' => 'https://evo.teste']);
    SystemSetting::setValue('bot_instance_name', 'bot-instance', 'bot');
    Bus::fake([\App\Jobs\PurgeBotMessages::class]);
    Mail::fake();
    $GLOBALS['botSent'] = [];
    Http::fake([
        '*/message/sendText/*' => function ($req) {
            $GLOBALS['botSent'][] = $req['text'] ?? '';

            return Http::response(['key' => ['id' => 'OUT', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => true]], 200);
        },
        '*' => Http::response([], 200),
    ]);
});

function bgUser(string $role = 'ngo', array $tenant = []): User
{
    $t = Tenant::factory()->create(array_merge(['type' => 'ngo'], $tenant));

    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role, 'status' => 'active']);
}

function bgSay(User $u, string $text): string
{
    (new ProcessWhatsAppBotMessage($u->fresh(), '5511999990001@s.whatsapp.net', $text, ['id' => 'IN', 'remoteJid' => '5511@s.whatsapp.net', 'fromMe' => false]))->handle();

    return (string) end($GLOBALS['botSent']);
}

function bgBenef(User $u, string $name): Beneficiary
{
    return Beneficiary::forceCreate(['tenant_id' => $u->tenant_id, 'name' => $name]);
}

it('nome com mais de um beneficiário mostra lista e grava no escolhido', function () {
    $u = bgUser();
    $ana = bgBenef($u, 'Ana Paula (exemplo)');
    $mari = bgBenef($u, 'Mariana Souza (exemplo)');

    $reply = bgSay($u, 'EVOL: Ana | Veio à oficina');
    expect($reply)->toContain('mais de um beneficiário')->toContain('1. ')->toContain('2. ');
    expect(Attendance::count())->toBe(0);

    $ids = Cache::get("bot_session_{$u->id}")['ids'];
    $pick = array_search($mari->id, $ids, true) + 1;
    expect(bgSay($u, (string) $pick))->toContain('Evolução registrada')->toContain('Mariana');
    expect(Attendance::where('beneficiary_id', $mari->id)->count())->toBe(1);
    expect(Attendance::where('beneficiary_id', $ana->id)->count())->toBe(0);
});

it('nome exatamente igual escolhe direto mesmo com outros parecidos', function () {
    $u = bgUser();
    $ana = bgBenef($u, 'Ana');
    bgBenef($u, 'Mariana');

    expect(bgSay($u, 'ATEND: Ana | Saúde | Consulta'))->toContain('Atendimento registrado');
    expect(Attendance::where('beneficiary_id', $ana->id)->count())->toBe(1);
});

it('registro de atendimento avisa logo no primeiro passo se não achar o beneficiário', function () {
    $u = bgUser();
    bgSay($u, '3');
    expect(bgSay($u, 'Ninguém Cadastrado'))->toContain('não encontrado');
    expect(Cache::get("bot_session_{$u->id}")['state'])->toBe('atend_nome');
});

it('colaborador não vê o financeiro nem lança receita pelo bot', function () {
    $u = bgUser('employee');
    expect(bgSay($u, '1'))->toContain('só para administração');
    expect(bgSay($u, 'RECV: 500,00 | Doação'))->toContain('só para administração');
    expect(Transaction::count())->toBe(0);
});

it('despesa do colaborador vai para aprovação; a do administrador não', function () {
    $emp = bgUser('employee');
    expect(bgSay($emp, 'DESP: 150,50 | Lanche'))->toContain('Aguardando aprovação');
    $t = Transaction::where('tenant_id', $emp->tenant_id)->first();
    expect((float) $t->amount)->toBe(150.5)->and($t->approval_status)->toBe('pending')->and($t->status)->toBe('pending');

    $adm = bgUser('ngo');
    expect(bgSay($adm, 'DESP: 1.200,00 | Instrumentos'))->not->toContain('Aguardando aprovação');
    $t2 = Transaction::where('tenant_id', $adm->tenant_id)->first();
    expect((float) $t2->amount)->toBe(1200.0)->and($t2->approval_status)->toBe('approved')->and($t2->status)->toBe('paid');
});

it('conta suspensa não usa o bot', function () {
    $u = bgUser('ngo', ['subscription_status' => 'suspended']);
    expect(bgSay($u, 'DESP: 10,00 | Teste'))->toContain('Conta com pendência');
    expect(Transaction::count())->toBe(0);
});

it('teste vencido não usa o bot', function () {
    $u = bgUser('manager', ['subscription_status' => 'trialing', 'trial_ends_at' => now()->subDay()]);
    expect(bgSay($u, '1'))->toContain('Fale com o administrador');
});

it('número em duas contas é recusado; DDD diferente não confunde', function () {
    \Illuminate\Support\Facades\Queue::fake();
    SystemSetting::setValue('bot_enabled', '1');
    config(['services.whatsapp.bot_secret' => 'segredo-123']);
    $payload = fn ($n) => ['event' => 'messages.upsert', 'data' => ['key' => ['remoteJid' => "{$n}@s.whatsapp.net", 'fromMe' => false, 'id' => 'X'], 'message' => ['conversation' => 'oi']]];

    $a = bgUser();
    $a->update(['phone' => '(16) 99999-1234']);
    $b = bgUser();
    $b->update(['phone' => '16999991234']);
    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('5516999991234'))->assertJson(['status' => 'ambiguous']);

    $c = bgUser();
    $c->update(['phone' => '(11) 98888-5678']);
    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('5521988885678'))->assertJson(['status' => 'unauthorized']);
    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('551188885678'))->assertJson(['status' => 'queued']);
});
