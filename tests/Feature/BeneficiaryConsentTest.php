<?php

use App\Models\Beneficiary;
use App\Models\BeneficiaryConsent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

/*
| Termo de consentimento LGPD do beneficiário.
*/

beforeEach(function () {
    Carbon::setTestNow('2026-11-09 10:00:00');

    $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo', 'name' => 'Instituto Girassol']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role'      => 'ngo',
        'email'     => 'ngo_' . uniqid() . '@example.com',
        'name'      => 'Técnica Ana',
    ]);
    $this->adult = Beneficiary::create(['tenant_id' => $this->tenant->id, 'name' => 'Maria Souza', 'status' => 'active', 'birth_date' => '1985-04-02']);
    $this->child = Beneficiary::create(['tenant_id' => $this->tenant->id, 'name' => 'Joãozinho', 'status' => 'active', 'birth_date' => '2016-08-10']);
    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

it('registra a assinatura do titular, com histórico e situação no cadastro', function () {
    $this->post("/ngo/beneficiaries/{$this->adult->id}/consent", [
        'signer_role' => 'titular', 'signed_at' => '2026-11-05', 'image_use' => '1',
    ])->assertRedirect()->assertSessionHas('success');

    $this->adult->refresh();
    $c = BeneficiaryConsent::where('beneficiary_id', $this->adult->id)->sole();

    expect($this->adult->consent_at->toDateString())->toBe('2026-11-05')
        ->and($this->adult->image_consent)->toBeTrue()
        ->and($c->action)->toBe('granted')
        ->and($c->signer_name)->toBe('Maria Souza')
        ->and($c->recorded_by)->toBe($this->user->id)
        ->and($c->term_version)->toBe(\App\Services\ConsentService::TERM_VERSION);
});

it('exige o responsável legal para menor de 18 anos', function () {
    $this->post("/ngo/beneficiaries/{$this->child->id}/consent", [
        'signer_role' => 'titular', 'signed_at' => '2026-11-09',
    ])->assertSessionHasErrors('signer_role');

    $this->post("/ngo/beneficiaries/{$this->child->id}/consent", [
        'signer_role' => 'responsavel_legal', 'signed_at' => '2026-11-09',
    ])->assertSessionHasErrors('signer_name');

    $this->post("/ngo/beneficiaries/{$this->child->id}/consent", [
        'signer_role' => 'responsavel_legal', 'signer_name' => 'Carla Mãe', 'signed_at' => '2026-11-09',
    ])->assertSessionHasNoErrors();

    expect($this->child->refresh()->consent_at)->not->toBeNull()
        ->and($this->child->image_consent)->toBeFalse();
});

it('não aceita data de assinatura no futuro', function () {
    $this->post("/ngo/beneficiaries/{$this->adult->id}/consent", [
        'signer_role' => 'titular', 'signed_at' => '2026-12-01',
    ])->assertSessionHasErrors('signed_at');
});

it('registra a revogação e limpa a situação, mantendo o histórico', function () {
    app(\App\Services\ConsentService::class)->grant($this->adult, ['signer_role' => 'titular', 'image_use' => true], $this->user->id);

    $this->post("/ngo/beneficiaries/{$this->adult->id}/consent/revoke", ['notes' => 'Pediu por telefone'])
        ->assertRedirect()->assertSessionHas('success');

    $this->adult->refresh();
    expect($this->adult->consent_at)->toBeNull()
        ->and($this->adult->image_consent)->toBeFalse()
        ->and(BeneficiaryConsent::where('beneficiary_id', $this->adult->id)->pluck('action')->all())->toBe(['granted', 'revoked']);
});

it('não mexe em beneficiário de outra organização', function () {
    $other = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $foreign = Beneficiary::create(['tenant_id' => $other->id, 'name' => 'Outra', 'status' => 'active']);

    $this->post("/ngo/beneficiaries/{$foreign->id}/consent", ['signer_role' => 'titular', 'signed_at' => '2026-11-09'])->assertNotFound();
    $this->get("/ngo/beneficiaries/{$foreign->id}/consent/term")->assertNotFound();
    expect(BeneficiaryConsent::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('gera o PDF do termo, com o campo do responsável para menor', function () {
    $r = $this->get("/ngo/beneficiaries/{$this->child->id}/consent/term");
    $r->assertOk();
    expect($r->headers->get('content-type'))->toContain('application/pdf');

    $html = view('ngo.beneficiaries.consent_term_pdf', [
        'beneficiary' => $this->child, 'minor' => true, 'orgName' => 'Instituto Girassol', 'orgDocument' => null,
        'orgCity' => 'Araraquara', 'contactEmail' => 'lgpd@girassol.org', 'contactPhone' => null, 'version' => '2026-11',
    ])->render();

    expect($html)->toContain('Responsável legal')
        ->and($html)->toContain('Joãozinho')
        ->and($html)->toContain('lgpd@girassol.org')
        ->and($html)->toContain('Uso de imagem e voz');
});

it('mostra o cartão na ficha: pendente e depois assinado', function () {
    $this->get("/ngo/beneficiaries/{$this->child->id}")
        ->assertOk()
        ->assertSee('Termo LGPD')
        ->assertSee('pendente')
        ->assertSee('Menor de 18 anos');

    app(\App\Services\ConsentService::class)->grant($this->child, ['signer_role' => 'responsavel_legal', 'signer_name' => 'Carla Mãe'], $this->user->id);

    $this->get("/ngo/beneficiaries/{$this->child->id}")
        ->assertOk()
        ->assertSee('Assinado em')
        ->assertSee('Carla Mãe')
        ->assertSee('não autorizado');
});

it('registra o termo já no cadastro do beneficiário', function () {
    $this->post('/ngo/beneficiaries', [
        'name' => 'Pedro Lima', 'status' => 'active', 'birth_date' => '1990-01-01',
        'consent_signed' => '1', 'signer_role' => 'titular', 'image_use' => '1',
    ])->assertRedirect('/ngo/beneficiaries');

    $b = Beneficiary::where('name', 'Pedro Lima')->sole();
    expect($b->consent_at->toDateString())->toBe('2026-11-09')
        ->and($b->image_consent)->toBeTrue();
});

it('no cadastro, menor sem responsável não é salvo', function () {
    $this->post('/ngo/beneficiaries', [
        'name' => 'Bia Criança', 'status' => 'active', 'birth_date' => '2015-01-01',
        'consent_signed' => '1', 'signer_role' => 'titular',
    ])->assertSessionHasErrors('signer_role');

    expect(Beneficiary::where('name', 'Bia Criança')->exists())->toBeFalse();
});

it('filtra a lista por termo pendente ou assinado', function () {
    app(\App\Services\ConsentService::class)->grant($this->adult, ['signer_role' => 'titular'], $this->user->id);

    $this->get('/ngo/beneficiaries?consent=assinado')->assertOk()->assertSee('Maria Souza')->assertDontSee('Joãozinho');
    $this->get('/ngo/beneficiaries?consent=pendente')->assertOk()->assertSee('Joãozinho')->assertDontSee('Maria Souza');
    $this->get('/ngo/beneficiaries?consent=sem_imagem')->assertOk()->assertSee('Maria Souza');
});
