<?php

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Lista de leads da landing: "Dados extras" mostra rótulo e valor dos campos
 * personalizados, não o JSON cru (2026-11-20).
 */

uses(RefreshDatabase::class);

function ledEnv(): array
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $lp = LandingPage::create(['tenant_id' => $t->id, 'title' => 'Inscrição Oficina', 'slug' => 'led-' . uniqid(), 'status' => 'published', 'settings' => []]);
    LandingPageSection::create([
        'landing_page_id' => $lp->id, 'type' => 'lead_capture', 'sort_order' => 1,
        'content' => ['custom_fields' => [['key' => 'oficina', 'label' => 'Qual oficina?', 'type' => 'select', 'options' => 'Violão, Percussão']]],
    ]);
    DB::table('landing_page_leads')->insert([
        'landing_page_id' => $lp->id, 'name' => 'Ana', 'email' => 'ana@exemplo.org',
        'extra_data' => json_encode(['source' => 'lead_capture', 'custom' => ['oficina' => 'Violão']]),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    return [$u, $lp];
}

it('mostra o rótulo e o valor do campo personalizado, sem JSON', function () {
    [$u, $lp] = ledEnv();
    $this->actingAs($u)->get("/ngo/landing-pages/{$lp->id}/leads")
        ->assertOk()
        ->assertSee('Qual oficina?')
        ->assertSee('Violão')
        ->assertDontSee('"custom"', false)
        ->assertDontSee('lead_capture');
});

it('exporta os dados extras legíveis no CSV', function () {
    [$u, $lp] = ledEnv();
    $csv = $this->actingAs($u)->get("/ngo/landing-pages/{$lp->id}/leads/export")->assertOk()->streamedContent();
    expect($csv)->toContain('Qual oficina?: Violão')->not->toContain('{"');
});
