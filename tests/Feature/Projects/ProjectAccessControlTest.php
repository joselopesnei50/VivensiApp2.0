<?php

use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectMember;
use App\Models\ProjectStage;
use App\Models\Tenant;
use App\Models\User;

/**
 * Auditoria 2026-08-01 — achados 1 e 2 do módulo de Projetos:
 * ProjectStageController e ProjectClassController não tinham checagem de
 * role/membership — qualquer user do tenant criava/excluía etapas (inclusive
 * complete() que gera Transaction de income) e turmas/matrículas.
 * Regra aplicada (padrão Planning): leitura = gestor OU ProjectMember;
 * escrita = manager/super_admin/ngo.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function pacTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function pacUser(Tenant $tenant, string $role): User
{
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

function pacProject(Tenant $tenant): Project
{
    return Project::factory()->create(['tenant_id' => $tenant->id]);
}

function pacStage(Project $project): ProjectStage
{
    return ProjectStage::create([
        'tenant_id'  => $project->tenant_id,
        'project_id' => $project->id,
        'title'      => 'Etapa 1',
        'order'      => 1,
        'status'     => 'pending',
    ]);
}

function pacClass(Project $project): ProjectClass
{
    return ProjectClass::create([
        'tenant_id'  => $project->tenant_id,
        'project_id' => $project->id,
        'name'       => 'Turma A',
    ]);
}

// ── Stages ────────────────────────────────────────────────────────────────────

it('employee nao-membro recebe 403 nos endpoints de stages', function (string $method, string $uri) {
    $tenant   = pacTenant();
    $employee = pacUser($tenant, 'employee');
    $project  = pacProject($tenant);
    $stage    = pacStage($project);

    $uri = str_replace(['{project}', '{stage}'], [(string) $project->id, (string) $stage->id], $uri);

    $this->actingAs($employee)->json($method, $uri)->assertStatus(403);
})->with([
    ['GET',    '/projects/{project}/stages'],
    ['POST',   '/projects/{project}/stages'],
    ['GET',    '/projects/{project}/stages/{stage}'],
    ['PUT',    '/projects/{project}/stages/{stage}'],
    ['DELETE', '/projects/{project}/stages/{stage}'],
    ['POST',   '/projects/{project}/stages/{stage}/complete'],
    ['POST',   '/projects/{project}/stages/reorder'],
]);

it('employee membro le stages mas nao escreve', function () {
    $tenant   = pacTenant();
    $employee = pacUser($tenant, 'employee');
    $project  = pacProject($tenant);
    pacStage($project);

    ProjectMember::create([
        'tenant_id'    => $tenant->id,
        'project_id'   => $project->id,
        'user_id'      => $employee->id,
        'access_level' => 'viewer',
    ]);

    $this->actingAs($employee)->getJson("/projects/{$project->id}/stages")->assertOk();

    $this->actingAs($employee)->postJson("/projects/{$project->id}/stages", [
        'title' => 'Etapa intrusa',
    ])->assertStatus(403);
});

it('ngo continua com CRUD de stages', function () {
    $tenant  = pacTenant();
    $ngo     = pacUser($tenant, 'ngo');
    $project = pacProject($tenant);

    $this->actingAs($ngo)->postJson("/projects/{$project->id}/stages", [
        'title' => 'Etapa nova',
    ])->assertStatus(201);
});

// ── Turmas ────────────────────────────────────────────────────────────────────

it('employee nao-membro recebe 403 nos endpoints de turmas', function (string $method, string $uri) {
    $tenant   = pacTenant();
    $employee = pacUser($tenant, 'employee');
    $project  = pacProject($tenant);
    $class    = pacClass($project);

    $uri = str_replace(['{project}', '{class}'], [(string) $project->id, (string) $class->id], $uri);

    $this->actingAs($employee)->json($method, $uri)->assertStatus(403);
})->with([
    ['GET',    '/projects/{project}/classes'],
    ['GET',    '/projects/{project}/classes/create'],
    ['POST',   '/projects/{project}/classes'],
    ['PUT',    '/projects/{project}/classes/{class}'],
    ['DELETE', '/projects/{project}/classes/{class}'],
    ['POST',   '/projects/{project}/classes/{class}/enrollments/bulk'],
    ['POST',   '/projects/{project}/classes/{class}/generate-sessions'],
]);

it('employee membro le turmas mas nao escreve', function () {
    $tenant   = pacTenant();
    $employee = pacUser($tenant, 'employee');
    $project  = pacProject($tenant);
    pacClass($project);

    ProjectMember::create([
        'tenant_id'    => $tenant->id,
        'project_id'   => $project->id,
        'user_id'      => $employee->id,
        'access_level' => 'viewer',
    ]);

    $this->actingAs($employee)->get("/projects/{$project->id}/classes")->assertOk();

    $this->actingAs($employee)->postJson("/projects/{$project->id}/classes", [
        'name' => 'Turma intrusa',
    ])->assertStatus(403);
});

it('ngo continua criando turmas', function () {
    $tenant  = pacTenant();
    $ngo     = pacUser($tenant, 'ngo');
    $project = pacProject($tenant);

    $this->actingAs($ngo)->post("/projects/{$project->id}/classes", [
        'name' => 'Turma B',
    ])->assertRedirect();

    expect(ProjectClass::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('rejeita professor de outro tenant em default_teacher_user_id', function () {
    $tenantA = pacTenant();
    $tenantB = pacTenant();
    $ngoA    = pacUser($tenantA, 'ngo');
    $ngoB    = pacUser($tenantB, 'ngo');
    $project = pacProject($tenantA);

    $resp = $this->actingAs($ngoA)->from("/projects/{$project->id}/classes/create")
        ->post("/projects/{$project->id}/classes", [
            'name'                    => 'Turma C',
            'default_teacher_user_id' => $ngoB->id,
        ]);

    $resp->assertSessionHasErrors('default_teacher_user_id');
    expect(ProjectClass::withoutGlobalScope('tenant')->where('tenant_id', $tenantA->id)->count())->toBe(0);
});
