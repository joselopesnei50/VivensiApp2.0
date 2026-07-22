<?php

namespace Tests\Feature\Conformidade;

use App\Models\AvaliacaoRequisito;
use App\Models\CicloConformidade;
use App\Models\Evidencia;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequisitoDetalheTest extends TestCase
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

        Storage::fake('local');
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

    // ── GET /ngo/conformidade/requisito/{id} ────────────────────────────────────

    public function test_detalhe_renderiza_para_admin(): void
    {
        $req = RequisitoLegal::where('tipo', 'A')->first();

        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.requisito', $req->id))
             ->assertStatus(200)
             ->assertSee($req->titulo);
    }

    public function test_detalhe_bloqueado_para_employee(): void
    {
        $req = RequisitoLegal::first();

        $this->actingAs($this->employee)
             ->get(route('ngo.conformidade.requisito', $req->id))
             ->assertStatus(403);
    }

    public function test_detalhe_mostra_historico_de_avaliacoes(): void
    {
        $req  = RequisitoLegal::where('tipo', 'C')->first();
        $ciclo = $this->cicloParaEixo($req->eixo);

        AvaliacaoRequisito::create([
            'tenant_id'             => $this->tenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => $this->admin->id,
            'observacoes'           => 'Requisito cumprido conforme normativa vigente.',
        ]);

        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.requisito', $req->id))
             ->assertStatus(200)
             ->assertSee('Requisito cumprido conforme normativa vigente.');
    }

    public function test_detalhe_requisito_inexistente_retorna_404(): void
    {
        $this->actingAs($this->admin)
             ->get(route('ngo.conformidade.requisito', 99999))
             ->assertStatus(404);
    }

    // ── POST /ngo/conformidade/avaliacao/{id}/evidencia ─────────────────────────

    public function test_upload_evidencia_tipo_c_cria_registro(): void
    {
        $req  = RequisitoLegal::where('tipo', 'C')->first();
        $ciclo = $this->cicloParaEixo($req->eixo);

        $avaliacao = AvaliacaoRequisito::create([
            'tenant_id'             => $this->tenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => $this->admin->id,
            'observacoes'           => 'Cumprido.',
        ]);

        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.evidencia.upload', $avaliacao->id), [
                 'arquivo'   => UploadedFile::fake()->create('ata.pdf', 200, 'application/pdf'),
                 'descricao' => 'Ata de reunião do conselho fiscal 2026',
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertEquals(1, Evidencia::where('avaliacao_requisito_id', $avaliacao->id)->count());
    }

    public function test_upload_evidencia_tipo_a_retorna_422(): void
    {
        $req  = RequisitoLegal::where('tipo', 'A')->first();
        $ciclo = $this->cicloParaEixo($req->eixo);

        $avaliacao = AvaliacaoRequisito::create([
            'tenant_id'             => $this->tenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => null,
        ]);

        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.evidencia.upload', $avaliacao->id), [
                 'arquivo'   => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                 'descricao' => 'Arquivo de evidência',
             ])
             ->assertStatus(422);
    }

    public function test_upload_evidencia_outro_tenant_retorna_404(): void
    {
        $outerTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $req          = RequisitoLegal::where('tipo', 'C')->first();
        $ciclo        = CicloConformidade::create([
            'tenant_id'   => $outerTenant->id,
            'eixo'        => $req->eixo,
            'data_inicio' => now()->startOfYear(),
            'data_fim'    => now()->addYear(),
            'status'      => 'em_andamento',
        ]);

        $avaliacao = AvaliacaoRequisito::create([
            'tenant_id'             => $outerTenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => $this->admin->id,
            'observacoes'           => 'Cumprido.',
        ]);

        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.evidencia.upload', $avaliacao->id), [
                 'arquivo'   => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                 'descricao' => 'Tentativa de IDOR',
             ])
             ->assertStatus(404);
    }

    public function test_upload_evidencia_bloqueado_para_employee(): void
    {
        $req  = RequisitoLegal::where('tipo', 'C')->first();
        $ciclo = $this->cicloParaEixo($req->eixo);

        $avaliacao = AvaliacaoRequisito::create([
            'tenant_id'             => $this->tenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => $this->admin->id,
            'observacoes'           => 'Cumprido.',
        ]);

        $this->actingAs($this->employee)
             ->post(route('ngo.conformidade.evidencia.upload', $avaliacao->id), [
                 'arquivo'   => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                 'descricao' => 'Tentativa employee',
             ])
             ->assertStatus(403);
    }

    public function test_upload_evidencia_descricao_curta_falha_validacao(): void
    {
        $req  = RequisitoLegal::where('tipo', 'C')->first();
        $ciclo = $this->cicloParaEixo($req->eixo);

        $avaliacao = AvaliacaoRequisito::create([
            'tenant_id'             => $this->tenant->id,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'    => $req->id,
            'resultado'             => 'verde',
            'avaliado_em'           => now(),
            'avaliado_por'          => $this->admin->id,
            'observacoes'           => 'Cumprido.',
        ]);

        $this->actingAs($this->admin)
             ->post(route('ngo.conformidade.evidencia.upload', $avaliacao->id), [
                 'arquivo'   => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                 'descricao' => 'ok',
             ])
             ->assertSessionHasErrors('descricao');
    }
}
