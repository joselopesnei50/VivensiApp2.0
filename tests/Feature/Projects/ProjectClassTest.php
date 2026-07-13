<?php

use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectClassEnrollment;
use App\Models\ProjectPerson;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Turmas (ProjectClass) — CRUD + matricula + geracao de sessoes + isolamento.
 */

uses(RefreshDatabase::class);

function ngoUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'ngo',
    ]);
}

function createProject(User $user): Project
{
    return Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => 'Musica na Escola',
    ]);
}

function createPerson(Project $project): ProjectPerson
{
    return ProjectPerson::create([
        'tenant_id'         => $project->tenant_id,
        'project_id'        => $project->id,
        'name'              => fake()->name(),
        'enrollment_status' => 'ativo',
    ]);
}

// ── Listagem e criacao ───────────────────────────────────────────────────────

it('exige autenticacao para listar turmas', function () {
    $tenant  = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $this->get("/projects/{$project->id}/classes")->assertRedirect('/login');
});

it('lista turmas do projeto (vazio + com dados)', function () {
    $user    = ngoUser();
    $project = createProject($user);

    $this->actingAs($user)
        ->get("/projects/{$project->id}/classes")
        ->assertOk()
        ->assertSee('Turmas')
        ->assertSee('Nenhuma turma cadastrada');

    ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Violao Iniciante',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}/classes")
        ->assertOk()
        ->assertSee('Violao Iniciante');
});

it('cria turma nova via POST', function () {
    $user    = ngoUser();
    $project = createProject($user);

    $response = $this->actingAs($user)
        ->post("/projects/{$project->id}/classes", [
            'name'               => 'Capoeira Iniciante',
            'default_mode'       => 'fechada',
            'default_start_time' => '19:00',
            'default_end_time'   => '20:30',
            'weekdays'           => [1, 3],
        ]);

    $response->assertRedirect();

    $class = ProjectClass::withoutGlobalScope('tenant')->first();
    expect($class)->not->toBeNull();
    expect($class->name)->toBe('Capoeira Iniciante');
    expect($class->weekdays)->toBe([1, 3]);
    expect((int) $class->tenant_id)->toBe($user->tenant_id);
    expect((int) $class->project_id)->toBe($project->id);
});

it('valida campos obrigatorios ao criar turma', function () {
    $user    = ngoUser();
    $project = createProject($user);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes", [])
        ->assertSessionHasErrors('name');
});

it('valida end_time depois de start_time', function () {
    $user    = ngoUser();
    $project = createProject($user);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes", [
            'name'               => 'Aula',
            'default_start_time' => '20:00',
            'default_end_time'   => '19:00', // antes
        ])
        ->assertSessionHasErrors('default_end_time');
});

// ── Show / edit / delete ─────────────────────────────────────────────────────

it('renderiza tela da turma com matriculados e sessoes', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Violao Avancado',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}/classes/{$class->id}")
        ->assertOk()
        ->assertSee('Violao Avancado')
        ->assertSee('Alunos matriculados');
});

it('atualiza turma via PUT', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Nome antigo',
    ]);

    $this->actingAs($user)
        ->put("/projects/{$project->id}/classes/{$class->id}", [
            'name'         => 'Nome novo',
            'default_mode' => 'aberta',
        ])
        ->assertRedirect();

    expect($class->fresh()->name)->toBe('Nome novo');
    expect($class->fresh()->default_mode)->toBe('aberta');
});

it('deleta turma (chamada permanece — SGBD produção nullifica via FK)', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Vai ser deletada',
    ]);
    $session = ClassSession::create([
        'tenant_id'        => $user->tenant_id,
        'project_id'       => $project->id,
        'project_class_id' => $class->id,
        'title'            => 'Aula solta',
        'date'             => now(),
        'mode'             => 'fechada',
    ]);

    $this->actingAs($user)
        ->delete("/projects/{$project->id}/classes/{$class->id}")
        ->assertRedirect();

    // Turma foi excluída
    expect(ProjectClass::withoutGlobalScope('tenant')->find($class->id))->toBeNull();
    // Chamada continua no banco (nullOnDelete só se aplica com FK enforcement — MySQL/prod)
    expect(ClassSession::withoutGlobalScope('tenant')->find($session->id))->not->toBeNull();
});

// ── Matricula bulk ───────────────────────────────────────────────────────────

it('matricula multiplos alunos de uma vez', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Aula',
    ]);
    $p1 = createPerson($project);
    $p2 = createPerson($project);
    $p3 = createPerson($project);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/enrollments/bulk", [
            'person_ids' => [$p1->id, $p2->id, $p3->id],
        ])
        ->assertRedirect();

    expect($class->enrollments()->count())->toBe(3);
});

it('nao duplica matricula se aluno ja esta na turma', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Aula',
    ]);
    $p = createPerson($project);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/enrollments/bulk", ['person_ids' => [$p->id]])
        ->assertRedirect();

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/enrollments/bulk", ['person_ids' => [$p->id]])
        ->assertRedirect();

    expect($class->enrollments()->count())->toBe(1);
});

it('bloqueia matricular pessoa de outro projeto (IDOR)', function () {
    $userA    = ngoUser();
    $projectA = createProject($userA);
    $classA   = ProjectClass::create([
        'tenant_id'  => $userA->tenant_id,
        'project_id' => $projectA->id,
        'name'       => 'Turma A',
    ]);

    $userB    = ngoUser();
    $projectB = createProject($userB);
    $personB  = createPerson($projectB);

    $this->actingAs($userA)
        ->post("/projects/{$projectA->id}/classes/{$classA->id}/enrollments/bulk", [
            'person_ids' => [$personB->id],
        ]);

    // Ninguem foi matriculado (person de outro projeto foi silenciosamente ignorado)
    expect($classA->enrollments()->count())->toBe(0);
});

it('desmatricula aluno', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Aula',
    ]);
    $p = createPerson($project);

    $enrollment = ProjectClassEnrollment::create([
        'tenant_id'         => $user->tenant_id,
        'project_class_id'  => $class->id,
        'project_person_id' => $p->id,
        'enrolled_at'       => now(),
        'status'            => 'ativo',
    ]);

    $this->actingAs($user)
        ->delete("/projects/{$project->id}/classes/{$class->id}/enrollments/{$enrollment->id}")
        ->assertRedirect();

    $enrollment->refresh();
    expect($enrollment->status)->toBe('saiu');
    expect($enrollment->unenrolled_at)->not->toBeNull();
});

// ── Geracao de sessoes ──────────────────────────────────────────────────────

it('gera sessoes para as datas correspondentes aos weekdays', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'          => $user->tenant_id,
        'project_id'         => $project->id,
        'name'               => 'Aula segunda e quarta',
        'weekdays'           => [1, 3], // seg, qua
        'default_start_time' => '19:00',
        'default_end_time'   => '20:00',
        'default_mode'       => 'fechada',
    ]);

    // Semana de 14 (segunda) a 20 (domingo) de julho de 2026
    $response = $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/generate-sessions", [
            'from' => '2026-07-13', // segunda
            'to'   => '2026-07-19', // domingo
        ]);

    $response->assertRedirect();

    $sessions = ClassSession::withoutGlobalScope('tenant')
        ->where('project_class_id', $class->id)
        ->get();
    expect($sessions->count())->toBe(2); // segunda e quarta
});

it('geracao de sessoes e idempotente (nao duplica datas)', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Idem',
        'weekdays'   => [1],
    ]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/generate-sessions", [
            'from' => '2026-07-13',
            'to'   => '2026-07-13',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/generate-sessions", [
            'from' => '2026-07-13',
            'to'   => '2026-07-13',
        ])
        ->assertRedirect();

    expect(ClassSession::withoutGlobalScope('tenant')->where('project_class_id', $class->id)->count())->toBe(1);
});

it('avisa quando turma nao tem weekdays configurados', function () {
    $user    = ngoUser();
    $project = createProject($user);
    $class   = ProjectClass::create([
        'tenant_id'  => $user->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Sem dias',
        'weekdays'   => [],
    ]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/classes/{$class->id}/generate-sessions", [
            'from' => '2026-07-13',
            'to'   => '2026-07-30',
        ])
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect(ClassSession::where('project_class_id', $class->id)->count())->toBe(0);
});

// ── Isolamento cross-tenant ─────────────────────────────────────────────────

it('bloqueia acesso a turma de outro tenant (404)', function () {
    $userA    = ngoUser();
    $projectA = createProject($userA);
    $classA   = ProjectClass::create([
        'tenant_id'  => $userA->tenant_id,
        'project_id' => $projectA->id,
        'name'       => 'Do tenant A',
    ]);

    $userB    = ngoUser(); // tenant diferente

    $this->actingAs($userB)
        ->get("/projects/{$projectA->id}/classes/{$classA->id}")
        ->assertNotFound();
});
