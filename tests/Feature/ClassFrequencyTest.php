<?php

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectClassEnrollment;
use App\Models\ProjectPerson;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AttendanceFrequencyService;

/**
 * Frequência por aluno nas turmas: % de presença, risco de evasão, média por
 * turma e relatório filtrado por turma (07/11/2026).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Turma com 3 alunos e 5 chamadas passadas:
 *  - Ana: 5 presenças (100%)
 *  - Bia: P P F F F → 3 faltas seguidas (em risco)
 *  - Caio: P F P J P → 75%, sem risco (justificada não conta)
 */
function frSetup(): array
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $p = Project::create(['tenant_id' => $t->id, 'name' => 'Música', 'budget' => 1, 'start_date' => '2026-01-01', 'status' => 'active']);
    $c = ProjectClass::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'name' => 'Violão', 'status' => 'ativo']);
    $outra = ProjectClass::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'name' => 'Canto', 'status' => 'ativo']);

    $alunos = [];
    foreach (['Ana', 'Bia', 'Caio'] as $nome) {
        $pp = ProjectPerson::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'name' => $nome, 'enrollment_status' => 'ativo']);
        ProjectClassEnrollment::create(['tenant_id' => $t->id, 'project_class_id' => $c->id, 'project_person_id' => $pp->id, 'enrolled_at' => now()->subMonth(), 'status' => 'ativo']);
        $alunos[$nome] = $pp;
    }

    $grade = ['Ana' => 'PPPPP', 'Bia' => 'PPFFF', 'Caio' => 'PFPJP'];
    $map = ['P' => 'presente', 'F' => 'falta', 'J' => 'falta_justificada'];
    foreach (range(0, 4) as $i) {
        $s = ClassSession::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'project_class_id' => $c->id, 'title' => 'Violão', 'date' => now()->subDays(20 - $i * 4)->toDateString(), 'mode' => 'fechada']);
        foreach ($grade as $nome => $seq) {
            ClassAttendance::create(['tenant_id' => $t->id, 'class_session_id' => $s->id, 'project_person_id' => $alunos[$nome]->id, 'status' => $map[$seq[$i]], 'checked_in_via' => 'professor', 'checked_in_at' => now()]);
        }
    }

    // Chamada de OUTRA turma: não pode entrar na frequência da turma Violão
    $s2 = ClassSession::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'project_class_id' => $outra->id, 'title' => 'Canto', 'date' => now()->subDays(2)->toDateString(), 'mode' => 'fechada']);
    ClassAttendance::create(['tenant_id' => $t->id, 'class_session_id' => $s2->id, 'project_person_id' => $alunos['Ana']->id, 'status' => 'falta', 'checked_in_via' => 'professor', 'checked_in_at' => now()]);

    return compact('t', 'u', 'p', 'c', 'outra', 'alunos');
}

it('calcula % de presença e risco por aluno da turma', function () {
    ['c' => $c, 'alunos' => $a] = frSetup();
    $f = app(AttendanceFrequencyService::class)->forClass($c);

    expect($f[$a['Ana']->id]['pct'])->toBe(100.0)
        ->and($f[$a['Ana']->id]['at_risk'])->toBeFalse();
    expect($f[$a['Bia']->id]['pct'])->toBe(40.0)
        ->and($f[$a['Bia']->id]['consecutive'])->toBe(3)
        ->and($f[$a['Bia']->id]['at_risk'])->toBeTrue();
    expect($f[$a['Caio']->id]['pct'])->toBe(75.0)
        ->and($f[$a['Caio']->id]['justified'])->toBe(1)
        ->and($f[$a['Caio']->id]['at_risk'])->toBeFalse();
});

it('média por turma conta só presença e falta', function () {
    ['t' => $t, 'c' => $c, 'outra' => $outra] = frSetup();
    $m = app(AttendanceFrequencyService::class)->averageByClass($t->id, [$c->id, $outra->id]);

    // Violão: presentes 5+2+3=10, faltas 0+3+1=4 → 10/14
    expect(round($m[$c->id], 1))->toBe(71.4)
        ->and($m[$outra->id])->toBe(0.0);
});

it('tela da turma mostra a frequência e o alerta de risco', function () {
    ['u' => $u, 'p' => $p, 'c' => $c] = frSetup();

    $this->actingAs($u)->get("/projects/{$p->id}/classes/{$c->id}")
        ->assertOk()
        ->assertSee('1 aluno em risco')
        ->assertSee('40% presença')
        ->assertSee('3 faltas seguidas')
        ->assertSee('100% presença')
        ->assertSee('Relatório de frequência');
});

it('lista de turmas mostra a presença média', function () {
    ['u' => $u, 'p' => $p] = frSetup();
    $this->actingAs($u)->get("/projects/{$p->id}/classes")->assertOk()->assertSee('Presença média')->assertSee('71%');
});

it('relatório filtra por turma (tela e CSV)', function () {
    ['u' => $u, 'p' => $p, 'c' => $c, 'outra' => $outra] = frSetup();
    $q = '?from=' . now()->subMonth()->toDateString() . '&to=' . now()->toDateString();

    $this->actingAs($u)->get("/projects/{$p->id}/attendance/report{$q}&turma={$c->id}")
        ->assertOk()->assertSee('Turma Violão')->assertSee('Bia');

    // Na turma Canto só a Ana tem registro
    $this->actingAs($u)->get("/projects/{$p->id}/attendance/report{$q}&turma={$outra->id}")
        ->assertOk()->assertSee('Ana')->assertDontSee('Caio');

    $csv = $this->actingAs($u)->get("/projects/{$p->id}/attendance/report.csv{$q}&turma={$c->id}")->streamedContent();
    expect($csv)->toContain('"# Turma",Violão')->toContain('Bia')->toContain('SIM');
});

it('professor (perfil comum) abre o relatório; turma de outro projeto é ignorada', function () {
    ['t' => $t, 'p' => $p, 'c' => $c] = frSetup();
    $prof = User::factory()->create(['tenant_id' => $t->id, 'role' => 'common']);
    $p2 = Project::create(['tenant_id' => $t->id, 'name' => 'Outro', 'budget' => 1, 'start_date' => '2026-01-01', 'status' => 'active']);

    $this->actingAs($prof)->get("/projects/{$p->id}/attendance/report")->assertOk();
    // turma de outro projeto no filtro: cai para "todas as chamadas"
    $this->actingAs($prof)->get("/projects/{$p2->id}/attendance/report?turma={$c->id}")->assertOk()->assertDontSee('Turma Violão');
});
