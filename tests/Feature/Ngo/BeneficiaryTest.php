<?php

namespace Tests\Feature\Ngo;

use App\Jobs\GeocodeAddressJob;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cobertura de CRUD, PII (encrypt at-rest), attendance, family members,
 * tenant isolation (IDOR) e export do modulo /ngo/beneficiaries.
 *
 * O modulo tem PII sensivel (CPF/NIS criptografado com blind index HMAC)
 * e isolamento por tenant obrigatorio — cada teste que toca em recurso
 * de outro tenant precisa validar 404 e persistencia intacta do original.
 */
class BeneficiaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([GeocodeAddressJob::class]);

        // SQLite em memoria nao respeita ON DELETE SET NULL sem isso — o teste
        // de nullOnDelete cross-model depende deste comportamento.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
            'email'     => 'ngo_' . uniqid() . '@example.com',
        ]);
        $this->actingAs($this->user);
    }

    private function makeBeneficiary(array $attrs = [], ?Tenant $tenant = null): Beneficiary
    {
        return Beneficiary::create(array_merge([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'name'      => 'Maria Silva',
            'status'    => 'active',
        ], $attrs));
    }

    // ── Index + filtros ──────────────────────────────────────────────────────

    /** @test */
    public function index_lista_beneficiarios_do_tenant_e_aplica_filtros(): void
    {
        $this->makeBeneficiary(['name' => 'Ana Match', 'status' => 'active']);
        $this->makeBeneficiary(['name' => 'Beto Fora', 'status' => 'inactive']);

        // Sem filtro: ambos aparecem
        $this->get('/ngo/beneficiaries')
            ->assertOk()
            ->assertSee('Ana Match')
            ->assertSee('Beto Fora');

        // Filtro por termo
        $this->get('/ngo/beneficiaries?q=Ana')
            ->assertOk()
            ->assertSee('Ana Match')
            ->assertDontSee('Beto Fora');

        // Filtro por status
        $this->get('/ngo/beneficiaries?status=inactive')
            ->assertOk()
            ->assertSee('Beto Fora')
            ->assertDontSee('Ana Match');
    }

    /** @test */
    public function index_nao_lista_beneficiarios_de_outro_tenant(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->makeBeneficiary(['name' => 'Alheio Vitima'], $outroTenant);

        $this->get('/ngo/beneficiaries')
            ->assertOk()
            ->assertDontSee('Alheio Vitima');
    }

    /** @test */
    public function index_busca_por_cpf_via_blind_index_com_e_sem_mascara(): void
    {
        // Cadastrado via POST — controller normaliza pra digitos antes de salvar,
        // entao cpf_bidx = hash_hmac('11122233344').
        $this->post('/ngo/beneficiaries', [
            'name'   => 'Ana Match CPF',
            'cpf'    => '111.222.333-44',
            'status' => 'active',
        ])->assertRedirect();

        $this->makeBeneficiary(['name' => 'Beto Sem CPF']);

        // Busca com formatacao
        $this->get('/ngo/beneficiaries?q=' . urlencode('111.222.333-44'))
            ->assertOk()
            ->assertSee('Ana Match CPF')
            ->assertDontSee('Beto Sem CPF');

        // Busca sem formatacao (11 digitos)
        $this->get('/ngo/beneficiaries?q=11122233344')
            ->assertOk()
            ->assertSee('Ana Match CPF')
            ->assertDontSee('Beto Sem CPF');
    }

    /** @test */
    public function index_busca_por_nis_via_blind_index(): void
    {
        $this->post('/ngo/beneficiaries', [
            'name'   => 'Carlos NIS',
            'nis'    => '99988877766',
            'status' => 'active',
        ])->assertRedirect();

        $this->makeBeneficiary(['name' => 'Diana Sem NIS']);

        $this->get('/ngo/beneficiaries?q=99988877766')
            ->assertOk()
            ->assertSee('Carlos NIS')
            ->assertDontSee('Diana Sem NIS');
    }

    /** @test */
    public function export_csv_busca_por_cpf_filtra_via_blind_index(): void
    {
        $this->post('/ngo/beneficiaries', [
            'name'   => 'Eva CSV',
            'cpf'    => '11122233344',
            'status' => 'active',
        ])->assertRedirect();

        $this->post('/ngo/beneficiaries', [
            'name'   => 'Fabio Outro',
            'cpf'    => '55566677788',
            'status' => 'active',
        ])->assertRedirect();

        $r = $this->get('/ngo/beneficiaries/export?q=11122233344');
        $r->assertOk();
        $content = $r->streamedContent();
        expect($content)->toContain('Eva CSV');
        expect($content)->not->toContain('Fabio Outro');
    }

    // ── Store (PII criptografada) ────────────────────────────────────────────

    /** @test */
    public function store_cria_beneficiario_com_cpf_criptografado_e_blind_index_setado(): void
    {
        $this->post('/ngo/beneficiaries', [
            'name'       => 'Novo Beneficiario',
            'cpf'        => '123.456.789-10',
            'nis'        => '98765432100',
            'birth_date' => '1990-04-15',
            'gender'     => 'masculino',
            'status'     => 'active',
        ])->assertRedirect('/ngo/beneficiaries');

        $b = Beneficiary::where('tenant_id', $this->tenant->id)->firstOrFail();
        expect($b->name)->toBe('Novo Beneficiario');
        // Accessor desencripta pra CPF/NIS puros (so digitos)
        expect($b->cpf)->toBe('12345678910');
        expect($b->nis)->toBe('98765432100');

        // Raw storage: nao pode estar em plaintext
        $raw = DB::table('beneficiaries')->where('id', $b->id)->first(['cpf', 'nis', 'cpf_bidx', 'nis_bidx']);
        expect($raw->cpf)->not->toBe('12345678910');
        expect($raw->nis)->not->toBe('98765432100');
        // Blind index deve estar setado
        expect($raw->cpf_bidx)->toBe(hash_hmac('sha256', '12345678910', config('app.key')));
        expect($raw->nis_bidx)->toBe(hash_hmac('sha256', '98765432100', config('app.key')));
    }

    /** @test */
    public function store_rejeita_cpf_duplicado_no_mesmo_tenant(): void
    {
        $this->makeBeneficiary(['cpf' => '12345678910']);

        $this->post('/ngo/beneficiaries', [
            'name' => 'Segundo com mesmo CPF',
            'cpf'  => '123.456.789-10',
            'status' => 'active',
        ])->assertSessionHasErrors('cpf');

        expect(Beneficiary::where('tenant_id', $this->tenant->id)->count())->toBe(1);
    }

    /** @test */
    public function store_aceita_mesmo_cpf_em_tenants_diferentes(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->makeBeneficiary(['cpf' => '12345678910'], $outroTenant);

        // No meu tenant, o mesmo CPF ainda esta livre
        $this->post('/ngo/beneficiaries', [
            'name' => 'Novo do meu tenant',
            'cpf'  => '12345678910',
            'status' => 'active',
        ])->assertRedirect('/ngo/beneficiaries');

        expect(Beneficiary::where('tenant_id', $this->tenant->id)->count())->toBe(1);
    }

    // ── Show ──────────────────────────────────────────────────────────────

    /** @test */
    public function show_exibe_beneficiario_com_dados_e_bloqueia_cross_tenant(): void
    {
        $b = $this->makeBeneficiary(['name' => 'Detalhado']);

        $this->get("/ngo/beneficiaries/{$b->id}")
            ->assertOk()
            ->assertSee('Detalhado');

        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $alheio = $this->makeBeneficiary(['name' => 'Alheio'], $outroTenant);

        $this->get("/ngo/beneficiaries/{$alheio->id}")
            ->assertNotFound();
    }

    // ── Update ─────────────────────────────────────────────────────────────

    /** @test */
    public function update_atualiza_beneficiario_do_proprio_tenant(): void
    {
        $b = $this->makeBeneficiary(['name' => 'Antes']);

        $this->put("/ngo/beneficiaries/{$b->id}", [
            'name'   => 'Depois',
            'status' => 'graduated',
        ])->assertRedirect();

        $b->refresh();
        expect($b->name)->toBe('Depois');
        expect($b->status)->toBe('graduated');
    }

    /** @test */
    public function update_de_beneficiario_de_outro_tenant_retorna_404(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $alheio = $this->makeBeneficiary(['name' => 'Preserved'], $outroTenant);

        $this->put("/ngo/beneficiaries/{$alheio->id}", [
            'name'   => 'Tentativa de hack',
            'status' => 'inactive',
        ])->assertNotFound();

        expect($alheio->fresh()->name)->toBe('Preserved');
    }

    // ── Destroy ────────────────────────────────────────────────────────────

    /** @test */
    public function destroy_remove_beneficiario_do_proprio_tenant(): void
    {
        $b = $this->makeBeneficiary();

        $this->delete("/ngo/beneficiaries/{$b->id}")
            ->assertRedirect('/ngo/beneficiaries');

        expect(Beneficiary::where('id', $b->id)->exists())->toBeFalse();
    }

    /** @test */
    public function destroy_de_beneficiario_de_outro_tenant_retorna_404(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $alheio = $this->makeBeneficiary(['name' => 'Sobrevivente'], $outroTenant);

        $this->delete("/ngo/beneficiaries/{$alheio->id}")
            ->assertNotFound();

        expect(Beneficiary::where('id', $alheio->id)->exists())->toBeTrue();
    }

    // ── Attendance CRUD ────────────────────────────────────────────────────

    /** @test */
    public function attendance_store_cria_atendimento_vinculado_ao_beneficiario(): void
    {
        $b = $this->makeBeneficiary();

        $this->post("/ngo/beneficiaries/{$b->id}/attendance", [
            'date'        => '2026-07-18',
            'type'        => 'psicologico',
            'description' => 'Primeira sessao de acompanhamento',
        ])->assertRedirect();

        $a = Attendance::where('beneficiary_id', $b->id)->firstOrFail();
        expect($a->type)->toBe('psicologico');
        expect((int) $a->tenant_id)->toBe($this->tenant->id);
        expect((int) $a->user_id)->toBe($this->user->id);
    }

    /** @test */
    public function attendance_update_atualiza_registro_do_proprio_tenant(): void
    {
        $b = $this->makeBeneficiary();
        $a = Attendance::create([
            'tenant_id'      => $this->tenant->id,
            'beneficiary_id' => $b->id,
            'user_id'        => $this->user->id,
            'date'           => '2026-07-01',
            'type'           => 'social',
            'description'    => 'Sessao inicial',
        ]);

        $this->put("/ngo/beneficiaries/{$b->id}/attendance/{$a->id}", [
            'date'        => '2026-07-05',
            'type'        => 'psicologico',
            'description' => 'Sessao ajustada',
        ])->assertRedirect();

        $a->refresh();
        expect($a->type)->toBe('psicologico');
        expect($a->description)->toBe('Sessao ajustada');
    }

    /** @test */
    public function attendance_de_beneficiario_de_outro_tenant_retorna_404_ao_criar(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $alheio = $this->makeBeneficiary([], $outroTenant);

        $this->post("/ngo/beneficiaries/{$alheio->id}/attendance", [
            'date'        => '2026-07-18',
            'type'        => 'social',
            'description' => 'Tentativa de hack',
        ])->assertNotFound();

        expect(Attendance::where('beneficiary_id', $alheio->id)->count())->toBe(0);
    }

    /** @test */
    public function attendance_destroy_remove_do_proprio_beneficiario(): void
    {
        $b = $this->makeBeneficiary();
        $a = Attendance::create([
            'tenant_id'      => $this->tenant->id,
            'beneficiary_id' => $b->id,
            'user_id'        => $this->user->id,
            'date'           => '2026-07-01',
            'type'           => 'social',
            'description'    => 'x',
        ]);

        $this->delete("/ngo/beneficiaries/{$b->id}/attendance/{$a->id}")
            ->assertRedirect();

        expect(Attendance::where('id', $a->id)->exists())->toBeFalse();
    }

    // ── Family members ─────────────────────────────────────────────────────

    /** @test */
    public function family_member_store_adiciona_familiar_ao_beneficiario(): void
    {
        $b = $this->makeBeneficiary();

        $this->post("/ngo/beneficiaries/{$b->id}/family-members", [
            'name'       => 'Irma da Maria',
            'kinship'    => 'irma',
            'birth_date' => '2010-08-15',
        ])->assertRedirect();

        $m = FamilyMember::where('beneficiary_id', $b->id)->firstOrFail();
        expect($m->name)->toBe('Irma da Maria');
        expect($m->kinship)->toBe('irma');
    }

    /** @test */
    public function family_member_de_beneficiario_de_outro_tenant_retorna_404(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $alheio = $this->makeBeneficiary([], $outroTenant);

        $this->post("/ngo/beneficiaries/{$alheio->id}/family-members", [
            'name'    => 'Nao devia entrar',
            'kinship' => 'primo',
        ])->assertNotFound();

        expect(FamilyMember::where('beneficiary_id', $alheio->id)->count())->toBe(0);
    }

    /** @test */
    public function family_member_destroy_remove_do_proprio_beneficiario(): void
    {
        $b = $this->makeBeneficiary();
        $m = FamilyMember::create([
            'beneficiary_id' => $b->id,
            'name'           => 'Familiar teste',
            'kinship'        => 'mae',
        ]);

        $this->delete("/ngo/beneficiaries/{$b->id}/family-members/{$m->id}")
            ->assertRedirect();

        expect(FamilyMember::where('id', $m->id)->exists())->toBeFalse();
    }

    // ── Export CSV ─────────────────────────────────────────────────────────

    /** @test */
    public function export_csv_gera_arquivo_com_beneficiarios_do_tenant(): void
    {
        $this->makeBeneficiary(['name' => 'No CSV']);
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->makeBeneficiary(['name' => 'Nao deve aparecer'], $outroTenant);

        $r = $this->get('/ngo/beneficiaries/export');
        $r->assertOk();
        $content = $r->streamedContent();
        expect($content)->toContain('No CSV');
        expect($content)->not->toContain('Nao deve aparecer');
    }

    // ── Vinculo Beneficiary <-> ProjectPerson (opt-in) ────────────────────────

    /** @test */
    public function projeto_recebe_pessoa_vinculada_a_beneficiario(): void
    {
        $benef = $this->makeBeneficiary(['name' => 'Ana Beneficiaria']);
        $project = \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->post("/projects/{$project->id}/people", [
            'name'           => 'Ana Beneficiaria',
            'beneficiary_id' => $benef->id,
        ])->assertRedirect();

        $pp = \App\Models\ProjectPerson::where('project_id', $project->id)->firstOrFail();
        expect((int) $pp->beneficiary_id)->toBe($benef->id);
        expect((int) $pp->tenant_id)->toBe($this->tenant->id);
    }

    /** @test */
    public function beneficiary_id_de_outro_tenant_e_rejeitado_pela_validacao(): void
    {
        $outroTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $benefAlheio = $this->makeBeneficiary(['name' => 'Alheio'], $outroTenant);
        $project = \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->post("/projects/{$project->id}/people", [
            'name'           => 'Tentativa',
            'beneficiary_id' => $benefAlheio->id,
        ])->assertSessionHasErrors('beneficiary_id');

        expect(\App\Models\ProjectPerson::where('project_id', $project->id)->count())->toBe(0);
    }

    /** @test */
    public function vincular_mesmo_beneficiario_no_mesmo_projeto_duas_vezes_e_bloqueado(): void
    {
        $benef = $this->makeBeneficiary(['name' => 'Duplicada']);
        $project = \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id]);

        // Primeiro vinculo OK
        $this->post("/projects/{$project->id}/people", [
            'name'           => 'Duplicada',
            'beneficiary_id' => $benef->id,
        ])->assertRedirect();

        // Tentativa de duplicar
        $r = $this->post("/projects/{$project->id}/people", [
            'name'           => 'Duplicada de novo',
            'beneficiary_id' => $benef->id,
        ]);
        $r->assertRedirect();
        $r->assertSessionHas('error');

        expect(\App\Models\ProjectPerson::where('project_id', $project->id)->where('beneficiary_id', $benef->id)->count())->toBe(1);
    }

    /** @test */
    public function excluir_beneficiario_apenas_nullifica_a_ligacao_no_project_person(): void
    {
        $benef = $this->makeBeneficiary(['name' => 'Sera excluido']);
        $project = \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id]);
        $pp = \App\Models\ProjectPerson::create([
            'tenant_id'      => $this->tenant->id,
            'project_id'     => $project->id,
            'beneficiary_id' => $benef->id,
            'name'           => 'Sera excluido',
        ]);

        $this->delete("/ngo/beneficiaries/{$benef->id}")->assertRedirect();

        // ProjectPerson permanece; beneficiary_id vira null via FK nullOnDelete
        $pp->refresh();
        expect($pp->beneficiary_id)->toBeNull();
        expect($pp->name)->toBe('Sera excluido');
    }

    /** @test */
    public function show_do_beneficiario_lista_projetos_vinculados(): void
    {
        $benef = $this->makeBeneficiary(['name' => 'Com vinculo']);
        $project = \App\Models\Project::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Projeto Cultural']);
        \App\Models\ProjectPerson::create([
            'tenant_id'         => $this->tenant->id,
            'project_id'        => $project->id,
            'beneficiary_id'    => $benef->id,
            'name'              => 'Com vinculo',
            'enrollment_status' => 'ativo',
        ]);

        $this->get("/ngo/beneficiaries/{$benef->id}")
            ->assertOk()
            ->assertSee('Projetos vinculados')
            ->assertSee('Projeto Cultural');
    }
}
