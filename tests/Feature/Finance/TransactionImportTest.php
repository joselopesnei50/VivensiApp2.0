<?php

use App\Models\FinancialCategory;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function importUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'ngo',
    ]);
}

function makeCsv(string $content): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'csvtest');
    file_put_contents($tmp, $content);
    return new UploadedFile($tmp, 'planilha.csv', 'text/csv', null, true);
}

// ── Form + template ───────────────────────────────────────────────────────────

it('renderiza tela do import', function () {
    $this->actingAs(importUser())
        ->get('/finance/import')
        ->assertOk()
        ->assertSee('Importar planilha');
});

it('baixa template CSV', function () {
    $r = $this->actingAs(importUser())->get('/finance/import/template');
    $r->assertOk();
    expect(str_contains($r->headers->get('content-type'), 'text/csv'))->toBeTrue();
    expect(str_contains($r->streamedContent(), 'descricao,valor,data,tipo,categoria,projeto'))->toBeTrue();
});

// ── Preview ───────────────────────────────────────────────────────────────────

it('preview de CSV válido mostra linhas parseadas', function () {
    $csv = "descricao,valor,data,tipo\n"
         . "Aluguel,\"R\$ 1.500,00\",01/07/2026,despesa\n"
         . "Doacao,500.00,10/07/2026,receita\n";

    $r = $this->actingAs(importUser())
        ->post('/finance/import/preview', ['file' => makeCsv($csv)]);

    $r->assertOk();
    $r->assertSee('Aluguel');
    $r->assertSee('Doacao');
});

it('preview marca linhas com erro em vermelho', function () {
    $csv = "descricao,valor,data,tipo\n"
         . "Sem valor,,01/07/2026,despesa\n"
         . "Data invalida,100,32/13/2026,despesa\n"
         . "Tipo invalido,100,01/07/2026,xxx\n";

    $r = $this->actingAs(importUser())
        ->post('/finance/import/preview', ['file' => makeCsv($csv)]);

    $r->assertOk();
    $r->assertSee('valor inválido');
    $r->assertSee('tipo inválido');
});

it('preview rejeita CSV sem colunas obrigatorias', function () {
    $csv = "nome,quantidade\nfoo,1\n";
    $this->actingAs(importUser())
        ->post('/finance/import/preview', ['file' => makeCsv($csv)])
        ->assertRedirect();
});

// ── Import ────────────────────────────────────────────────────────────────────

it('confirma import cria transacoes no banco (admin ngo → aprovado)', function () {
    $user = importUser(); // role=ngo (admin)
    $csv  = "descricao,valor,data,tipo,categoria\n"
          . "Salario Ana,3200.00,05/07/2026,despesa,Folha\n"
          . "Doacao empresa,\"500,00\",10/07/2026,receita,Doações\n";

    // Preview primeiro pra popular session
    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);

    // Confirm
    $this->actingAs($user)->post('/finance/import/confirm')->assertRedirect();

    expect(Transaction::withoutGlobalScope('tenant')->count())->toBe(2);

    $salario = Transaction::withoutGlobalScope('tenant')->where('description', 'Salario Ana')->first();
    expect((float) $salario->amount)->toBe(3200.00);
    expect($salario->type)->toBe('expense');
    // Admin (ngo) importando: vai direto aprovado
    expect($salario->status)->toBe('paid');
    expect($salario->approval_status)->toBe('approved');
    expect((int) $salario->tenant_id)->toBe($user->tenant_id);
});

// ── Aprovacao por role ────────────────────────────────────────────────────────

it('subordinado (employee) importando despesa: fica em pending', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    $csv = "descricao,valor,data,tipo\nMaterial escritorio,120,01/07/2026,despesa\n";

    $this->actingAs($employee)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($employee)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Material escritorio')->first();
    expect($tx)->not->toBeNull();
    expect($tx->status)->toBe('pending');
    expect($tx->approval_status)->toBe('pending');
});

it('subordinado importando receita: nao precisa aprovacao', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    $csv = "descricao,valor,data,tipo\nDoacao pessoa fisica,50,01/07/2026,receita\n";

    $this->actingAs($employee)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($employee)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Doacao pessoa fisica')->first();
    expect($tx->status)->toBe('paid');
    expect($tx->approval_status)->toBe('approved');
});

it('manager importando despesa: aprovado direto (nao pending)', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $manager = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $csv = "descricao,valor,data,tipo\nConta luz,300,01/07/2026,despesa\n";

    $this->actingAs($manager)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($manager)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Conta luz')->first();
    expect($tx->status)->toBe('paid');
    expect($tx->approval_status)->toBe('approved');
});

it('categoria e criada se nao existir (find-or-create)', function () {
    $user = importUser();
    $csv  = "descricao,valor,data,tipo,categoria\n"
          . "X,100,01/07/2026,despesa,Nova Categoria\n";

    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    expect(FinancialCategory::withoutGlobalScope('tenant')->where('name', 'Nova Categoria')->exists())->toBeTrue();
});

it('deduplicacao ignora linha ja existente por description+date+amount', function () {
    $user = importUser();

    // Cria uma manualmente
    Transaction::create([
        'tenant_id'   => $user->tenant_id,
        'description' => 'Aluguel',
        'amount'      => 1500,
        'date'        => '2026-07-01',
        'type'        => 'expense',
        'status'      => 'paid',
    ]);

    // Tenta importar mesma linha
    $csv = "descricao,valor,data,tipo\nAluguel,1500,01/07/2026,despesa\n";
    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    // Continua com só 1 (não duplicou)
    expect(Transaction::withoutGlobalScope('tenant')->where('description', 'Aluguel')->count())->toBe(1);
});

it('project_id override do dropdown ganha da coluna projeto do CSV', function () {
    $user  = importUser();
    $projA = Project::factory()->create(['tenant_id' => $user->tenant_id, 'name' => 'Projeto A', 'status' => 'active']);
    $projB = Project::factory()->create(['tenant_id' => $user->tenant_id, 'name' => 'Projeto B', 'status' => 'active']);

    // CSV menciona Projeto B, mas dropdown seleciona Projeto A → todas viram A.
    $csv = "descricao,valor,data,tipo,categoria,projeto\n"
         . "X,100,01/07/2026,despesa,Cat,Projeto B\n"
         . "Y,200,02/07/2026,despesa,Cat,\n";

    $this->actingAs($user)->post('/finance/import/preview', [
        'file'       => makeCsv($csv),
        'project_id' => $projA->id,
    ]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $txs = Transaction::withoutGlobalScope('tenant')->get();
    expect($txs)->toHaveCount(2);
    foreach ($txs as $tx) {
        expect((int) $tx->project_id)->toBe($projA->id);
    }
});

it('project_id de outro tenant e ignorado (anti-IDOR)', function () {
    $mine   = importUser();
    $other  = importUser(); // outro tenant
    $othersProj = Project::factory()->create(['tenant_id' => $other->tenant_id, 'name' => 'Alheio', 'status' => 'active']);

    $csv = "descricao,valor,data,tipo\nX,100,01/07/2026,despesa\n";
    $this->actingAs($mine)->post('/finance/import/preview', [
        'file'       => makeCsv($csv),
        'project_id' => $othersProj->id,
    ]);
    $this->actingAs($mine)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'X')->first();
    expect($tx->project_id)->toBeNull(); // override rejeitado; sem coluna projeto no CSV, fica null
});

it('projeto por nome e resolvido corretamente', function () {
    $user = importUser();
    $project = Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => 'Projeto Musica',
        'status'    => 'active',
    ]);

    $csv = "descricao,valor,data,tipo,categoria,projeto\n"
         . "Instrumento,200,01/07/2026,despesa,Insumos,Projeto Musica\n";

    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Instrumento')->first();
    expect((int) $tx->project_id)->toBe($project->id);
});

it('rejeita arquivo maior que 5 MB', function () {
    $big = str_repeat("descricao,valor,data,tipo\n" . str_repeat('X', 5000) . ",100,01/07/2026,despesa\n", 200);
    $this->actingAs(importUser())
        ->post('/finance/import/preview', ['file' => UploadedFile::fake()->createWithContent('big.csv', $big . str_repeat('x', 6 * 1024 * 1024))])
        ->assertSessionHasErrors('file');
});

// ── Etapa (ProjectStage) ─────────────────────────────────────────────────────

it('etapa por nome resolve stage_id da Transaction', function () {
    $user = importUser();
    $project = Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => 'Proj com etapa',
        'status'    => 'active',
    ]);
    $stage = ProjectStage::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'title'      => 'Pre-producao',
        'order'      => 1,
        'status'     => 'pending',
    ]);

    $csv = "descricao,valor,data,tipo,categoria,projeto,etapa\n"
         . "Aluguel Etapa 1,1500,15/07/2026,despesa,,Proj com etapa,Pre-producao\n";

    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Aluguel Etapa 1')->first();
    expect($tx)->not->toBeNull();
    expect((int) $tx->project_id)->toBe($project->id);
    expect((int) $tx->stage_id)->toBe($stage->id);
});

it('etapa nao encontrada deixa stage_id null (fallback silencioso)', function () {
    $user = importUser();
    $project = Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => 'Proj X',
        'status'    => 'active',
    ]);

    $csv = "descricao,valor,data,tipo,categoria,projeto,etapa\n"
         . "Y,100,15/07/2026,despesa,,Proj X,Nao existe\n";

    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Y')->first();
    expect($tx)->not->toBeNull();
    expect((int) $tx->project_id)->toBe($project->id);
    expect($tx->stage_id)->toBeNull();
});

it('etapa e ignorada quando nao ha projeto resolvido', function () {
    $user = importUser();

    $csv = "descricao,valor,data,tipo,categoria,projeto,etapa\n"
         . "Z,100,15/07/2026,despesa,,,Etapa qualquer\n";

    $this->actingAs($user)->post('/finance/import/preview', ['file' => makeCsv($csv)]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Z')->first();
    expect($tx->project_id)->toBeNull();
    expect($tx->stage_id)->toBeNull();
});

it('stage_id override do dropdown forca stage em todas as linhas', function () {
    $user = importUser();
    $project = Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => 'Proj OV',
        'status'    => 'active',
    ]);
    $stageA = ProjectStage::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'title'      => 'A',
        'order'      => 1,
        'status'     => 'pending',
    ]);

    // CSV nem menciona etapa. Override forca stageA em ambas.
    $csv = "descricao,valor,data,tipo\nX,10,01/07/2026,despesa\nY,20,02/07/2026,despesa\n";

    $this->actingAs($user)->post('/finance/import/preview', [
        'file'       => makeCsv($csv),
        'project_id' => $project->id,
        'stage_id'   => $stageA->id,
    ]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $txs = Transaction::withoutGlobalScope('tenant')->whereIn('description', ['X','Y'])->get();
    expect($txs)->toHaveCount(2);
    foreach ($txs as $tx) {
        expect((int) $tx->stage_id)->toBe($stageA->id);
    }
});

it('stage_id de outro projeto e rejeitado (anti-IDOR)', function () {
    $user = importUser();
    $projA = Project::factory()->create(['tenant_id' => $user->tenant_id, 'name' => 'A', 'status' => 'active']);
    $projB = Project::factory()->create(['tenant_id' => $user->tenant_id, 'name' => 'B', 'status' => 'active']);
    $stageB = ProjectStage::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $projB->id,
        'title'      => 'Stage B',
        'order'      => 1,
        'status'     => 'pending',
    ]);

    // Selecionou projA no dropdown + stageB no dropdown → stage rejeitada.
    $csv = "descricao,valor,data,tipo\nX,10,01/07/2026,despesa\n";
    $this->actingAs($user)->post('/finance/import/preview', [
        'file'       => makeCsv($csv),
        'project_id' => $projA->id,
        'stage_id'   => $stageB->id,
    ]);
    $this->actingAs($user)->post('/finance/import/confirm');

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'X')->first();
    expect((int) $tx->project_id)->toBe($projA->id);
    expect($tx->stage_id)->toBeNull(); // stage foi rejeitada
});
