<?php

use App\Models\AuditLog;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\NgoDonor;
use App\Models\Tenant;
use App\Services\EmailUnsubscribeTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeContact(Tenant $tenant, string $email, string $status = 'active'): EmailContact
{
    $list = EmailContactList::create([
        'tenant_id'           => $tenant->id,
        'name'                => 'Lista ' . uniqid(),
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
    ]);
    return EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $tenant->id,
        'email'                 => $email,
        'name'                  => 'Foo',
        'status'                => $status,
        'source'                => 'csv_upload',
        'added_at'              => now(),
    ]);
}

it('gera token deterministico e reversivel', function () {
    $svc = new EmailUnsubscribeTokenService();
    $t1  = $svc->generate('Alice@Foo.COM', 42);
    $t2  = $svc->generate('alice@foo.com', 42);
    expect($t1)->toBe($t2);

    $parsed = $svc->parse($t1);
    expect($parsed)->toBe(['email' => 'alice@foo.com', 'tenant_id' => 42]);
});

it('rejeita token com assinatura invalida', function () {
    $svc   = new EmailUnsubscribeTokenService();
    $token = $svc->generate('bob@x.com', 7);
    // adultera o payload mantendo a assinatura antiga
    [$payload, $sig] = explode('.', $token);
    $tampered = $payload . 'AAAA.' . $sig;

    expect($svc->parse($tampered))->toBeNull();
    expect($svc->parse('lixo'))->toBeNull();
    expect($svc->parse(''))->toBeNull();
});

it('marca contato como unsubscribed em todas as listas do tenant e loga auditoria', function () {
    $tenant = Tenant::factory()->create();
    $c1 = makeContact($tenant, 'maria@ong.org');
    $c2 = makeContact($tenant, 'maria@ong.org'); // segunda lista, mesmo email
    $c3 = makeContact($tenant, 'outro@ong.org'); // nao deve mexer

    $svc   = app(EmailUnsubscribeTokenService::class);
    $token = $svc->generate('maria@ong.org', $tenant->id);

    $r = $this->get(route('public.email.unsubscribe', ['token' => $token]));

    $r->assertOk()
      ->assertSee('Descadastro efetuado')
      ->assertSee('maria@ong.org');

    expect($c1->fresh()->status)->toBe('unsubscribed');
    expect($c1->fresh()->unsubscribed_at)->not->toBeNull();
    expect($c2->fresh()->status)->toBe('unsubscribed');
    expect($c3->fresh()->status)->toBe('active'); // outro email intacto

    $audit = AuditLog::where('event', 'email.unsubscribed')->first();
    expect($audit)->not->toBeNull();
    expect((int) $audit->tenant_id)->toBe($tenant->id);
});

it('zera email_marketing_opt_in em ngo_donors com mesmo email do tenant', function () {
    $tenant = Tenant::factory()->create();
    makeContact($tenant, 'donor@x.com');
    $donor = NgoDonor::factory()->create([
        'tenant_id'              => $tenant->id,
        'email'                  => 'donor@x.com',
        'email_marketing_opt_in' => true,
    ]);

    $token = app(EmailUnsubscribeTokenService::class)->generate('donor@x.com', $tenant->id);
    $this->get(route('public.email.unsubscribe', ['token' => $token]))->assertOk();

    expect((bool) $donor->fresh()->email_marketing_opt_in)->toBeFalse();
});

it('nao vaza entre tenants — token do tenant A nao descadastra no tenant B', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $inA = makeContact($tenantA, 'shared@x.com');
    $inB = makeContact($tenantB, 'shared@x.com');

    $tokenA = app(EmailUnsubscribeTokenService::class)->generate('shared@x.com', $tenantA->id);
    $this->get(route('public.email.unsubscribe', ['token' => $tokenA]))->assertOk();

    expect($inA->fresh()->status)->toBe('unsubscribed');
    expect($inB->fresh()->status)->toBe('active');
});

it('exibe pagina de erro pra token invalido', function () {
    $r = $this->get(route('public.email.unsubscribe', ['token' => 'nao-eh-token-valido']));
    $r->assertStatus(400)->assertSee('Link inválido');
});

it('reativa cadastro via POST', function () {
    $tenant  = Tenant::factory()->create();
    $contact = makeContact($tenant, 'x@y.com', 'unsubscribed');
    $contact->update(['unsubscribed_at' => now()]);

    $token = app(EmailUnsubscribeTokenService::class)->generate('x@y.com', $tenant->id);

    $r = $this->post(route('public.email.unsubscribe.reactivate', ['token' => $token]));
    $r->assertOk()->assertSee('Cadastro reativado');

    expect($contact->fresh()->status)->toBe('active');
    expect($contact->fresh()->unsubscribed_at)->toBeNull();
});
