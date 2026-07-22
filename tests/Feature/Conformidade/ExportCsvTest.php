<?php

namespace Tests\Feature\Conformidade;

use App\Models\AvaliacaoRequisito;
use App\Models\CicloConformidade;
use App\Models\RequisitoLegal;
use App\Models\SnapshotConformidade;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCsvTest extends TestCase
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

    private function cicloParaEixo(string $eixo): CicloConformidade
    {
        return CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => $eixo,
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->addYears(3),
            'status'      => 'em_andamento',
        ]);
    }

    // ── GET /ngo/conformidade/export ────────────────────────────────────────────

    public function test_export_csv_retorna_arquivo(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.export'))
             ->assertStatus(200)
             ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.export'))
             ->assertStatus(403);
    }

    public function test_export_csv_contem_cabecalho(): void
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('ngo.conformidade.export'));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Código', $content);
        $this->assertStringContainsString('Resultado', $content);
        $this->assertStringContainsString('Avaliado Em', $content);
    }

    public function test_export_csv_filtra_por_eixo(): void
    {
        $reqSuas = RequisitoLegal::where('eixo', 'suas')->first();
        $reqMrosc = RequisitoLegal::where('eixo', 'mrosc')->first();

        if ($reqSuas) {
            $ciclo = $this->cicloParaEixo('suas');
            AvaliacaoRequisito::create([
                'tenant_id'             => $this->tenant->id,
                'ciclo_conformidade_id' => $ciclo->id,
                'requisito_legal_id'    => $reqSuas->id,
                'resultado'             => 'verde',
                'avaliado_em'           => now(),
                'avaliado_por'          => null,
            ]);
        }

        if ($reqMrosc) {
            $ciclo = $this->cicloParaEixo('mrosc');
            AvaliacaoRequisito::create([
                'tenant_id'             => $this->tenant->id,
                'ciclo_conformidade_id' => $ciclo->id,
                'requisito_legal_id'    => $reqMrosc->id,
                'resultado'             => 'amarelo',
                'avaliado_em'           => now(),
                'avaliado_por'          => null,
            ]);
        }

        if ($reqSuas && $reqMrosc) {
            $response = $this->actingAs($this->admin)
                             ->get(route('ngo.conformidade.export', ['eixo' => 'suas']));

            $content = $response->streamedContent();
            $this->assertStringContainsString($reqSuas->codigo, $content);
            $this->assertStringNotContainsString($reqMrosc->codigo, $content);
        } else {
            $this->markTestSkipped('Sem requisitos SUAS ou MROSC no seeder.');
        }
    }

    public function test_export_valida_eixo_invalido(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.export', ['eixo' => 'invalido']))
             ->assertSessionHasErrors('eixo');
    }

    // ── POST /ngo/conformidade/snapshot ─────────────────────────────────────────

    public function test_snapshot_cria_registro(): void
    {
        CicloConformidade::create([
            'tenant_id'   => $this->tenant->id,
            'eixo'        => 'suas',
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->endOfYear(),
            'status'      => 'em_andamento',
        ]);

        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.snapshot'))
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('snapshots_conformidade', [
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_snapshot_bloqueado_para_employee(): void
    {
        $this->actingAs($this->employee)
             ->post(route('ngo.conformidade.snapshot'))
             ->assertStatus(403);
    }
}
