<?php

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeTenantForCred(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function makeUserForCred(Tenant $tenant, string $role = 'credenciado', ?int $defaultProjectId = null): User
{
    return User::factory()->create([
        'tenant_id'          => $tenant->id,
        'role'               => $role,
        'default_project_id' => $defaultProjectId,
    ]);
}

function makeProjectForCred(Tenant $tenant, array $overrides = []): Project
{
    return Project::create(array_merge([
        'tenant_id'  => $tenant->id,
        'name'       => 'Projeto Teste',
        'status'     => 'active',
        'start_date' => now(),
        'end_date'   => now()->addMonths(3),
    ], $overrides));
}

function linkMemberCred(Tenant $tenant, User $user, Project $project, string $level = 'editor'): ProjectMember
{
    return ProjectMember::create([
        'tenant_id'    => $tenant->id,
        'project_id'   => $project->id,
        'user_id'      => $user->id,
        'access_level' => $level,
    ]);
}

// ── Middleware: bloqueio de rotas fora do escopo ─────────────────────────────

it('middleware bloqueia credenciado de acessar /dashboard', function () {
    $tenant = makeTenantForCred();
    $user   = makeUserForCred($tenant);
    $this->actingAs($user);

    $r = $this->get('/dashboard');
    $r->assertRedirect('/credenciado');
});

it('middleware bloqueia credenciado de acessar /ngo/donors', function () {
    $tenant = makeTenantForCred();
    $user   = makeUserForCred($tenant);
    $this->actingAs($user);

    $r = $this->get('/ngo/donors');
    $r->assertRedirect('/credenciado');
});

it('middleware bloqueia credenciado de acessar projeto onde NAO e member (403 redirect)', function () {
    $tenant  = makeTenantForCred();
    $user    = makeUserForCred($tenant);
    $project = makeProjectForCred($tenant);
    // Nao cria ProjectMember — user nao tem acesso.
    $this->actingAs($user);

    $r = $this->get('/projects/' . $project->id);
    $r->assertRedirect('/credenciado');
});

it('middleware libera credenciado em projeto onde e member', function () {
    $tenant  = makeTenantForCred();
    $user    = makeUserForCred($tenant);
    $project = makeProjectForCred($tenant);
    linkMemberCred($tenant, $user, $project, 'editor');
    $this->actingAs($user);

    $r = $this->get('/projects/' . $project->id);
    // O middleware libera; o controller pode retornar 200 (workspace) ou 302
    // pra outro destino. O ponto e: NAO pode redirecionar pro /credenciado
    // (isso indicaria que o middleware bloqueou), e NAO pode retornar 403.
    expect($r->getStatusCode())->not->toBe(403);
    expect($r->getStatusCode())->not->toBe(401);
    $location = (string) $r->headers->get('Location');
    expect($location)->not->toContain('/credenciado');
});

it('middleware retorna JSON 403 em requests ajax fora do escopo', function () {
    $tenant = makeTenantForCred();
    $user   = makeUserForCred($tenant);
    $this->actingAs($user);

    $r = $this->getJson('/dashboard');
    $r->assertStatus(403)->assertJson(['error_code' => 'credenciado_scope']);
});

it('middleware NAO afeta users de outros roles', function () {
    $tenant  = makeTenantForCred();
    $manager = makeUserForCred($tenant, 'manager');
    $this->actingAs($manager);

    $r = $this->get('/dashboard');
    // Manager passa pelo middleware sem bloqueio — nao pode ser redirecionado
    // pro /credenciado (isso indicaria falha do bypass).
    $location = (string) $r->headers->get('Location');
    expect($location)->not->toContain('/credenciado');
    expect($r->getStatusCode())->not->toBe(403);
});

// ── Login redirect ───────────────────────────────────────────────────────────

it('login de credenciado redireciona para /credenciado', function () {
    $tenant = makeTenantForCred();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'credenciado',
        'email'     => 'cred@test.com',
        'password'  => bcrypt('SenhaSegura123'),
    ]);

    $r = $this->post('/login', [
        'email'    => 'cred@test.com',
        'password' => 'SenhaSegura123',
    ]);

    $r->assertRedirect('/credenciado');
});

// ── /credenciado index ───────────────────────────────────────────────────────

it('credenciado com 1 projeto e redirecionado direto ao workspace', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->get('/credenciado');
    $r->assertRedirect(route('credenciado.project', $project->id));
});

it('credenciado com N projetos ve seletor no /credenciado', function () {
    $tenant   = makeTenantForCred();
    $project1 = makeProjectForCred($tenant, ['name' => 'Projeto Alfa']);
    $project2 = makeProjectForCred($tenant, ['name' => 'Projeto Beta']);
    $user     = makeUserForCred($tenant, 'credenciado', $project1->id);
    linkMemberCred($tenant, $user, $project1);
    linkMemberCred($tenant, $user, $project2);
    $this->actingAs($user);

    $r = $this->get('/credenciado');
    $r->assertOk()->assertSee('Projeto Alfa')->assertSee('Projeto Beta');
});

it('credenciado sem projetos ve mensagem vazia', function () {
    $tenant = makeTenantForCred();
    $user   = makeUserForCred($tenant);
    $this->actingAs($user);

    $r = $this->get('/credenciado');
    $r->assertOk()->assertSee('Nenhum projeto vinculado');
});

it('credenciado NAO ve projetos de outro tenant', function () {
    $tenantA = makeTenantForCred();
    $tenantB = makeTenantForCred();
    $projB   = makeProjectForCred($tenantB);
    $user    = makeUserForCred($tenantA);
    // Simula bug: ProjectMember cross-tenant (nao deveria acontecer, mas testa filtro)
    // Como a criacao valida tenant_id, forcamos direct DB:
    \DB::table('project_members')->insert([
        'tenant_id'    => $tenantA->id,
        'project_id'   => $projB->id,
        'user_id'      => $user->id,
        'access_level' => 'editor',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    $this->actingAs($user);

    $r = $this->get('/credenciado');
    // Project B nao aparece pq o CredenciadoController filtra memberships
    // com project->tenant_id == user->tenant_id (via BelongsToTenant scope).
    $r->assertOk()->assertDontSee($projB->name);
});

// ── Cadastro de credenciado no workspace ─────────────────────────────────────

it('manager cadastra credenciado com role correto, default_project_id e ProjectMember', function () {
    $tenant  = makeTenantForCred();
    $manager = makeUserForCred($tenant, 'manager');
    $project = makeProjectForCred($tenant);
    $this->actingAs($manager);

    $r = $this->post("/projects/{$project->id}/members/credenciado", [
        'name'         => 'Prof Ana',
        'email'        => 'ana@escola.com',
        'access_level' => 'editor',
    ]);

    $r->assertRedirect();

    $newUser = User::where('email', 'ana@escola.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->role)->toBe('credenciado');
    expect((int) $newUser->tenant_id)->toBe($tenant->id);
    expect((int) $newUser->default_project_id)->toBe($project->id);

    $member = ProjectMember::where('user_id', $newUser->id)->first();
    expect($member)->not->toBeNull();
    expect((int) $member->project_id)->toBe($project->id);
    expect($member->access_level)->toBe('editor');
});

it('endpoint credenciado bloqueia role employee', function () {
    $tenant   = makeTenantForCred();
    $employee = makeUserForCred($tenant, 'employee');
    $project  = makeProjectForCred($tenant);
    $this->actingAs($employee);

    $r = $this->post("/projects/{$project->id}/members/credenciado", [
        'name'         => 'X',
        'email'        => 'x@x.com',
        'access_level' => 'viewer',
    ]);
    $r->assertStatus(403);
});

// ── Vinculacao de projetos extras via manager ────────────────────────────────

it('manager vincula projeto extra ao credenciado', function () {
    $tenant   = makeTenantForCred();
    $manager  = makeUserForCred($tenant, 'manager');
    $projA    = makeProjectForCred($tenant, ['name' => 'Projeto Alfa']);
    $projB    = makeProjectForCred($tenant, ['name' => 'Projeto Beta']);
    $cred     = makeUserForCred($tenant, 'credenciado', $projA->id);
    linkMemberCred($tenant, $cred, $projA);
    $this->actingAs($manager);

    $r = $this->post("/manager/team/{$cred->id}/link-project", [
        'project_id'   => $projB->id,
        'access_level' => 'editor',
    ]);
    $r->assertRedirect();

    expect(ProjectMember::where('user_id', $cred->id)->count())->toBe(2);
});

it('manager linkProject bloqueia projeto de outro tenant', function () {
    $tenantA   = makeTenantForCred();
    $tenantB   = makeTenantForCred();
    $manager   = makeUserForCred($tenantA, 'manager');
    $cred      = makeUserForCred($tenantA, 'credenciado');
    $projB     = makeProjectForCred($tenantB); // projeto do OUTRO tenant
    $this->actingAs($manager);

    $r = $this->post("/manager/team/{$cred->id}/link-project", [
        'project_id'   => $projB->id,
        'access_level' => 'editor',
    ]);
    $r->assertSessionHasErrors(['project_id']);
});
