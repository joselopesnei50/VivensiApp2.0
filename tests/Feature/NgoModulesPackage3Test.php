<?php

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectClassEnrollment;
use App\Models\ProjectPerson;
use App\Models\RegraAvaliacao;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComplianceCalculationService;

/**
 * Análise dos módulos da ONG (06/11/2026) — pacote 3: Turmas (vagas, volta
 * de aluno, período das chamadas, tela) e Conformidade por área de atuação.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p3Org(array $tenant = []): User
{
    $t = Tenant::factory()->create(array_merge(['subscription_status' => 'active', 'type' => 'ngo'], $tenant));
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

function p3Turma(User $u, array $class = [], int $pessoas = 5): array
{
    $p = Project::create(['tenant_id' => $u->tenant_id, 'name' => 'Música', 'budget' => 1, 'start_date' => '2026-01-01', 'status' => 'active']);
    $c = ProjectClass::create(array_merge(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => 'Violão', 'status' => 'ativo'], $class));
    $ids = collect(range(1, $pessoas))->map(fn ($i) => ProjectPerson::create([
        'tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'name' => "Aluno {$i}", 'enrollment_status' => 'ativo',
    ])->id)->all();
    return [$p, $c, $ids];
}

// ── Vagas ─────────────────────────────────────────────────────────────────

it('matrícula respeita o limite de vagas e avisa quem ficou de fora', function () {
    $u = p3Org();
    [$p, $c, $ids] = p3Turma($u, ['max_students' => 2]);

    $this->actingAs($u)->post("/projects/{$p->id}/classes/{$c->id}/enrollments/bulk", ['person_ids' => $ids])
        ->assertSessionHas('warning', fn ($m) => str_contains($m, '2 aluno(s) matriculado(s)') && str_contains($m, '3 ficaram de fora'));

    expect($c->activeEnrollments()->count())->toBe(2);
});

it('turma sem limite aceita todos', function () {
    $u = p3Org();
    [$p, $c, $ids] = p3Turma($u);

    $this->actingAs($u)->post("/projects/{$p->id}/classes/{$c->id}/enrollments/bulk", ['person_ids' => $ids])
        ->assertSessionHas('success', '5 aluno(s) matriculado(s).');
});

// ── Volta de quem saiu ────────────────────────────────────────────────────

it('aluno que saiu aparece de novo e pode ser matriculado', function () {
    $u = p3Org();
    [$p, $c, $ids] = p3Turma($u, [], 1);
    $e = ProjectClassEnrollment::create(['tenant_id' => $u->tenant_id, 'project_class_id' => $c->id, 'project_person_id' => $ids[0], 'enrolled_at' => now()->subMonth(), 'unenrolled_at' => now()->subWeek(), 'status' => 'saiu']);

    $this->actingAs($u)->get("/projects/{$p->id}/classes/{$c->id}")
        ->assertOk()->assertSee('já foi da turma')->assertSee('Saíram ou concluíram (1)');

    $this->actingAs($u)->post("/projects/{$p->id}/classes/{$c->id}/enrollments/bulk", ['person_ids' => [$ids[0]]])
        ->assertSessionHas('success', '1 aluno(s) matriculado(s).');

    $e->refresh();
    expect($e->status)->toBe('ativo')->and($e->unenrolled_at)->toBeNull();
    expect(ProjectClassEnrollment::count())->toBe(1);
});

// ── Gerar chamadas ────────────────────────────────────────────────────────

it('gerar chamadas respeita as datas da turma', function () {
    $u = p3Org();
    [$p, $c] = p3Turma($u, ['weekdays' => [1], 'start_date' => '2026-03-02', 'end_date' => '2026-03-31']);

    $this->actingAs($u)->post("/projects/{$p->id}/classes/{$c->id}/generate-sessions", ['from' => '2026-01-01', 'to' => '2026-12-31']);

    $datas = ClassSession::where('project_class_id', $c->id)->orderBy('date')->pluck('date')->map->format('Y-m-d')->all();
    expect($datas)->toBe(['2026-03-02', '2026-03-09', '2026-03-16', '2026-03-23', '2026-03-30']);
});

it('gerar chamadas limita o período a 1 ano', function () {
    $u = p3Org();
    [$p, $c] = p3Turma($u, ['weekdays' => [1, 3, 5]]);

    $this->actingAs($u)->post("/projects/{$p->id}/classes/{$c->id}/generate-sessions", ['from' => '2020-01-01', 'to' => '2099-12-31'])
        ->assertSessionHas('warning', 'Gere no máximo 1 ano de chamadas por vez.');
    expect(ClassSession::count())->toBe(0);
});

// ── Tela da turma ─────────────────────────────────────────────────────────

it('tela da turma separa próximas e anteriores e mostra a chamada feita', function () {
    $u = p3Org();
    [$p, $c, $ids] = p3Turma($u, ['max_students' => 8], 2);
    foreach ($ids as $id) {
        ProjectClassEnrollment::create(['tenant_id' => $u->tenant_id, 'project_class_id' => $c->id, 'project_person_id' => $id, 'enrolled_at' => now(), 'status' => 'ativo']);
    }
    $passada = ClassSession::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'project_class_id' => $c->id, 'title' => 'V', 'date' => now()->subDays(2)->toDateString(), 'mode' => 'fechada']);
    ClassSession::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'project_class_id' => $c->id, 'title' => 'V', 'date' => now()->subDays(9)->toDateString(), 'mode' => 'fechada']);
    ClassSession::create(['tenant_id' => $u->tenant_id, 'project_id' => $p->id, 'project_class_id' => $c->id, 'title' => 'V', 'date' => now()->addDays(5)->toDateString(), 'mode' => 'fechada']);
    ClassAttendance::create(['tenant_id' => $u->tenant_id, 'class_session_id' => $passada->id, 'project_person_id' => $ids[0], 'status' => 'presente', 'checked_in_via' => 'professor', 'checked_in_at' => now()]);
    ClassAttendance::create(['tenant_id' => $u->tenant_id, 'class_session_id' => $passada->id, 'project_person_id' => $ids[1], 'status' => 'falta', 'checked_in_via' => 'professor', 'checked_in_at' => now()]);

    $this->actingAs($u)->get("/projects/{$p->id}/classes/{$c->id}")
        ->assertOk()
        ->assertSee('2 de 8 vagas')
        ->assertSeeInOrder(['Próximas', 'Anteriores', '1 de 2 presentes', 'chamada pendente'])
        ->assertDontSee('Ctrl/Cmd');
});

// ── Conformidade por área de atuação ──────────────────────────────────────

it('área de atuação define os eixos do índice', function () {
    $this->seed(\Database\Seeders\RequisitoLegalSeeder::class);
    $this->seed(\Database\Seeders\RegraAvaliacaoSeeder::class);
    $svc = app(ComplianceCalculationService::class);

    $as = p3Org(['area_atuacao_cebas' => 'assistencia_social']);
    $d = $svc->calcularDashboard($as->tenant_id);
    expect(array_keys($d['indices_por_eixo']))->toBe(['cebas_geral', 'cebas_as', 'mrosc', 'suas'])
        ->and($d['area_configurada'])->toBeTrue()
        ->and(collect($d['pendencias'])->pluck('eixo')->unique()->diff(['cebas_geral', 'cebas_as', 'mrosc', 'suas'])->all())->toBe([]);

    $saude = p3Org(['area_atuacao_cebas' => 'saude']);
    expect(array_keys($svc->calcularDashboard($saude->tenant_id)['indices_por_eixo']))->toBe(['cebas_geral', 'cebas_saude', 'mrosc']);

    $semArea = p3Org();
    $d = $svc->calcularDashboard($semArea->tenant_id);
    expect(count($d['indices_por_eixo']))->toBe(6)->and($d['area_configurada'])->toBeFalse();
});

it('painel da conformidade pede a área quando não configurada e esconde eixos que não se aplicam', function () {
    $this->seed(\Database\Seeders\RequisitoLegalSeeder::class);
    $this->seed(\Database\Seeders\RegraAvaliacaoSeeder::class);

    $semArea = p3Org();
    $this->actingAs($semArea)->get('/ngo/conformidade')->assertOk()->assertSee('Informe a área de atuação');

    $as = p3Org(['area_atuacao_cebas' => 'assistencia_social']);
    $this->actingAs($as)->get('/ngo/conformidade')
        ->assertOk()
        ->assertDontSee('Informe a área de atuação')
        ->assertSee('CEBAS Assist. Social')
        ->assertDontSee('CEBAS Saúde</span>', false);
});

it('unidade aparece legível', function () {
    expect(RegraAvaliacao::formatar('24.32', '%_meses'))->toBe('24,3% dos meses')
        ->and(RegraAvaliacao::formatar(100, '%_meses'))->toBe('100% dos meses')
        ->and(RegraAvaliacao::formatar(1, 'registros'))->toBe('1 registro')
        ->and(RegraAvaliacao::formatar(20, '%'))->toBe('20%')
        ->and(RegraAvaliacao::formatar(null, '%'))->toBe('—');
});
