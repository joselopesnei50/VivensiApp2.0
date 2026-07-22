<?php

namespace Tests\Feature\Conformidade;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $this->tenant       = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->adminUser    = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
        $this->employeeUser = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'employee']);
    }

    public function test_dashboard_renderiza_para_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('ngo.conformidade.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Conformidade Contínua');
        $response->assertSee('Índice Geral');
    }

    public function test_dashboard_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employeeUser);

        $response = $this->get(route('ngo.conformidade.dashboard'));

        $response->assertStatus(403);
    }

    public function test_eixo_cebas_as_renderiza(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('ngo.conformidade.eixo', 'cebas_as'));

        $response->assertStatus(200);
        $response->assertSee('CEBAS Assistência Social');
        $response->assertSee('CEBAS-AS-002');
    }

    public function test_eixo_invalido_retorna_404(): void
    {
        $this->actingAs($this->adminUser);

        $this->get(route('ngo.conformidade.eixo', 'inexistente'))->assertStatus(404);
    }

    public function test_declarar_tipo_c_cria_avaliacao(): void
    {
        $this->actingAs($this->adminUser);

        $req = \App\Models\RequisitoLegal::where('tipo', 'C')->where('eixo', 'suas')->first();

        \App\Models\CicloConformidade::create([
            'tenant_id'  => $this->tenant->id,
            'eixo'       => $req->eixo,
            'data_inicio' => now()->startOfYear(),
            'data_fim'   => now()->addYear(),
            'status'     => 'em_andamento',
        ]);

        $response = $this->post(route('ngo.conformidade.declarar', $req->id), [
            'observacoes' => 'O RMA foi enviado conforme protocolo interno no prazo regulamentar.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('avaliacoes_requisito', [
            'tenant_id'          => $this->tenant->id,
            'requisito_legal_id' => $req->id,
            'resultado'          => 'verde',
        ]);
    }

    public function test_recalcular_despacha_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->actingAs($this->adminUser);

        $this->post(route('ngo.conformidade.recalcular'))->assertRedirect();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\RecalcularConformidadeJob::class);
    }
}
