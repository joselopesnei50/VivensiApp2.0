<?php

use App\Models\EmailCampaign;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao LGPD art. 8/18 (2026-09-29) — bug reportado pelo cliente:
 * contatos descadastrados via link no rodape do e-mail continuavam
 * recebendo campanhas seguintes. Safety net em resolveRecipients dos
 * 3 paineis (NGO/Admin/Manager) exclui qualquer email com status
 * 'unsubscribed' NO tenant antes de disparar.
 *
 * Cenarios cobertos:
 *  - Mesmo email em duas listas do tenant, descadastrou em uma via link
 *    (o link marca TODAS as ocorrencias) -> NAO recebe pela outra lista
 *  - Email descadastrado + CSV reimportado que criou uma nova linha
 *    'active' em outra lista -> NAO recebe (safety net cobre)
 *  - Email vindo por audience_type=leads/donors/manual -> tambem excluido
 *    se tiver marcado 'unsubscribed' em alguma lista do tenant
 */

uses(RefreshDatabase::class);

function safetyTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function safetyContact(Tenant $t, string $email, string $status = 'active'): EmailContact
{
    $list = EmailContactList::create([
        'tenant_id'           => $t->id,
        'name'                => 'Lista ' . uniqid(),
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
    ]);
    return EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $t->id,
        'email'                 => mb_strtolower($email),
        'name'                  => 'Foo',
        'status'                => $status,
        'source'                => 'csv_upload',
        'added_at'              => now(),
    ]);
}

it('unsubscribedLookup retorna emails descadastrados do tenant', function () {
    $t = safetyTenant();
    safetyContact($t, 'ativo@x.com', 'active');
    safetyContact($t, 'desativo@x.com', 'unsubscribed');

    $lookup = EmailContact::unsubscribedLookup($t->id, [
        'ativo@x.com',
        'desativo@x.com',
        'nunca-existiu@x.com',
    ]);

    expect($lookup)
        ->toHaveKey('desativo@x.com')
        ->not->toHaveKey('ativo@x.com')
        ->not->toHaveKey('nunca-existiu@x.com');
});

it('unsubscribedLookup respeita tenant scope quando tenantId > 0', function () {
    $t1 = safetyTenant();
    $t2 = safetyTenant();
    safetyContact($t1, 'joao@x.com', 'unsubscribed');
    safetyContact($t2, 'joao@x.com', 'active');

    // Perspectiva do tenant 1: joao esta bloqueado
    $lookup1 = EmailContact::unsubscribedLookup($t1->id, ['joao@x.com']);
    expect($lookup1)->toHaveKey('joao@x.com');

    // Perspectiva do tenant 2: mesmo email nao esta bloqueado la
    $lookup2 = EmailContact::unsubscribedLookup($t2->id, ['joao@x.com']);
    expect($lookup2)->not->toHaveKey('joao@x.com');
});

it('unsubscribedLookup com tenantId=0 checa TODOS os tenants (admin SaaS)', function () {
    $t1 = safetyTenant();
    $t2 = safetyTenant();
    safetyContact($t1, 'quer-sair@x.com', 'unsubscribed');
    safetyContact($t2, 'so-ativo@x.com',  'active');

    $lookup = EmailContact::unsubscribedLookup(0, ['quer-sair@x.com', 'so-ativo@x.com']);
    expect($lookup)
        ->toHaveKey('quer-sair@x.com')
        ->not->toHaveKey('so-ativo@x.com');
});

it('unsubscribedLookup normaliza case-insensitive', function () {
    $t = safetyTenant();
    safetyContact($t, 'joao@x.com', 'unsubscribed');

    $lookup = EmailContact::unsubscribedLookup($t->id, ['JOAO@X.COM', ' Joao@X.Com ']);
    expect($lookup)->toHaveKey('joao@x.com');
});

it('unsubscribedLookup com array vazio nao consulta DB', function () {
    $lookup = EmailContact::unsubscribedLookup(1, []);
    expect($lookup)->toBe([]);
});

// ── Cenario cliente: email em 2 listas, unsub em uma, safety net cobre ──

it('NGO resolveRecipients EXCLUI email unsubscribed em outra lista do tenant', function () {
    $t = safetyTenant();

    // Lista A: contato ativo
    $listA = EmailContactList::create([
        'tenant_id' => $t->id,
        'name'      => 'Lista A',
        'opt_in_confirmed' => true,
        'opt_in_confirmed_at' => now(),
    ]);
    EmailContact::create([
        'email_contact_list_id' => $listA->id,
        'tenant_id'             => $t->id,
        'email'                 => 'unsubbed@x.com',
        'status'                => 'active', // ativo NA LISTA A
        'source'                => 'csv_upload',
        'added_at'              => now(),
    ]);

    // Lista B: MESMO email, mas descadastrado (simula link no rodape marcando
    // TODAS as ocorrencias — mas por bug antigo, ficou marcado so na Lista B)
    $listB = EmailContactList::create([
        'tenant_id' => $t->id,
        'name'      => 'Lista B',
        'opt_in_confirmed' => true,
        'opt_in_confirmed_at' => now(),
    ]);
    EmailContact::create([
        'email_contact_list_id' => $listB->id,
        'tenant_id'             => $t->id,
        'email'                 => 'unsubbed@x.com',
        'status'                => 'unsubscribed',
        'source'                => 'csv_upload',
        'added_at'              => now(),
        'unsubscribed_at'       => now(),
    ]);

    // Cria user common + campanha na Lista A (onde contato esta 'active')
    $user = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $campaign = EmailCampaign::create([
        'tenant_id'             => $t->id,
        'created_by'            => $user->id,
        'name'                  => 'Campanha teste A',
        'subject'               => 'Teste',
        'html_content'          => 'body',
        'audience_type'         => 'contact_list',
        'email_contact_list_id' => $listA->id,
        'status'                => 'draft',
    ]);

    // Simular resolveRecipients via reflection
    $controller = new \App\Http\Controllers\Ngo\NgoEmailCampaignController();
    $method = new \ReflectionMethod($controller, 'resolveRecipients');
    $method->setAccessible(true);

    // resolveRecipients usa auth()->user()->tenant_id
    $this->actingAs($user);
    $recipients = $method->invoke($controller, $campaign);

    // Safety net deve ter EXCLUIDO unsubbed@x.com porque status='unsubscribed'
    // existe em OUTRA lista do mesmo tenant.
    $emails = collect($recipients)->pluck('email')->all();
    expect($emails)->not->toContain('unsubbed@x.com');
});

it('NGO resolveRecipients EXCLUI email manual se ja descadastrou no tenant', function () {
    $t = safetyTenant();
    safetyContact($t, 'jose@x.com', 'unsubscribed');

    $user = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $campaign = EmailCampaign::create([
        'tenant_id'     => $t->id,
        'created_by'    => $user->id,
        'name'          => 'Campanha manual',
        'subject'       => 'Teste manual',
        'html_content'  => 'body',
        'audience_type' => 'manual',
        'manual_emails' => json_encode([
            ['email' => 'jose@x.com', 'name' => 'Jose'],
            ['email' => 'novato@x.com', 'name' => 'Novato'],
        ]),
        'status'        => 'draft',
    ]);

    $this->actingAs($user);

    $controller = new \App\Http\Controllers\Ngo\NgoEmailCampaignController();
    $method = new \ReflectionMethod($controller, 'resolveRecipients');
    $method->setAccessible(true);
    $recipients = $method->invoke($controller, $campaign);

    $emails = collect($recipients)->pluck('email')->all();
    expect($emails)
        ->not->toContain('jose@x.com')
        ->toContain('novato@x.com');
});
