<?php

use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * P0.1 — Opt-in público explícito por página.
 * Submissão pública de landing page só vira Lead/Consent quando o
 * checkbox de consentimento estiver marcado (LGPD Art. 7º/8º).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function lpoiTenantAndPage(): array
{
    $tenant = Tenant::factory()->create();
    $page = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'Pagina Teste',
        'slug'      => 'pagina-teste-' . uniqid(),
        'status'    => 'published',
    ]);
    return [$tenant, $page];
}

it('rejeita submissao sem consentimento', function () {
    [$tenant, $page] = lpoiTenantAndPage();

    $response = $this->from('/lp/' . $page->slug)
        ->post('/lp/' . $page->slug . '/lead', [
            'name'  => 'Fulano',
            'email' => 'fulano@gmail.com',
        ]);

    $response->assertRedirect('/lp/' . $page->slug);
    $response->assertSessionHasErrors('consent_given');

    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(LeadConsent::where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(DB::table('landing_page_leads')->where('landing_page_id', $page->id)->count())->toBe(0);
});

it('cria Lead + Consent quando consentimento marcado (com email)', function () {
    [$tenant, $page] = lpoiTenantAndPage();

    $response = $this->post('/lp/' . $page->slug . '/lead', [
        'name'          => 'Maria',
        'email'         => 'maria@gmail.com',
        'consent_given' => '1',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $lead = Lead::where('tenant_id', $tenant->id)->first();
    expect($lead)->not->toBeNull();
    expect($lead->email)->toBe('maria@gmail.com');
    expect($lead->name)->toBe('Maria');
    expect($lead->status)->toBe(Lead::STATUS_PENDING);

    $consent = LeadConsent::where('lead_id', $lead->id)->first();
    expect($consent)->not->toBeNull();
    expect($consent->type)->toBe(LeadConsent::TYPE_OPT_IN);
    expect($consent->origin)->toBe('public_form:' . $page->slug);
    expect($consent->ip_address)->not->toBeNull();

    expect(DB::table('landing_page_leads')->where('landing_page_id', $page->id)->count())->toBe(1);
});

it('usa phone como chave de idempotencia quando informado', function () {
    [$tenant, $page] = lpoiTenantAndPage();

    $this->post('/lp/' . $page->slug . '/lead', [
        'name'          => 'João',
        'email'         => 'joao@gmail.com',
        'phone'         => '11987654321',
        'consent_given' => '1',
    ]);

    $this->post('/lp/' . $page->slug . '/lead', [
        'name'          => 'João Silva',
        'email'         => 'joao@gmail.com',
        'phone'         => '11987654321',
        'consent_given' => '1',
    ]);

    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(1);
    expect(LeadConsent::where('tenant_id', $tenant->id)->count())->toBe(2);
});

it('rejeita sem email mesmo com consentimento marcado', function () {
    [$tenant, $page] = lpoiTenantAndPage();

    $response = $this->from('/lp/' . $page->slug)
        ->post('/lp/' . $page->slug . '/lead', [
            'name'          => 'Sem Email',
            'consent_given' => '1',
        ]);

    $response->assertSessionHasErrors('email');
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('rejeita pagina nao publicada para visitante anonimo', function () {
    $tenant = Tenant::factory()->create();
    $page = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'Rascunho',
        'slug'      => 'rascunho-' . uniqid(),
        'status'    => 'draft',
    ]);

    $response = $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'x@gmail.com',
        'consent_given' => '1',
    ]);

    $response->assertNotFound();
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
});
