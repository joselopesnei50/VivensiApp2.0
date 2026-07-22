<?php

namespace Tests\Feature\Conformidade;

use App\Models\PlanoAcaoConformidade;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanoAcaoTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        $req = RequisitoLegal::first();
        return array_merge([
            'requisito_legal_id' => $req->id,
            'titulo'             => 'Regularizar CNAS 2026',
            'descricao'          => 'Renovar registro junto ao CNAS antes do vencimento em dezembro.',
            'responsavel'        => 'Maria Silva',
            'prazo'              => now()->addMonth()->toDateString(),
        ], $overrides);
    }

    private function criarPlano(array $overrides = []): PlanoAcaoConformidade
    {
        $req = RequisitoLegal::first();
        return PlanoAcaoConformidade::create(array_merge([
            'tenant_id'          => $this->tenant->id,
            'requisito_legal_id' => $req->id,
            'titulo'             => 'Plano Teste',
            'descricao'          => 'Ações necessárias para regularização.',
            'responsavel'        => 'João',
            'prazo'              => now()->addDays(30)->toDateString(),
            'status'             => 'pendente',
        ], $overrides));
    }

    // ── GET /ngo/conformidade/planos-acao ───────────────────────────────────────

    public function test_index_renderiza_para_admin(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.planos.index'))
             ->assertStatus(200)
             ->assertSee('Planos de Ação');
    }

    public function test_index_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.planos.index'))
             ->assertStatus(403);
    }

    // ── POST /ngo/conformidade/planos-acao ──────────────────────────────────────

    public function test_store_cria_plano(): void
    {
        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.planos.store'), $this->payload())
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('planos_acao_conformidade', [
            'tenant_id' => $this->tenant->id,
            'titulo'    => 'Regularizar CNAS 2026',
            'status'    => 'pendente',
        ]);
    }

    public function test_store_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->post(route('ngo.conformidade.planos.store'), $this->payload())
             ->assertStatus(403);
    }

    public function test_store_valida_prazo_passado(): void
    {
        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.planos.store'), $this->payload([
                 'prazo' => now()->subDay()->toDateString(),
             ]))
             ->assertSessionHasErrors('prazo');
    }

    public function test_store_valida_descricao_curta(): void
    {
        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.planos.store'), $this->payload([
                 'descricao' => 'curto',
             ]))
             ->assertSessionHasErrors('descricao');
    }

    // ── PUT /ngo/conformidade/planos-acao/{id} ──────────────────────────────────

    public function test_update_muda_status(): void
    {
        $plano = $this->criarPlano();

        $this->actingAs($this->admin)
             ->put(route('ngo.conformidade.planos.update', $plano->id), [
                 'status'      => 'em_andamento',
                 'responsavel' => 'João',
                 'prazo'       => $plano->prazo->toDateString(),
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertEquals('em_andamento', $plano->fresh()->status);
    }

    public function test_update_concluido_registra_data_resolucao(): void
    {
        $plano = $this->criarPlano();

        $this->actingAs($this->admin)
             ->put(route('ngo.conformidade.planos.update', $plano->id), [
                 'status'                => 'concluido',
                 'observacoes_resolucao' => 'CNAS renovado com sucesso.',
             ])
             ->assertRedirect();

        $fresh = $plano->fresh();
        $this->assertEquals('concluido', $fresh->status);
        $this->assertNotNull($fresh->resolvido_em);
        $this->assertEquals($this->admin->id, $fresh->resolvido_por);
    }

    public function test_update_outro_tenant_retorna_404(): void
    {
        $outraTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $req         = RequisitoLegal::first();
        $planoAlheio = PlanoAcaoConformidade::create([
            'tenant_id'          => $outraTenant->id,
            'requisito_legal_id' => $req->id,
            'titulo'             => 'Alheio',
            'descricao'          => 'Não deveria ser editável.',
            'responsavel'        => 'X',
            'prazo'              => now()->addMonth()->toDateString(),
            'status'             => 'pendente',
        ]);

        $this->actingAs($this->admin)
             ->put(route('ngo.conformidade.planos.update', $planoAlheio->id), [
                 'status' => 'concluido',
             ])
             ->assertStatus(404);
    }

    // ── DELETE /ngo/conformidade/planos-acao/{id} ───────────────────────────────

    public function test_destroy_remove_plano(): void
    {
        $plano = $this->criarPlano();

        $this->actingAs($this->admin)
             ->delete(route('ngo.conformidade.planos.destroy', $plano->id))
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('planos_acao_conformidade', ['id' => $plano->id]);
    }

    public function test_destroy_outro_tenant_retorna_404(): void
    {
        $outraTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $req         = RequisitoLegal::first();
        $planoAlheio = PlanoAcaoConformidade::create([
            'tenant_id'          => $outraTenant->id,
            'requisito_legal_id' => $req->id,
            'titulo'             => 'Alheio',
            'descricao'          => 'Não deve ser deletável.',
            'responsavel'        => 'X',
            'prazo'              => now()->addMonth()->toDateString(),
            'status'             => 'pendente',
        ]);

        $this->actingAs($this->admin)
             ->delete(route('ngo.conformidade.planos.destroy', $planoAlheio->id))
             ->assertStatus(404);
    }
}
