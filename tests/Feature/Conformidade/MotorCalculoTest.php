<?php

namespace Tests\Feature\Conformidade;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\CicloConformidade;
use App\Models\Employee;
use App\Models\ProjectStage;
use App\Models\RegraAvaliacao;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ComplianceCalculationService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotorCalculoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private ComplianceCalculationService $service;
    private CicloConformidade $cicloAs;
    private CicloConformidade $cicloSuas;
    private CicloConformidade $cicloMrosc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
        $this->service = app(ComplianceCalculationService::class);

        $this->cicloAs   = CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'cebas_as',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->startOfYear()->addYears(3),
            'status'      => 'em_andamento',
        ]);
        $this->cicloSuas = CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'suas',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->addYear(),
            'status'      => 'em_andamento',
        ]);
        $this->cicloMrosc = CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'mrosc',
            'data_inicio' => now()->subMonths(6),
            'data_fim'    => now()->addMonths(6),
            'status'      => 'em_andamento',
        ]);
    }

    private function makeBeneficiary(): Beneficiary
    {
        return Beneficiary::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Beneficiário Teste',
            'status'    => 'active',
        ]);
    }

    // ── CEBAS-AS-002: % gratuidade ────────────────────────────────────────────

    public function test_gratuidade_acima_do_threshold_retorna_verde(): void
    {
        $b = $this->makeBeneficiary();
        // 5 gratuitos de 5 total = 100%
        for ($i = 0; $i < 5; $i++) {
            Attendance::create([
                'tenant_id'      => $this->tenant->id,
                'beneficiary_id' => $b->id,
                'user_id'        => $this->user->id,
                'date'           => now()->toDateString(),
                'type'           => 'individual',
                'description'    => 'teste',
                'gratuito'       => true,
            ]);
        }

        $req   = RequisitoLegal::where('codigo', 'CEBAS-AS-002')->first();
        $regra = $req->regra;
        $valor = $this->service->calcularTipoA($this->tenant->id, $req->codigo, $regra, $this->cicloAs);

        $this->assertEquals(100.0, $valor);

        $aval = $this->service->avaliarRequisito($this->tenant->id, $req, $this->cicloAs);
        $this->assertEquals('verde', $aval['resultado']);
    }

    public function test_gratuidade_abaixo_do_threshold_retorna_vermelho(): void
    {
        $b = $this->makeBeneficiary();
        // 1 gratuito de 10 = 10% (abaixo de 20%)
        Attendance::create([
            'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
            'user_id' => $this->user->id, 'date' => now()->toDateString(),
            'type' => 'individual', 'description' => 'g', 'gratuito' => true,
        ]);
        for ($i = 0; $i < 9; $i++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
                'user_id' => $this->user->id, 'date' => now()->toDateString(),
                'type' => 'individual', 'description' => 'p', 'gratuito' => false,
            ]);
        }

        $req  = RequisitoLegal::where('codigo', 'CEBAS-AS-002')->first();
        $aval = $this->service->avaliarRequisito($this->tenant->id, $req, $this->cicloAs);

        $this->assertEquals(10.0, (float) $aval['valor_calculado']);
        $this->assertEquals('vermelho', $aval['resultado']);
    }

    public function test_gratuidade_na_zona_amarela(): void
    {
        $b = $this->makeBeneficiary();
        // 17 de 100 = 17% (entre 16% e 20% = amarelo: >= threshold*0.8)
        for ($i = 0; $i < 17; $i++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
                'user_id' => $this->user->id, 'date' => now()->toDateString(),
                'type' => 'individual', 'description' => 'g', 'gratuito' => true,
            ]);
        }
        for ($i = 0; $i < 83; $i++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
                'user_id' => $this->user->id, 'date' => now()->toDateString(),
                'type' => 'individual', 'description' => 'p', 'gratuito' => false,
            ]);
        }

        $req  = RequisitoLegal::where('codigo', 'CEBAS-AS-002')->first();
        $aval = $this->service->avaliarRequisito($this->tenant->id, $req, $this->cicloAs);

        $this->assertEquals('amarelo', $aval['resultado']);
    }

    // ── SUAS-OP-002: atendimentos tipificados ─────────────────────────────────

    public function test_tipificacao_suas_parcial_retorna_amarelo(): void
    {
        $b = $this->makeBeneficiary();
        for ($i = 0; $i < 85; $i++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
                'user_id' => $this->user->id, 'date' => now()->toDateString(),
                'type' => 'group', 'description' => 't',
                'tipificacao_suas' => 'paif',
            ]);
        }
        for ($i = 0; $i < 15; $i++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
                'user_id' => $this->user->id, 'date' => now()->toDateString(),
                'type' => 'group', 'description' => 's',
                'tipificacao_suas' => null,
            ]);
        }

        $req  = RequisitoLegal::where('codigo', 'SUAS-OP-002')->first();
        $aval = $this->service->avaliarRequisito($this->tenant->id, $req, $this->cicloSuas);

        $this->assertEquals(85.0, (float) $aval['valor_calculado']);
        $this->assertEquals('verde', $aval['resultado']); // 85% >= 80% threshold
    }

    // ── MROSC-P-007: cumprimento de metas ────────────────────────────────────

    public function test_metas_cumpridas_retorna_verde(): void
    {
        \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $project = \App\Models\Project::where('tenant_id', $this->tenant->id)->first();

        ProjectStage::create([
            'tenant_id'      => $this->tenant->id,
            'project_id'     => $project->id,
            'title'          => 'Meta 1',
            'planned_value'  => 10000,
            'executed_value' => 9800, // desvio 2% < 25%
            'status'         => 'completed',
            'order'          => 1,
        ]);

        $req  = RequisitoLegal::where('codigo', 'MROSC-P-007')->first();
        $aval = $this->service->avaliarRequisito($this->tenant->id, $req, $this->cicloMrosc);

        $this->assertEquals(100.0, (float) $aval['valor_calculado']);
        $this->assertEquals('verde', $aval['resultado']);
    }

    // ── Dashboard sem dados ───────────────────────────────────────────────────

    public function test_dashboard_renderiza_sem_erro_para_tenant_sem_dados(): void
    {
        $dashboard = $this->service->calcularDashboard($this->tenant->id);

        $this->assertArrayHasKey('indice_geral', $dashboard);
        $this->assertArrayHasKey('pendencias', $dashboard);
        $this->assertArrayHasKey('ciclos', $dashboard);
        $this->assertIsFloat((float) $dashboard['indice_geral']);
    }

    // ── RecalcularConformidadeJob ─────────────────────────────────────────────

    public function test_observer_attendance_dispara_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->actingAs($this->user);

        $b = $this->makeBeneficiary();
        Attendance::create([
            'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id,
            'user_id' => $this->user->id, 'date' => now()->toDateString(),
            'type' => 'individual', 'description' => 'x', 'gratuito' => true,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\RecalcularConformidadeJob::class);
    }
}
