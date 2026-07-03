<?php

namespace Tests\Feature\StrategyRoom;

use App\Enums\StrategyRoomMode;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\StrategyRoom\ProgramsAgentTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sala de Estrategia — tools de operacao da Sofia no modo Negocio:
 * termometro_teto_mei (wrapper do MeiPanelService) e notas_fiscais_pendentes
 * (cobertura de NFS-e + valor sem nota). Valida tambem que o modo negocio
 * nao expoe frequencia_e_evasao (conceito do terceiro setor).
 */
class ProgramsBusinessToolsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mei.teto_anual_centavos' => 8_100_000]); // R$ 81.000,00

        $this->tenant = Tenant::factory()->create([
            'type'          => 'common',
            'business_type' => 'mei',
        ]);
    }

    private function income(float $amount, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'tenant_id'   => $this->tenant->id,
            'description' => 'Venda teste',
            'amount'      => $amount,
            'type'        => 'income',
            'status'      => 'paid',
            'date'        => now()->toDateString(),
        ], $overrides));
    }

    /** @test */
    public function termometro_devolve_percentual_e_status_para_mei(): void
    {
        $this->income(40_500.00); // 50% do teto de R$ 81.000

        $out = ProgramsAgentTools::execute('termometro_teto_mei', [], $this->tenant->id);

        $this->assertTrue($out['success']);
        $this->assertTrue($out['aplicavel']);
        $this->assertSame(50.0, (float) $out['percentual_teto']);
        $this->assertSame('verde', $out['status']);
        $this->assertSame(40_500.0, (float) $out['realizado_reais']);
        $this->assertSame(40_500.0, (float) $out['faltam_reais']);
        $this->assertArrayHasKey('das', $out);
        $this->assertFalse($out['das']['pago_este_mes']);
    }

    /** @test */
    public function termometro_nao_se_aplica_a_tenant_nao_mei(): void
    {
        $pj = Tenant::factory()->create([
            'type'          => 'common',
            'business_type' => 'pj',
        ]);

        $out = ProgramsAgentTools::execute('termometro_teto_mei', [], $pj->id);

        $this->assertTrue($out['success']);
        $this->assertFalse($out['aplicavel']);
        $this->assertArrayNotHasKey('percentual_teto', $out);
    }

    /** @test */
    public function notas_pendentes_conta_income_pago_sem_nfse(): void
    {
        $this->income(100.00, ['nfse_numero' => 'NF-001']);
        $this->income(250.00); // sem nota
        $this->income(150.00); // sem nota
        // pending fica fora da base
        $this->income(999.00, ['status' => 'pending']);
        // ano passado fica fora
        $this->income(500.00, ['date' => now()->subYear()->toDateString()]);

        $out = ProgramsAgentTools::execute('notas_fiscais_pendentes', [], $this->tenant->id);

        $this->assertTrue($out['success']);
        $this->assertSame(3, (int) $out['total_receitas_pagas']);
        $this->assertSame(1, (int) $out['com_nfse']);
        $this->assertSame(2, (int) $out['sem_nfse']);
        $this->assertSame(400.0, (float) $out['valor_sem_nota_reais']);
        $this->assertSame(33.3, (float) $out['percentual_com_nfse']);
    }

    /** @test */
    public function modo_negocio_nao_expoe_frequencia_e_evasao(): void
    {
        $negocio = collect(ProgramsAgentTools::definitions(StrategyRoomMode::Negocio))
            ->pluck('function.name')->all();
        $institucional = collect(ProgramsAgentTools::definitions())
            ->pluck('function.name')->all();

        $this->assertSame(['termometro_teto_mei', 'notas_fiscais_pendentes', 'execucao_de_tarefas'], $negocio);
        $this->assertNotContains('frequencia_e_evasao', $negocio);

        $this->assertSame(['frequencia_e_evasao', 'execucao_de_tarefas'], $institucional);
    }
}
