<?php

namespace Tests\Feature\StrategyRoom;

use App\Enums\StrategyRoomMode;
use App\Models\Client;
use App\Models\Prospect;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sala de Estrategia — tools de mercado da Maria no modo Negocio (MEI/PJ):
 * pipeline_de_clientes, recibos_emitidos e prospeccao. Valida agregacao,
 * isolamento de tenant e a troca das definitions por modo.
 */
class IntelligenceBusinessToolsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['type' => 'common']);
    }

    private function makeClient(string $name, ?int $tenantId = null): Client
    {
        return Client::create([
            'tenant_id' => $tenantId ?? $this->tenant->id,
            'name'      => $name,
            'type'      => 'individual',
        ]);
    }

    private function income(array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'tenant_id'   => $this->tenant->id,
            'description' => 'Venda teste',
            'amount'      => 100.00,
            'type'        => 'income',
            'status'      => 'paid',
            'date'        => now()->subDays(10)->toDateString(),
        ], $overrides));
    }

    /** @test */
    public function pipeline_agrega_receita_por_cliente_do_tenant(): void
    {
        $alfa = $this->makeClient('Cliente Alfa');
        $beta = $this->makeClient('Cliente Beta');

        $this->income(['client_id' => $alfa->id, 'amount' => 300.00]);
        $this->income(['client_id' => $alfa->id, 'amount' => 200.00]);
        $this->income(['client_id' => $beta->id, 'amount' => 50.00]);
        // Receita sem cliente vinculado nao entra no pipeline.
        $this->income(['amount' => 999.00]);

        // Cliente de OUTRO tenant com receita — nunca pode vazar.
        $outroTenant  = Tenant::factory()->create(['type' => 'common']);
        $clienteAlheio = $this->makeClient('Cliente Alheio', $outroTenant->id);
        Transaction::create([
            'tenant_id'   => $outroTenant->id,
            'client_id'   => $clienteAlheio->id,
            'description' => 'Venda alheia',
            'amount'      => 5000.00,
            'type'        => 'income',
            'status'      => 'paid',
            'date'        => now()->subDays(5)->toDateString(),
        ]);

        $out = \App\Services\StrategyRoom\IntelligenceAgentTools::execute(
            'pipeline_de_clientes', [], $this->tenant->id
        );

        $this->assertTrue($out['success']);
        $this->assertSame(2, (int) $out['total_clientes']);
        $this->assertSame(2, (int) $out['clientes_com_receita']);
        $this->assertCount(2, $out['top_clientes']);
        $this->assertSame('Cliente Alfa', $out['top_clientes'][0]['nome']);
        $this->assertSame(500.0, (float) $out['top_clientes'][0]['receita_total']);

        $nomes = array_column($out['top_clientes'], 'nome');
        $this->assertNotContains('Cliente Alheio', $nomes);
    }

    /** @test */
    public function recibos_mede_vinculo_a_cliente_e_links_ativos(): void
    {
        // Todo income ganha token/recibo automatico no Transaction::booted();
        // o que a tool mede e vinculo a cliente do CRM e links ainda ativos.
        $cliente = $this->makeClient('Cliente Recibo');

        $this->income(['amount' => 100.00, 'client_id' => $cliente->id]);
        $this->income(['amount' => 200.00, 'client_id' => $cliente->id]);
        $avulsa = $this->income(['amount' => 700.00]); // venda avulsa, sem cliente
        // pending nao conta como receita paga
        $this->income(['amount' => 50.00, 'status' => 'pending']);

        // Link da venda avulsa revogado (expira no passado).
        Transaction::withoutGlobalScopes()->whereKey($avulsa->id)
            ->update(['public_receipt_expires_at' => now()->subDay()]);

        $out = \App\Services\StrategyRoom\IntelligenceAgentTools::execute(
            'recibos_emitidos', [], $this->tenant->id
        );

        $this->assertTrue($out['success']);
        $this->assertSame(3, (int) $out['receitas_pagas']);
        $this->assertSame(2, (int) $out['com_cliente']);
        $this->assertSame(1, (int) $out['sem_cliente']);
        $this->assertSame(1000.0, (float) $out['valor_total']);
        $this->assertSame(300.0, (float) $out['valor_com_cliente']);
        $this->assertSame(66.7, (float) $out['percentual_com_cliente']);
        $this->assertSame(2, (int) $out['links_ativos']);
        $this->assertSame(1, (int) $out['links_expirados']);
    }

    /** @test */
    public function prospeccao_agrupa_por_status_sem_vazar_outro_tenant(): void
    {
        Prospect::create(['tenant_id' => $this->tenant->id, 'company_name' => 'Padaria Boa', 'lead_score' => 85, 'status' => 'analyzed', 'source' => 'maps']);
        Prospect::create(['tenant_id' => $this->tenant->id, 'company_name' => 'Mercado Top', 'lead_score' => 60, 'status' => 'analyzed', 'source' => 'maps']);
        Prospect::create(['tenant_id' => $this->tenant->id, 'company_name' => 'Oficina Ze',  'lead_score' => 40, 'status' => 'contacted', 'source' => 'web']);

        $outroTenant = Tenant::factory()->create(['type' => 'common']);
        Prospect::create(['tenant_id' => $outroTenant->id, 'company_name' => 'Lead Alheio', 'lead_score' => 99, 'status' => 'new', 'source' => 'maps']);

        $out = \App\Services\StrategyRoom\IntelligenceAgentTools::execute(
            'prospeccao', ['status' => 'todos'], $this->tenant->id
        );

        $this->assertTrue($out['success']);
        $this->assertSame(3, (int) $out['total']);
        $this->assertSame(2, (int) $out['por_status']['analyzed']);
        $this->assertSame(1, (int) $out['por_status']['contacted']);
        $this->assertArrayNotHasKey('new', $out['por_status']);
        $this->assertSame('Padaria Boa', $out['prospects'][0]['empresa']);

        $empresas = array_column($out['prospects'], 'empresa');
        $this->assertNotContains('Lead Alheio', $empresas);
    }

    /** @test */
    public function definitions_troca_editais_por_tools_de_mercado_no_modo_negocio(): void
    {
        $institucional = collect(\App\Services\StrategyRoom\IntelligenceAgentTools::definitions())
            ->pluck('function.name')->all();
        $negocio = collect(\App\Services\StrategyRoom\IntelligenceAgentTools::definitions(StrategyRoomMode::Negocio))
            ->pluck('function.name')->all();

        $this->assertContains('buscar_editais_cadastrados', $institucional);
        $this->assertNotContains('pipeline_de_clientes', $institucional);

        $this->assertSame(['pipeline_de_clientes', 'recibos_emitidos', 'prospeccao'], $negocio);
        $this->assertNotContains('buscar_editais_cadastrados', $negocio);
    }
}
