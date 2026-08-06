<?php

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Custom fields (2026-08-06) — landing pages permitem definir campos
 * personalizados por section (lead_capture / final_cta_form). Persistidos em
 * landing_page_leads.extra_data.custom.<key> apos validacao dinamica.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function lpcfSetup(array $customFields, string $type = 'lead_capture'): array
{
    $tenant = Tenant::factory()->create();
    $page = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'LP CustomFields',
        'slug'      => 'lp-cf-' . uniqid(),
        'status'    => 'published',
    ]);
    LandingPageSection::create([
        'landing_page_id' => $page->id,
        'type'            => $type,
        'content'         => ['custom_fields' => $customFields],
        'sort_order'      => 1,
    ]);
    return [$tenant, $page];
}

it('submissao SEM custom_fields configurados continua funcionando (backward compat)', function () {
    [$tenant, $page] = lpcfSetup([]);

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'x@teste.com',
        'consent_given' => '1',
    ])->assertRedirect();

    expect(DB::table('landing_page_leads')->where('landing_page_id', $page->id)->count())->toBe(1);
});

it('custom field opcional preenchido vai pra extra_data.custom', function () {
    [$tenant, $page] = lpcfSetup([
        ['key' => 'instituicao', 'label' => 'Instituição', 'type' => 'text', 'required' => false],
    ]);

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'a@teste.com',
        'consent_given' => '1',
        'custom'        => ['instituicao' => 'ONG Acolher'],
    ])->assertRedirect();

    $row = DB::table('landing_page_leads')->where('landing_page_id', $page->id)->first();
    $extra = json_decode($row->extra_data, true);
    expect($extra['custom']['instituicao'] ?? null)->toBe('ONG Acolher');
});

it('custom field required vazio dispara erro de validacao', function () {
    [$tenant, $page] = lpcfSetup([
        ['key' => 'cargo', 'label' => 'Cargo', 'type' => 'text', 'required' => true],
    ]);

    $this->from('/lp/' . $page->slug)
        ->post('/lp/' . $page->slug . '/lead', [
            'email'         => 'b@teste.com',
            'consent_given' => '1',
            'custom'        => ['cargo' => ''],
        ])
        ->assertSessionHasErrors('custom.cargo');

    expect(DB::table('landing_page_leads')->where('landing_page_id', $page->id)->count())->toBe(0);
});

it('custom field select rejeita valor fora das options', function () {
    [$tenant, $page] = lpcfSetup([
        ['key' => 'porte', 'label' => 'Porte', 'type' => 'select', 'required' => true, 'options' => 'Pequeno, Medio, Grande'],
    ]);

    $this->from('/lp/' . $page->slug)
        ->post('/lp/' . $page->slug . '/lead', [
            'email'         => 'c@teste.com',
            'consent_given' => '1',
            'custom'        => ['porte' => 'Gigante'],
        ])
        ->assertSessionHasErrors('custom.porte');
});

it('custom field key nao definida no schema NAO polui extra_data.custom', function () {
    [$tenant, $page] = lpcfSetup([
        ['key' => 'instituicao', 'label' => 'Instituição', 'type' => 'text'],
    ]);

    $this->post('/lp/' . $page->slug . '/lead', [
        'email'         => 'd@teste.com',
        'consent_given' => '1',
        'custom'        => [
            'instituicao' => 'ONG X',
            'lixo_forjado' => 'tentativa_de_injecao',
        ],
    ])->assertRedirect();

    $row = DB::table('landing_page_leads')->where('landing_page_id', $page->id)->first();
    $extra = json_decode($row->extra_data, true);
    expect($extra['custom']['instituicao'] ?? null)->toBe('ONG X');
    expect(array_key_exists('lixo_forjado', $extra['custom'] ?? []))->toBeFalse();
});

it('renderiza input custom no HTML do form public', function () {
    [$tenant, $page] = lpcfSetup([
        ['key' => 'cnpj', 'label' => 'CNPJ da instituição', 'type' => 'text'],
    ]);

    $resp = $this->get('/lp/' . $page->slug)->assertStatus(200);
    $resp->assertSee('name="custom[cnpj]"', false);
    $resp->assertSee('CNPJ da instituição');
});
