<?php

use App\Models\Attachment;
use App\Models\Beneficiary;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectClassEnrollment;
use App\Models\ProjectPerson;
use App\Models\PublicPartnership;
use App\Models\Tenant;
use App\Models\TransparencyPortal;
use App\Models\User;
use App\Support\BrMoney;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Análise dos módulos da ONG (06/11/2026) — pacote 1: valores ×100, chamada
 * da turma, reimportação de planilha, download da conformidade, portal.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p1Org(string $role = 'ngo', array $tenant = []): User
{
    $t = Tenant::factory()->create(array_merge(['subscription_status' => 'active', 'type' => 'ngo'], $tenant));
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

function p1Project(User $u): Project
{
    return Project::create(['tenant_id' => $u->tenant_id, 'name' => 'Música', 'budget' => 15000, 'start_date' => '2026-01-01', 'status' => 'active']);
}

// ── Valores em reais ──────────────────────────────────────────────────────

it('lê valores nos dois formatos sem multiplicar por 100', function () {
    expect(BrMoney::parse('15000.00'))->toBe('15000.00')
        ->and(BrMoney::parse('48000.5'))->toBe('48000.5')
        ->and(BrMoney::parse('1.500,50'))->toBe('1500.50')
        ->and(BrMoney::parse('1500,5'))->toBe('1500.5')
        ->and(BrMoney::parse('1.500'))->toBe('1500')
        ->and(BrMoney::parse('1.500.000'))->toBe('1500000')
        ->and(BrMoney::parse('R$ 2.000,00'))->toBe('2000.00')
        ->and(BrMoney::parse('300'))->toBe('300')
        ->and(BrMoney::parse(''))->toBe('');
});

it('salvar a edição do projeto mantém o orçamento', function () {
    $u = p1Org();
    $p = p1Project($u);

    // A tela de edição envia o valor como vem do banco: "15000.00"
    $this->actingAs($u)->put('/projects/' . $p->id, [
        'name' => 'Música', 'budget' => (string) $p->fresh()->budget, 'start_date' => '2026-01-01', 'status' => 'active',
    ])->assertSessionHasNoErrors();
    expect((float) $p->fresh()->budget)->toBe(15000.0);

    // E o formato brasileiro da tela de criação continua funcionando
    $this->actingAs($u)->put('/projects/' . $p->id, [
        'name' => 'Música', 'budget' => '22.500,75', 'start_date' => '2026-01-01', 'status' => 'active',
    ]);
    expect((float) $p->fresh()->budget)->toBe(22500.75);
});

it('parceria com centavos é gravada com o valor certo', function () {
    $u = p1Org();
    $this->actingAs($u)->post('/ngo/transparencia/partnerships', [
        'agency_name' => 'Prefeitura', 'project_name' => 'X', 'value' => '48000.50', 'status' => 'active',
    ])->assertSessionHasNoErrors();
    expect((float) PublicPartnership::first()->value)->toBe(48000.5);

    $part = PublicPartnership::first();
    $this->actingAs($u)->put('/ngo/transparencia/partnerships/' . $part->id, [
        'agency_name' => 'Prefeitura', 'project_name' => 'X', 'value' => '48.000,50', 'status' => 'active',
    ]);
    expect((float) $part->fresh()->value)->toBe(48000.5);
});

it('link do Diário Oficial só aceita http/https', function () {
    $u = p1Org();
    $base = ['agency_name' => 'Prefeitura', 'project_name' => 'X', 'value' => '100', 'status' => 'active'];

    $this->actingAs($u)->post('/ngo/transparencia/partnerships', $base + ['gazette_link' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('gazette_link');
    session()->forget('errors');
    $this->actingAs($u)->post('/ngo/transparencia/partnerships', $base + ['gazette_link' => 'https://doe.sp.gov.br/x'])
        ->assertSessionHasNoErrors();

    expect(PublicPartnership::pluck('gazette_link')->all())->toBe(['https://doe.sp.gov.br/x']);
});

// ── Chamada da turma ──────────────────────────────────────────────────────

function p1Turma(User $u): array
{
    $p = p1Project($u);
    $c = ProjectClass::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => 'Violão', 'status' => 'ativo']);
    $aluno = ProjectPerson::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => 'Aluno Matriculado', 'enrollment_status' => 'ativo']);
    $saiu = ProjectPerson::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => 'Aluno Que Saiu', 'enrollment_status' => 'ativo']);
    $outro = ProjectPerson::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => 'Outro Do Projeto', 'enrollment_status' => 'ativo']);
    ProjectClassEnrollment::create(['tenant_id' => $u->tenant_id, 'project_class_id' => $c->id, 'project_person_id' => $aluno->id, 'enrolled_at' => now(), 'status' => 'ativo']);
    ProjectClassEnrollment::create(['tenant_id' => $u->tenant_id, 'project_class_id' => $c->id, 'project_person_id' => $saiu->id, 'enrolled_at' => now(), 'status' => 'saiu']);
    $s = ClassSession::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'project_class_id' => $c->id, 'title' => 'Violão', 'date' => now()->toDateString(), 'mode' => 'fechada']);

    return compact('p', 'c', 'aluno', 'saiu', 'outro', 's');
}

it('chamada da turma lista só os matriculados ativos', function () {
    $u = p1Org();
    ['p' => $p, 's' => $s] = p1Turma($u);

    $this->actingAs($u)->get("/projects/{$p->id}/class-sessions/{$s->id}")
        ->assertOk()
        ->assertSee('Aluno Matriculado')
        ->assertDontSee('Outro Do Projeto')
        ->assertDontSee('Aluno Que Saiu');
});

it('salvar a chamada ignora quem não é da turma', function () {
    $u = p1Org();
    ['p' => $p, 's' => $s, 'aluno' => $aluno, 'outro' => $outro] = p1Turma($u);

    $this->actingAs($u)->post("/projects/{$p->id}/class-sessions/{$s->id}/attendance", ['attendance' => [
        $aluno->id => ['status' => 'presente'],
        $outro->id => ['status' => 'presente'],
    ]])->assertRedirect();

    expect(ClassAttendance::pluck('project_person_id')->all())->toBe([$aluno->id]);
});

it('chamada avulsa (sem turma) continua com todos do projeto', function () {
    $u = p1Org();
    ['p' => $p] = p1Turma($u);
    $avulsa = ClassSession::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'title' => 'Evento', 'date' => now()->toDateString(), 'mode' => 'fechada']);

    $this->actingAs($u)->get("/projects/{$p->id}/class-sessions/{$avulsa->id}")
        ->assertOk()->assertSee('Aluno Matriculado')->assertSee('Outro Do Projeto');
});

it('presença registrada antes continua aparecendo e editável', function () {
    $u = p1Org();
    ['p' => $p, 's' => $s, 'outro' => $outro] = p1Turma($u);
    ClassAttendance::create(['tenant_id' => $u->tenant_id, 'class_session_id' => $s->id, 'project_person_id' => $outro->id, 'status' => 'presente', 'checked_in_via' => 'professor', 'checked_in_at' => now()]);

    $this->actingAs($u)->get("/projects/{$p->id}/class-sessions/{$s->id}")->assertSee('Outro Do Projeto');
    $this->actingAs($u)->post("/projects/{$p->id}/class-sessions/{$s->id}/attendance", ['attendance' => [
        $outro->id => ['status' => 'falta'],
    ]]);
    expect(ClassAttendance::first()->status)->toBe('falta');
});

it('link público da chamada só aceita e mostra matriculados da turma', function () {
    $u = p1Org();
    ['s' => $s] = p1Turma($u);
    $s->public_token = 'tok-' . str_repeat('a', 40);
    $s->public_enabled_until = now()->endOfDay();
    $s->save();
    $url = '/chamada/' . 'tok-' . str_repeat('a', 40);

    $page = $this->get($url);
    $page->assertOk()->assertSee('Aluno Matriculado')->assertDontSee('Outro Do Projeto');

    $this->post($url . '/checkin', ['name' => 'Outro Do Projeto', 'consent' => '1']);
    expect(ClassAttendance::count())->toBe(0);

    $this->post($url . '/checkin', ['name' => 'aluno matriculado', 'consent' => '1']);
    expect(ClassAttendance::count())->toBe(1);
});

// ── Reimportação de planilha ──────────────────────────────────────────────

function p1Csv(string $content): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($tmp, $content);
    return new UploadedFile($tmp, 'familias.csv', 'text/csv', null, true);
}

it('reimportar planilha com colunas vazias não apaga dados nem reativa', function () {
    $u = p1Org();
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Maria', 'cpf' => '12345678909', 'phone' => '(16) 99999-0000',
        'address_street' => 'Rua A', 'address_number' => '10', 'address_city' => 'Araraquara', 'address_state' => 'SP',
        'address' => 'Rua A, 10, Araraquara - SP, Brasil', 'status' => 'inactive']);

    $this->actingAs($u)->post('/ngo/beneficiaries/import', ['file' => p1Csv("Nome,CPF,Telefone,Status,Cidade\nMaria,123.456.789-09,,,\n")]);

    $b->refresh();
    expect($b->phone)->toBe('(16) 99999-0000')
        ->and($b->status)->toBe('inactive')
        ->and($b->address_city)->toBe('Araraquara')
        ->and($b->address)->toBe('Rua A, 10, Araraquara - SP, Brasil');
});

it('reimportar atualiza o que veio preenchido e recompõe o endereço', function () {
    $u = p1Org();
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Maria', 'cpf' => '12345678909', 'phone' => '(16) 99999-0000',
        'address_street' => 'Rua A', 'address_number' => '10', 'address_city' => 'Araraquara', 'address_state' => 'SP', 'status' => 'active']);

    $this->actingAs($u)->post('/ngo/beneficiaries/import', ['file' => p1Csv("Nome,CPF,Telefone,Bairro,Status\nMaria,12345678909,(16) 98888-1111,Centro,inativo\n")]);

    $b->refresh();
    expect($b->phone)->toBe('(16) 98888-1111')
        ->and($b->status)->toBe('inactive')
        ->and($b->address_neighborhood)->toBe('Centro')
        ->and($b->address)->toBe('Rua A, 10, Centro, Araraquara - SP, Brasil');
});

it('importação de cadastro novo continua igual', function () {
    $u = p1Org();
    $this->actingAs($u)->post('/ngo/beneficiaries/import', ['file' => p1Csv("Nome,CPF,Telefone,Status\nJoão,98765432100,(16) 97777-0000,\n")]);

    $b = Beneficiary::where('name', 'João')->first();
    expect($b)->not->toBeNull()->and($b->status)->toBe('active')->and($b->phone)->toBe('(16) 97777-0000');
});

// ── Download da conformidade ──────────────────────────────────────────────

function p1Attachment(User $u, string $type, int $id, ?string $tipo = null): Attachment
{
    Storage::disk('local')->put("private/t/{$type}{$id}.pdf", '%PDF-1.4');
    return Attachment::withoutGlobalScopes()->create([
        'tenant_id' => $u->tenant_id, 'attachable_type' => $type, 'attachable_id' => $id,
        'original_name' => 'doc.pdf', 'path' => "private/t/{$type}{$id}.pdf", 'mime_type' => 'application/pdf',
        'size_bytes' => 8, 'uploaded_by' => $u->id, 'tipo_documento' => $tipo,
    ]);
}

it('download da conformidade: só gestores e só documentos da conformidade', function () {
    Storage::fake('local');
    $this->seed(\Database\Seeders\RequisitoLegalSeeder::class);
    $this->seed(\Database\Seeders\RegraAvaliacaoSeeder::class);
    $u = p1Org();
    $emp = User::factory()->create(['tenant_id' => $u->tenant_id, 'role' => 'employee']);
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'B']);

    $estatuto = p1Attachment($u, Tenant::class, $u->tenant_id, 'estatuto');
    $laudo = p1Attachment($u, Beneficiary::class, $b->id, 'laudo_medico');

    // Funcionário: bloqueado
    $this->actingAs($emp)->get("/ngo/conformidade/download/{$estatuto->id}")->assertForbidden();

    // Gestor: documento da conformidade sim, anexo de beneficiário não
    $ok = $this->actingAs($u)->get("/ngo/conformidade/download/{$estatuto->id}");
    expect($ok->baseResponse->getStatusCode())->toBe(200);
    $this->actingAs($u)->get("/ngo/conformidade/download/{$laudo->id}")->assertNotFound();
});

// ── Portal público ────────────────────────────────────────────────────────

function p1SqliteMysqlDates(): void
{
    // O portal usa MONTH()/YEAR() (MySQL); no SQLite dos testes, emula.
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    if (method_exists($pdo, 'sqliteCreateFunction')) {
        $pdo->sqliteCreateFunction('MONTH', fn ($d) => $d ? (int) date('n', strtotime($d)) : null, 1);
        $pdo->sqliteCreateFunction('YEAR', fn ($d) => $d ? (int) date('Y', strtotime($d)) : null, 1);
    }
}

it('portal mostra o nome da organização e não mostra e-mail de terceiros', function () {
    p1SqliteMysqlDates();
    $u = p1Org('ngo', ['name' => 'Instituto Girassol', 'brand_name' => null]);
    TransparencyPortal::create(['tenant_id' => $u->tenant_id, 'slug' => 'girassol', 'title' => 'Portal da Transparência', 'is_published' => true]);

    $this->get('/transparencia/girassol')
        ->assertOk()
        ->assertSee('Instituto Girassol')
        ->assertDontSee('transparencia@vivensi.org');

    TransparencyPortal::withoutGlobalScopes()->where('slug', 'girassol')->update(['sic_email' => 'sic@girassol.org']);
    \Illuminate\Support\Facades\Cache::flush();
    $this->get('/transparencia/girassol')->assertSee('sic@girassol.org');
});

it('endereço do portal novo usa o nome da organização', function () {
    $u = p1Org('ngo', ['name' => 'Instituto Girassol']);
    $u->update(['name' => 'Maria Coordenadora']);

    $this->actingAs($u)->get('/ngo/transparencia')->assertOk();

    expect(TransparencyPortal::withoutGlobalScopes()->where('tenant_id', $u->tenant_id)->value('slug'))
        ->toBe('instituto-girassol-' . $u->tenant_id);
});
