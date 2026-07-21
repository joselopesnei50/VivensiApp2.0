<?php

namespace Tests\Feature\Conformidade;

use App\Models\Attendance;
use App\Models\Attachment;
use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\NgoGrant;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\RegraAvaliacao;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CnpjApiService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FundacaoDadosTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'subscription_status' => 'active',
            'type'                => 'ngo',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
        ]);
        $this->actingAs($this->user);
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    private function makeBeneficiary(): Beneficiary
    {
        return Beneficiary::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Beneficiário Teste',
            'status'    => 'active',
        ]);
    }

    public function test_attendance_gratuito_persiste(): void
    {
        $beneficiary = $this->makeBeneficiary();

        $attendance = Attendance::create([
            'tenant_id'        => $this->tenant->id,
            'beneficiary_id'   => $beneficiary->id,
            'user_id'          => $this->user->id,
            'date'             => now()->toDateString(),
            'type'             => 'individual',
            'description'      => 'Atendimento teste',
            'gratuito'         => true,
            'tipificacao_suas' => 'paif',
        ]);

        $fresh = $attendance->fresh();
        $this->assertTrue($fresh->gratuito);
        $this->assertEquals('paif', $fresh->tipificacao_suas);
    }

    public function test_attendance_gratuito_false_por_padrao(): void
    {
        $beneficiary = $this->makeBeneficiary();

        $attendance = Attendance::create([
            'tenant_id'      => $this->tenant->id,
            'beneficiary_id' => $beneficiary->id,
            'user_id'        => $this->user->id,
            'date'           => now()->toDateString(),
            'type'           => 'individual',
            'description'    => 'Atendimento pago',
        ]);

        $this->assertFalse($attendance->fresh()->gratuito);
    }

    // ── Attachment ────────────────────────────────────────────────────────────

    public function test_attachment_tipo_documento_e_validade_persistem(): void
    {
        $attachment = Attachment::create([
            'tenant_id'      => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'  => $this->tenant->id,
            'original_name'  => 'cnd_inss.pdf',
            'path'           => 'tenants/1/attachments/Tenant/cnd_inss.pdf',
            'mime_type'      => 'application/pdf',
            'size_bytes'     => 12345,
            'uploaded_by'    => $this->user->id,
            'tipo_documento' => 'cnd_inss',
            'valid_until'    => now()->addDays(60)->toDateString(),
            'versao'         => 1,
        ]);

        $fresh = $attachment->fresh();
        $this->assertEquals('cnd_inss', $fresh->tipo_documento);
        $this->assertTrue($fresh->valid_until->isFuture());
        $this->assertEquals(1, $fresh->versao);
    }

    public function test_attachment_substituido_por_vincula_versao_nova(): void
    {
        $v1 = Attachment::create([
            'tenant_id'      => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'  => $this->tenant->id,
            'original_name'  => 'cnd_v1.pdf',
            'path'           => 'tenants/1/attachments/Tenant/v1.pdf',
            'mime_type'      => 'application/pdf',
            'size_bytes'     => 10000,
            'uploaded_by'    => $this->user->id,
            'tipo_documento' => 'cnd_inss',
            'valid_until'    => now()->subDays(5)->toDateString(),
            'versao'         => 1,
        ]);

        $v2 = Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'cnd_v2.pdf',
            'path'            => 'tenants/1/attachments/Tenant/v2.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 11000,
            'uploaded_by'     => $this->user->id,
            'tipo_documento'  => 'cnd_inss',
            'valid_until'     => now()->addDays(90)->toDateString(),
            'versao'          => 2,
            'substituido_por_id' => null, // v2 é a nova versão; v1 aponta pra v2
        ]);

        $v1->update(['substituido_por_id' => $v2->id]);

        $this->assertEquals($v2->id, $v1->fresh()->substituido_por_id);
        $this->assertNotNull($v1->substituidoPor);
    }

    // ── Tenant ────────────────────────────────────────────────────────────────

    public function test_tenant_campos_conformidade_persistem(): void
    {
        $this->tenant->update([
            'cnae_principal'     => '8730301',
            'data_fundacao'      => '2010-03-15',
            'area_atuacao_cebas' => 'as',
            'cnas_numero'        => 'CNAS-12345',
            'cnas_validade'      => now()->addYears(3)->toDateString(),
            'cmas_numero'        => 'CMAS-67890',
            'cmas_validade'      => now()->addYear()->toDateString(),
            'cneas_codigo'       => 'CNEAS-001',
            'receita_bruta_anual_ref' => 500000.00,
        ]);

        $fresh = $this->tenant->fresh();
        $this->assertEquals('8730301', $fresh->cnae_principal);
        $this->assertEquals('as', $fresh->area_atuacao_cebas);
        $this->assertEquals('CNAS-12345', $fresh->cnas_numero);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->cnas_validade);
        $this->assertEquals('500000.00', $fresh->receita_bruta_anual_ref);
    }

    // ── Employee ─────────────────────────────────────────────────────────────

    public function test_employee_categoria_profissional_persiste(): void
    {
        $employee = Employee::create([
            'tenant_id'              => $this->tenant->id,
            'name'                   => 'Maria da Silva',
            'position'               => 'Assistente Social',
            'salary'                 => 3500.00,
            'work_hours_weekly'      => '40h',
            'contract_type'          => 'clt',
            'status'                 => 'active',
            'hired_at'               => now()->toDateString(),
            'categoria_profissional' => 'assistente_social',
        ]);

        $this->assertEquals('assistente_social', $employee->fresh()->categoria_profissional);
    }

    // ── Transaction ───────────────────────────────────────────────────────────

    public function test_transaction_elegivel_mrosc_e_fonte_persistem(): void
    {
        $transaction = Transaction::create([
            'tenant_id'     => $this->tenant->id,
            'description'   => 'Pagamento serviço MROSC',
            'amount'        => 1500.00,
            'type'          => 'expense',
            'date'          => now()->toDateString(),
            'status'         => 'paid',
            'elegivel_mrosc' => true,
            'fonte_recurso'  => 'mrosc',
        ]);

        $fresh = $transaction->fresh();
        $this->assertTrue($fresh->elegivel_mrosc);
        $this->assertEquals('mrosc', $fresh->fonte_recurso);
    }

    // ── NgoGrant ─────────────────────────────────────────────────────────────

    public function test_ngo_grant_campos_mrosc_persistem(): void
    {
        $grant = NgoGrant::create([
            'tenant_id'               => $this->tenant->id,
            'title'                   => 'Parceria CRAS Sul',
            'agency'                  => 'SMADS',
            'value'                   => 120000.00,
            'start_date'              => now()->toDateString(),
            'deadline'                => now()->addYear()->toDateString(),
            'status'                  => 'active',
            'modalidade'              => 'colaboracao',
            'numero_instrumento'      => 'TC-2026-001',
            'orgao_concedente_codigo' => 'SMADS-SP',
        ]);

        $fresh = $grant->fresh();
        $this->assertEquals('colaboracao', $fresh->modalidade);
        $this->assertEquals('TC-2026-001', $fresh->numero_instrumento);
        $this->assertEquals('SMADS-SP', $fresh->orgao_concedente_codigo);
    }

    // ── ProjectStage ─────────────────────────────────────────────────────────

    public function test_project_stage_executed_value_persiste(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $stage = ProjectStage::create([
            'tenant_id'      => $this->tenant->id,
            'project_id'     => $project->id,
            'title'          => 'Meta 1 — Atendimentos',
            'planned_value'  => 10000.00,
            'executed_value' => 8500.00,
            'status'         => 'in_progress',
            'order'          => 1,
        ]);

        $fresh = $stage->fresh();
        $this->assertEquals('8500.00', $fresh->executed_value);

        $desvio = abs($fresh->planned_value - $fresh->executed_value) / $fresh->planned_value * 100;
        $this->assertLessThan(25, $desvio); // desvio de 15% — dentro do limite MROSC
    }

    // ── Seeders ───────────────────────────────────────────────────────────────

    public function test_seeder_cria_requisitos_legais_sem_erro(): void
    {
        $this->seed(RequisitoLegalSeeder::class);

        $this->assertDatabaseCount('requisitos_legais', 30);
        $this->assertDatabaseHas('requisitos_legais', ['codigo' => 'CEBAS-AS-002']);
        $this->assertDatabaseHas('requisitos_legais', ['codigo' => 'MROSC-P-007']);
        $this->assertDatabaseHas('requisitos_legais', ['codigo' => 'SUAS-OP-002']);
    }

    public function test_seeder_cria_regras_avaliacao_com_threshold_editavel(): void
    {
        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $regraGratuidade = RegraAvaliacao::whereHas('requisito', fn ($q) => $q->where('codigo', 'CEBAS-AS-002'))->first();

        $this->assertNotNull($regraGratuidade);
        $this->assertEquals(20.00, (float) $regraGratuidade->threshold);
        $this->assertTrue($regraGratuidade->threshold_editavel_admin);
        $this->assertFalse($regraGratuidade->threshold_editavel_tenant);
    }

    public function test_seeder_idempotente(): void
    {
        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RequisitoLegalSeeder::class); // segunda execução não duplica

        $this->assertDatabaseCount('requisitos_legais', 30);
    }

    // ── CnpjApiService ────────────────────────────────────────────────────────

    public function test_cnpj_service_retorna_dados_esperados(): void
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'cnpj'                       => '11222333000181',
                'razao_social'               => 'ENTIDADE TESTE DE ASSISTENCIA SOCIAL',
                'cnae_fiscal'                => 8730301,
                'data_inicio_atividade'      => '2005-08-20',
                'descricao_situacao_cadastral' => 'ATIVA',
                'logradouro'                 => 'Rua das Flores',
                'numero'                     => '100',
                'complemento'                => 'Sala 1',
                'bairro'                     => 'Centro',
                'municipio'                  => 'São Paulo',
                'uf'                         => 'SP',
                'cep'                        => '01310-000',
            ], 200),
        ]);

        $service = new CnpjApiService();
        $result  = $service->consultar('11.222.333/0001-81');

        $this->assertNotNull($result);
        $this->assertEquals('ENTIDADE TESTE DE ASSISTENCIA SOCIAL', $result['razao_social']);
        $this->assertEquals('8730301', $result['cnae_principal']);
        $this->assertEquals('2005-08-20', $result['data_fundacao']);
        $this->assertEquals('SP', $result['uf']);
        $this->assertEquals('01310000', $result['cep']);
    }

    public function test_cnpj_service_retorna_null_em_falha(): void
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response(['message' => 'CNPJ inválido'], 404),
        ]);

        $service = new CnpjApiService();
        $result  = $service->consultar('00000000000000');

        $this->assertNull($result);
    }

    public function test_cnpj_service_retorna_null_para_cnpj_invalido(): void
    {
        $service = new CnpjApiService();
        $result  = $service->consultar('123');

        $this->assertNull($result);
    }
}
