<?php

use App\Models\FinancialCategory;
use App\Models\Project;
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

it('confirma import cria transacoes no banco', function () {
    $user = importUser();
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
    expect($salario->status)->toBe('pending');
    expect((int) $salario->tenant_id)->toBe($user->tenant_id);
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
