<?php

use App\Mail\LandingPageLeadConfirmationMail;
use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;

/**
 * Confirmacao pos-submissao de landing page (2026-08-06).
 * Cobre: (1) session flash 'lp_lead_success', (2) email transacional
 * disparado pro lead, (3) toast HTML apos redirect, (4) label acima do
 * input[type=date] (nao usar placeholder que browser ignora).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function lpcSetup(): array
{
    $tenant = Tenant::factory()->create(['name' => 'ONG Acolher']);
    $page = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'Inscricao Curso Verao',
        'slug'      => 'lp-conf-' . uniqid(),
        'status'    => 'published',
    ]);
    LandingPageSection::create([
        'landing_page_id' => $page->id,
        'type'            => 'lead_capture',
        'content'         => ['enable_birth_date' => true],
        'sort_order'      => 1,
    ]);
    return [$tenant, $page];
}

it('dispara email de confirmacao para o lead apos submissao', function () {
    Mail::fake();
    [$tenant, $page] = lpcSetup();

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'novo@lead.com',
        'name'          => 'Maria',
        'consent_given' => '1',
    ])->assertRedirect();

    Mail::assertSent(LandingPageLeadConfirmationMail::class, function ($m) use ($page) {
        return $m->hasTo('novo@lead.com')
            && $m->leadName === 'Maria'
            && $m->page->id === $page->id;
    });
});

it('grava flash lp_lead_success com nome e email', function () {
    Mail::fake();
    [$tenant, $page] = lpcSetup();

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'flash@lead.com',
        'name'          => 'Carlos',
        'consent_given' => '1',
    ])->assertSessionHas('lp_lead_success', function ($value) {
        return is_array($value)
            && ($value['email'] ?? null) === 'flash@lead.com'
            && ($value['name']  ?? null) === 'Carlos';
    });
});

it('toast de sucesso aparece no HTML apos redirect back', function () {
    Mail::fake();
    [$tenant, $page] = lpcSetup();

    $this->from('/lp/' . $page->slug)->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'toast@lead.com',
        'consent_given' => '1',
    ])->assertRedirect('/lp/' . $page->slug);

    $this->followingRedirects()->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'toast2@lead.com',
        'consent_given' => '1',
    ])->assertSee('Inscrição enviada com sucesso');
});

it('input date tem label visivel (nao depende de placeholder)', function () {
    [$tenant, $page] = lpcSetup();

    $resp = $this->get('/lp/' . $page->slug)->assertStatus(200);
    // Label textual acima do input
    $resp->assertSee('Data de nascimento');
    // Sem placeholder no input date (browser ignoraria)
    $resp->assertDontSee('placeholder="Data de nascimento"', false);
});

it('falha silenciosa: se mail explode, lead ainda e persistido', function () {
    Mail::fake();
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

    [$tenant, $page] = lpcSetup();

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'resiliente@lead.com',
        'consent_given' => '1',
    ])->assertRedirect();

    expect(\Illuminate\Support\Facades\DB::table('landing_page_leads')
        ->where('landing_page_id', $page->id)
        ->where('email', 'resiliente@lead.com')
        ->count())->toBe(1);
});
