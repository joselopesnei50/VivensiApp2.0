<?php

use App\Models\Beneficiary;
use App\Models\Project;
use App\Models\ProjectPerson;
use App\Models\Tenant;
use App\Models\User;

/**
 * Análise dos módulos da ONG (06/11/2026) — pacote 5: usabilidade.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p5Org(array $tenant = []): User
{
    $t = Tenant::factory()->create(array_merge(['subscription_status' => 'active', 'type' => 'ngo'], $tenant));
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

it('topo de Beneficiários tem uma ação principal e o resto em "Mais"', function () {
    $u = p5Org();
    $html = $this->actingAs($u)->get('/ngo/beneficiaries')->assertOk()->getContent();

    expect($html)->toContain('Novo beneficiário')
        ->toContain('class="ben-more"')
        ->toContain('Exportar lista (CSV)')
        ->toContain('Mais filtros');
    // Filtros avançados abrem sozinhos quando há algum ativo
    $this->actingAs($u)->get('/ngo/beneficiaries?gender=feminino')->assertSee('(1 ativo)');
});

it('pessoas do projeto são paginadas e têm busca', function () {
    $u = p5Org();
    $p = Project::create(['tenant_id' => $u->tenant_id, 'name' => 'P', 'budget' => 1000, 'start_date' => '2026-01-01', 'status' => 'active']);
    foreach (range(1, 30) as $i) {
        ProjectPerson::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => sprintf('Pessoa %02d', $i), 'enrollment_status' => 'ativo']);
    }

    $this->actingAs($u)->get('/projects/' . $p->id)
        ->assertOk()
        ->assertSee('Pessoa 01')->assertSee('Pessoa 25')->assertDontSee('Pessoa 26')
        ->assertSee('(30)');

    $this->actingAs($u)->get('/projects/' . $p->id . '?pessoas=2')->assertSee('Pessoa 26')->assertDontSee('Pessoa 01');
    $this->actingAs($u)->get('/projects/' . $p->id . '?pessoa=Pessoa 07')->assertSee('Pessoa 07')->assertDontSee('Pessoa 08');
});

it('projeto mostra "Saldo do orçamento" em vez de "Saldo em Caixa"', function () {
    $u = p5Org();
    $p = Project::create(['tenant_id' => $u->tenant_id, 'name' => 'P', 'budget' => 1000, 'start_date' => '2026-01-01', 'status' => 'active']);
    $this->actingAs($u)->get('/projects/' . $p->id)->assertSee('Saldo do orçamento')->assertDontSee('Saldo em Caixa');
});

it('pendências da conformidade mostram as 8 primeiras e ação direta no requisito', function () {
    $this->seed(\Database\Seeders\RequisitoLegalSeeder::class);
    $this->seed(\Database\Seeders\RegraAvaliacaoSeeder::class);
    $u = p5Org(['area_atuacao_cebas' => 'assistencia_social']);

    $html = $this->actingAs($u)->get('/ngo/conformidade')->assertOk()->getContent();
    expect($html)->toContain('Ver todas as')
        ->toContain('Enviar documento')
        ->toContain('/ngo/conformidade/requisito/');
    expect(substr_count($html, 'conf-pend-extra d-none'))->toBeGreaterThan(0);
});

it('CSS do tema escuro clareia o submenu da barra lateral', function () {
    expect(file_get_contents(public_path('css/design-system.css')))
        ->toContain('[data-theme="dark"] .sidebar:not(.sidebar-sa) .sidebar-menu a:not(.active):not(:hover)');
});
