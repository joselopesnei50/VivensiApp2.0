<?php

namespace Tests\Feature\Ngo;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exports CSV do relatorio anual — cobrem o dispatcher unificado
 * `?format=detailed|grouped|grouped-simple|pivot-type|pivot-user` e os
 * 5 wrappers legados (rotas antigas continuam funcionando).
 *
 * Nota: as consultas usam `MONTH(date)` e joins com beneficiaries/users,
 * que rodam em SQLite in-memory (dialeto e comum entre MySQL e SQLite).
 */
class BeneficiaryReportExportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
            'email'     => 'ngo_' . uniqid() . '@example.com',
            'name'      => 'Fulana Assistente',
        ]);
        $this->actingAs($this->user);

        // Cenario: 1 beneficiario com 2 atendimentos em julho/2026 do mesmo tipo
        $b = Beneficiary::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Maria da Silva',
            'status'    => 'active',
        ]);
        Attendance::create([
            'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id, 'user_id' => $this->user->id,
            'date' => '2026-07-05', 'type' => 'psicossocial', 'description' => 'Sessao inicial',
        ]);
        Attendance::create([
            'tenant_id' => $this->tenant->id, 'beneficiary_id' => $b->id, 'user_id' => $this->user->id,
            'date' => '2026-07-20', 'type' => 'psicossocial', 'description' => 'Segunda sessao',
        ]);
    }

    /** @test */
    public function endpoint_unificado_com_format_detailed_gera_uma_linha_por_atendimento(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=detailed&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringContainsString('Maria da Silva', $body);
        $this->assertStringContainsString('Sessao inicial', $body);
        $this->assertStringContainsString('Segunda sessao', $body);
    }

    /** @test */
    public function endpoint_unificado_com_format_grouped_agrega_por_mes_tipo_tecnico(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=grouped&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        // Cabecalho de agrupado
        $this->assertStringContainsString('Técnico/Usuário', $body);
        // Mes 07 com 2 atendimentos do mesmo tipo pelo mesmo usuario
        $this->assertMatchesRegularExpression('/2026,07,psicossocial,"?Fulana Assistente"?,2,1/', $body);
    }

    /** @test */
    public function endpoint_unificado_com_format_grouped_simple_omite_tecnico(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=grouped-simple&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        // Simple nao mostra usuario
        $this->assertStringNotContainsString('Técnico', explode("\n", $body)[0] ?? '');
        // Linha de julho agrupada
        $this->assertStringContainsString('psicossocial,2,1', $body);
    }

    /** @test */
    public function endpoint_unificado_com_format_pivot_type_gera_matriz_tipo_x_mes(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=pivot-type&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringContainsString('Tipo', $body);
        // psicossocial deve aparecer com 2 no mes 07 (sétima coluna após Tipo)
        $this->assertMatchesRegularExpression('/psicossocial,0,0,0,0,0,0,2,0,0,0,0,0,2,1/', $body);
    }

    /** @test */
    public function endpoint_unificado_com_format_pivot_user_agrega_por_tecnico(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=pivot-user&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringContainsString('Fulana Assistente', $body);
        // 2 atendimentos no mes 07 do mesmo tecnico
        $this->assertMatchesRegularExpression('/"?Fulana Assistente"?,0,0,0,0,0,0,2,0,0,0,0,0,2,1/', $body);
    }

    /** @test */
    public function format_invalido_cai_em_detailed(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=xpto&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        // Comportamento identico ao detailed
        $this->assertStringContainsString('Maria da Silva', $body);
        $this->assertStringContainsString('Sessao inicial', $body);
    }

    /** @test */
    public function rota_legada_export_sem_format_continua_funcionando_como_detailed(): void
    {
        // Wrapper thin: /reports/annual/export -> annualReportExportCsv -> dispatcher(detailed)
        $r = $this->get('/ngo/beneficiaries/reports/annual/export?year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringContainsString('Maria da Silva', $body);
    }

    /** @test */
    public function rota_legada_export_grouped_continua_funcionando(): void
    {
        $r = $this->get('/ngo/beneficiaries/reports/annual/export-grouped?year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringContainsString('Técnico/Usuário', $body);
    }

    /** @test */
    public function tenant_isolation_dispatcher_nao_expoe_atendimentos_de_outro_tenant(): void
    {
        $outro = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $bOutro = Beneficiary::create([
            'tenant_id' => $outro->id, 'name' => 'ALHEIO SIGILOSO', 'status' => 'active',
        ]);
        Attendance::create([
            'tenant_id' => $outro->id, 'beneficiary_id' => $bOutro->id, 'user_id' => 1,
            'date' => '2026-07-10', 'type' => 'x', 'description' => 'nao pode aparecer',
        ]);

        $r = $this->get('/ngo/beneficiaries/reports/annual/export.csv?format=detailed&year=2026');
        $r->assertOk();
        $body = $r->streamedContent();
        $this->assertStringNotContainsString('ALHEIO SIGILOSO', $body);
        $this->assertStringNotContainsString('nao pode aparecer', $body);
    }
}
