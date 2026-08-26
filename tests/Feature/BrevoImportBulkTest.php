<?php

use App\Models\SystemSetting;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Http;

/**
 * Trava a Fase A do fix de disparo grande (20K+):
 * BrevoService::importContacts agora usa bulk async /contacts/import com
 * polling do processId (antes: 1-a-1 POST /contacts, ~50min pra 20K).
 */

beforeEach(function () {
    SystemSetting::setValue('brevo_api_key', 'test-brevo-key');
});

it('importContacts usa bulk /contacts/import + polling e retorna qtd de contatos', function () {
    Http::fake([
        'api.brevo.com/v3/contacts/import' => Http::response(['processId' => 999], 202),
        'api.brevo.com/v3/processes/999'   => Http::response(['status' => 'completed'], 200),
    ]);

    $contacts = [
        ['email' => 'a@a.com', 'name' => 'A'],
        ['email' => 'b@b.com', 'name' => 'B'],
        ['email' => 'c@c.com'],
    ];

    $added = (new BrevoService())->importContacts(123, $contacts);

    expect($added)->toBe(3);

    // Confirma que fez bulk POST + poll status
    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/contacts/import')
            && ($req->data()['listIds'] ?? []) === [123]
            && count($req->data()['jsonBody'] ?? []) === 3
            && ($req->data()['updateExistingContacts'] ?? null) === true;
    });
    Http::assertSent(fn ($r) => str_contains($r->url(), '/processes/999'));
});

it('importContacts chunka em 5K quando ha mais que isso', function () {
    Http::fake([
        'api.brevo.com/v3/contacts/import' => Http::sequence()
            ->push(['processId' => 111], 202)
            ->push(['processId' => 222], 202),
        'api.brevo.com/v3/processes/111'   => Http::response(['status' => 'completed'], 200),
        'api.brevo.com/v3/processes/222'   => Http::response(['status' => 'completed'], 200),
    ]);

    // 7500 contatos = 2 chunks (5000 + 2500)
    $contacts = array_map(fn ($i) => ['email' => "u{$i}@x.com"], range(1, 7500));

    $added = (new BrevoService())->importContacts(456, $contacts);
    expect($added)->toBe(7500);

    // Contou 2 imports + 2 polls
    Http::assertSentCount(4);
});

it('importContacts cai pra fallback sequencial quando bulk falha', function () {
    Http::fake([
        'api.brevo.com/v3/contacts/import' => Http::response(['error' => 'internal'], 500),
        // fallback: cria contato 1 a 1
        'api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
    ]);

    $contacts = [['email' => 'a@a.com'], ['email' => 'b@b.com']];

    $added = (new BrevoService())->importContacts(1, $contacts);

    expect($added)->toBe(2);

    // 1 tentativa bulk (falhou) + 2 individuais (fallback)
    Http::assertSent(fn ($r) => str_contains($r->url(), '/contacts/import'));
    Http::assertSent(fn ($r) => $r->url() === 'https://api.brevo.com/v3/contacts');
});

it('importContacts cai pra fallback quando process falha durante polling', function () {
    Http::fake([
        'api.brevo.com/v3/contacts/import' => Http::response(['processId' => 42], 202),
        'api.brevo.com/v3/processes/42'    => Http::response(['status' => 'failed'], 200),
        'api.brevo.com/v3/contacts'        => Http::response(['id' => 1], 201),
    ]);

    $added = (new BrevoService())->importContacts(9, [['email' => 'x@y.com']]);
    expect($added)->toBe(1); // fallback rodou
});

it('importContacts retorna 0 pra lista vazia sem chamar Brevo', function () {
    Http::fake();
    $added = (new BrevoService())->importContacts(1, []);
    expect($added)->toBe(0);
    Http::assertNothingSent();
});
