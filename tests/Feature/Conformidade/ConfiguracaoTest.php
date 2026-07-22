<?php

namespace Tests\Feature\Conformidade;

use App\Models\CicloConformidade;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CnpjApiService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConfiguracaoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $this->tenant   = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->admin    = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
        $this->employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'employee']);
    }

    // ── GET /ngo/conformidade/configurar ────────────────────────────────────────

    public function test_configurar_renderiza_para_admin(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.configurar'))
             ->assertStatus(200)
             ->assertSee('Perfil de Conformidade');
    }

    public function test_configurar_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.configurar'))
             ->assertStatus(403);
    }

    // ── POST /ngo/conformidade/configurar ───────────────────────────────────────

    public function test_salvar_configurar_atualiza_campos(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('ngo.conformidade.configurar.save'), [
            'cnas_numero'          => 'CNAS-001/2023',
            'cnas_validade'        => '2026-12-31',
            'cmas_numero'          => 'CMAS-77/2022',
            'cmas_validade'        => '2025-12-31',
            'cneas_codigo'         => '23.001-8',
            'area_atuacao_cebas'   => 'assistencia_social',
            'data_fundacao'        => '2010-05-15',
            'cnae_principal'       => '8720401',
            'receita_bruta_anual_ref' => '500000.00',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertEquals('CNAS-001/2023', $this->tenant->cnas_numero);
        $this->assertEquals('assistencia_social', $this->tenant->area_atuacao_cebas);
        $this->assertEquals('8720401', $this->tenant->cnae_principal);
    }

    public function test_salvar_configurar_valida_area_invalida(): void
    {
        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.configurar.save'), [
                 'area_atuacao_cebas' => 'cultura',
             ])
             ->assertSessionHasErrors('area_atuacao_cebas');
    }

    public function test_salvar_configurar_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->post(route('ngo.conformidade.configurar.save'), [
                 'cnas_numero' => 'X',
             ])
             ->assertStatus(403);
    }

    // ── GET /ngo/conformidade/cnpj-lookup ───────────────────────────────────────

    public function test_cnpj_lookup_retorna_dados(): void
    {
        $this->actingAs($this->admin);

        $this->mock(CnpjApiService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                 ->once()
                 ->with('11222333000181')
                 ->andReturn([
                     'razao_social'       => 'ASSOCIACAO TESTE',
                     'cnae_principal'     => '8720401',
                     'data_fundacao'      => '2010-05-15',
                     'situacao_cadastral' => 'ATIVA',
                 ]);
        });

        $this->get(route('ngo.conformidade.cnpj.lookup', ['cnpj' => '11222333000181']))
             ->assertStatus(200)
             ->assertJsonFragment(['razao_social' => 'ASSOCIACAO TESTE']);
    }

    public function test_cnpj_lookup_rejeita_cnpj_invalido(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.cnpj.lookup', ['cnpj' => '123']))
             ->assertStatus(422)
             ->assertJsonFragment(['error' => 'CNPJ inválido.']);
    }

    public function test_cnpj_lookup_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.cnpj.lookup', ['cnpj' => '11222333000181']))
             ->assertStatus(403);
    }

    // ── GET /ngo/conformidade/ciclos ────────────────────────────────────────────

    public function test_ciclos_renderiza_para_admin(): void
    {
        CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'suas',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->endOfYear(),
            'status'      => 'em_andamento',
        ]);

        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.ciclos'))
             ->assertStatus(200)
             ->assertSee('Ciclos de Conformidade')
             ->assertSee('SUAS');
    }

    public function test_ciclos_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.ciclos'))
             ->assertStatus(403);
    }
}
