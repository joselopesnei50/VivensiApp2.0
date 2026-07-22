<?php

namespace Tests\Feature\Conformidade;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\CicloConformidade;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RelatorioPdfService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioPdfTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->admin  = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);

        CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'mrosc',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->addYear(),
            'status'      => 'em_andamento',
        ]);
        CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'cebas_as',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->addYears(3),
            'status'      => 'em_andamento',
        ]);
    }

    // ── RelatorioPdfService ───────────────────────────────────────────────────

    public function test_dados_rma_retorna_estrutura_correta(): void
    {
        $b = Beneficiary::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Teste',
            'status'    => 'active',
        ]);

        for ($i = 0; $i < 5; $i++) {
            Attendance::create([
                'tenant_id'        => $this->tenant->id,
                'beneficiary_id'   => $b->id,
                'user_id'          => $this->admin->id,
                'date'             => now()->toDateString(),
                'type'             => 'individual',
                'description'      => 'teste',
                'gratuito'         => true,
                'tipificacao_suas' => 'paif',
            ]);
        }

        $service = app(RelatorioPdfService::class);
        $dados   = $service->dadosRma($this->tenant->id, now()->month, now()->year);

        $this->assertArrayHasKey('tenant', $dados);
        $this->assertArrayHasKey('total', $dados);
        $this->assertArrayHasKey('por_tipificacao', $dados);
        $this->assertEquals(5, $dados['total']);
        $this->assertEquals(100.0, $dados['pct_gratuito']);
        $this->assertArrayHasKey('paif', $dados['por_tipificacao']);
    }

    public function test_dados_rma_mes_sem_atendimentos_retorna_zero(): void
    {
        $service = app(RelatorioPdfService::class);
        $dados   = $service->dadosRma($this->tenant->id, 1, 2020);

        $this->assertEquals(0, $dados['total']);
        $this->assertEquals(0, $dados['beneficiarios']);
        $this->assertTrue($dados['por_tipificacao']->isEmpty());
    }

    public function test_dados_cebas_retorna_estrutura_correta(): void
    {
        $service = app(RelatorioPdfService::class);
        $dados   = $service->dadosCebas($this->tenant->id);

        $this->assertArrayHasKey('requisitos', $dados);
        $this->assertArrayHasKey('indice', $dados);
        $this->assertArrayHasKey('verde', $dados);
        $this->assertArrayHasKey('documentos', $dados);
        $this->assertIsFloat((float) $dados['indice']);
    }

    public function test_dados_mrosc_retorna_estrutura_correta(): void
    {
        $service = app(RelatorioPdfService::class);
        $dados   = $service->dadosMrosc($this->tenant->id);

        $this->assertArrayHasKey('requisitos', $dados);
        $this->assertArrayHasKey('grants', $dados);
        $this->assertArrayHasKey('etapas', $dados);
        $this->assertArrayHasKey('total_mrosc', $dados);
    }

    // ── Rotas HTTP ────────────────────────────────────────────────────────────

    public function test_rota_pdf_rma_retorna_pdf(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('ngo.conformidade.pdf.rma', [
            'mes' => now()->month,
            'ano' => now()->year,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_rota_pdf_rma_valida_mes_invalido(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('ngo.conformidade.pdf.rma', ['mes' => 13, 'ano' => now()->year]))
             ->assertSessionHasErrors('mes');
    }

    public function test_rota_pdf_cebas_retorna_pdf(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('ngo.conformidade.pdf.cebas'))
             ->assertStatus(200)
             ->assertHeader('content-type', 'application/pdf');
    }

    public function test_rota_pdf_mrosc_retorna_pdf(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('ngo.conformidade.pdf.mrosc'))
             ->assertStatus(200)
             ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_bloqueado_para_employee(): void
    {
        $employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'employee']);
        $this->actingAs($employee);

        $this->get(route('ngo.conformidade.pdf.cebas'))->assertStatus(403);
    }
}
