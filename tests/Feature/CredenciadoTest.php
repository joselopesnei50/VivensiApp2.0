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

it('middleware bloqueia credenciado no workspace normal /projects/{id} mesmo se e member', function () {
    // POLITICA DE SEGURANCA: o workspace normal do projeto expoe financeiro,
    // doadores, transacoes e membros da equipe interna. Credenciado NUNCA
    // acessa essa URL — ele so entra via /credenciado/projeto/{id}.
    $tenant  = makeTenantForCred();
    $user    = makeUserForCred($tenant);
    $project = makeProjectForCred($tenant);
    linkMemberCred($tenant, $user, $project, 'editor');
    $this->actingAs($user);

    $r = $this->get('/projects/' . $project->id);
    $r->assertRedirect('/credenciado');
});

it('middleware bloqueia credenciado nas subrotas do projeto (financeiro/tarefas/etc)', function () {
    $tenant  = makeTenantForCred();
    $user    = makeUserForCred($tenant);
    $project = makeProjectForCred($tenant);
    linkMemberCred($tenant, $user, $project, 'admin');
    $this->actingAs($user);

    foreach ([
        "/projects/{$project->id}/kanban",
        "/projects/{$project->id}/planning",
        "/projects/{$project->id}/stages",
        "/transactions",
        "/ngo/donors",
        "/manager/team",
        "/finance/import",
        "/email-campaigns/ai/quota",
    ] as $blocked) {
        $r = $this->get($blocked);
        expect($r->getStatusCode())->toBe(302);
        expect((string) $r->headers->get('Location'))->toContain('/credenciado');
    }
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

it('credenciado com 1 projeto e redirecionado direto ao workspace minimal', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->get('/credenciado');
    $r->assertRedirect(route('credenciado.project', $project->id));
});

it('workspace minimal do credenciado renderiza sem expor financeiro/doadores', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant, ['name' => 'Projeto Oficina', 'description' => 'Descricao do projeto']);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project, 'editor');
    $this->actingAs($user);

    $r = $this->get(route('credenciado.project', $project->id));
    $r->assertOk()
      ->assertSee('Projeto Oficina')
      ->assertSee('Minhas tarefas')
      ->assertSee('Aulas do projeto')
      // Palavras que NAO devem aparecer (marcadores das seções sensíveis do
      // workspace normal /projects/{id}):
      ->assertDontSee('Dossiê Financeiro')
      ->assertDontSee('Stakeholders')
      ->assertDontSee('addMemberModal');
});

it('credenciado atualiza status de tarefa propria', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $task = \App\Models\Task::create([
        'tenant_id'   => $tenant->id,
        'project_id'  => $project->id,
        'title'       => 'Preparar material da aula',
        'status'      => 'todo',
        'assigned_to' => $user->id,
        'created_by'  => $user->id,
    ]);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.task.status', ['id' => $project->id, 'taskId' => $task->id]), [
        'status' => 'in_progress',
    ]);
    $r->assertRedirect();
    expect($task->fresh()->status)->toBe('in_progress');
});

// ── Diario de evolucao (ProjectLog) ──────────────────────────────────────────

it('credenciado registra entrada no diario do projeto', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.log.store', $project->id), [
        'body' => 'Aula fluiu bem, 8 alunos presentes. Faltou material do modulo 3.',
    ]);
    $r->assertRedirect();

    $log = \App\Models\ProjectLog::where('project_id', $project->id)->first();
    expect($log)->not->toBeNull();
    expect($log->body)->toContain('Aula fluiu bem');
    expect((int) $log->user_id)->toBe($user->id);
    expect((int) $log->tenant_id)->toBe($tenant->id);
});

it('credenciado NAO pode registrar log em projeto onde nao e member', function () {
    $tenant   = makeTenantForCred();
    $projA    = makeProjectForCred($tenant, ['name' => 'Alfa']);
    $projB    = makeProjectForCred($tenant, ['name' => 'Beta']);
    $user     = makeUserForCred($tenant, 'credenciado', $projA->id);
    linkMemberCred($tenant, $user, $projA); // so em A
    $this->actingAs($user);

    $r = $this->post(route('credenciado.log.store', $projB->id), [
        'body' => 'Tentativa em projeto alheio',
    ]);
    $r->assertStatus(403);
    expect(\App\Models\ProjectLog::where('project_id', $projB->id)->count())->toBe(0);
});

it('log valida body obrigatorio', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.log.store', $project->id), ['body' => '']);
    $r->assertSessionHasErrors(['body']);
});

// ── Marcos / Timeline (ProjectTimelineRecord) ───────────────────────────────

it('credenciado registra marco no timeline', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.timeline.store', $project->id), [
        'title'   => 'Finalizamos modulo 1',
        'content' => 'Encerramos com apresentacao dos alunos.',
        'type'    => 'milestone',
        'date'    => '2026-07-27',
    ]);
    $r->assertRedirect();

    $tl = \App\Models\ProjectTimelineRecord::where('project_id', $project->id)->first();
    expect($tl)->not->toBeNull();
    expect($tl->title)->toBe('Finalizamos modulo 1');
    expect($tl->type)->toBe('milestone');
});

it('credenciado NAO pode criar marco em projeto onde nao e member', function () {
    $tenant   = makeTenantForCred();
    $projA    = makeProjectForCred($tenant, ['name' => 'Alfa']);
    $projB    = makeProjectForCred($tenant, ['name' => 'Beta']);
    $user     = makeUserForCred($tenant, 'credenciado', $projA->id);
    linkMemberCred($tenant, $user, $projA);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.timeline.store', $projB->id), [
        'title' => 'Invasao', 'type' => 'milestone',
    ]);
    $r->assertStatus(403);
});

it('marco com foto salva media_path e associa ao registro', function () {
    // fake()->image usa GD (imagecreatetruecolor). Se a extensao nao esta
    // instalada no PHP local, pula — o pipeline CI/prod tem GD habilitada.
    if (!function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('GD extension nao disponivel no PHP local.');
    }

    \Illuminate\Support\Facades\Storage::fake('public');

    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $file = \Illuminate\Http\UploadedFile::fake()->image('evidencia.jpg', 800, 600);

    $r = $this->post(route('credenciado.timeline.store', $project->id), [
        'title' => 'Apresentacao final',
        'type'  => 'photo',
        'media' => $file,
    ]);
    $r->assertRedirect();

    $tl = \App\Models\ProjectTimelineRecord::where('project_id', $project->id)->first();
    expect($tl)->not->toBeNull();
    expect($tl->media_path)->not->toBeNull();
    expect($tl->media_path)->toContain("tenants/{$tenant->id}/projects/timeline/");
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($tl->media_path);
});

it('marco rejeita tipo invalido', function () {
    $tenant  = makeTenantForCred();
    $project = makeProjectForCred($tenant);
    $user    = makeUserForCred($tenant, 'credenciado', $project->id);
    linkMemberCred($tenant, $user, $project);
    $this->actingAs($user);

    $r = $this->post(route('credenciado.timeline.store', $project->id), [
        'title' => 'X', 'type' => 'video', // video nao esta na whitelist do credenciado
    ]);
    $r->assertSessionHasErrors(['type']);
});

it('credenciado NAO pode atualizar tarefa de outro membro', function () {
    $tenant   = makeTenantForCred();
    $project  = makeProjectForCred($tenant);
    $cred     = makeUserForCred($tenant, 'credenciado', $project->id);
    $outroUser = makeUserForCred($tenant, 'employee');
    linkMemberCred($tenant, $cred, $project);
    linkMemberCred($tenant, $outroUser, $project);
    $tarefaAlheia = \App\Models\Task::create([
        'tenant_id'   => $tenant->id,
        'project_id'  => $project->id,
        'title'       => 'Tarefa de outro',
        'status'      => 'todo',
        'assigned_to' => $outroUser->id,
        'created_by'  => $outroUser->id,
    ]);
    $this->actingAs($cred);

    $r = $this->post(route('credenciado.task.status', ['id' => $project->id, 'taskId' => $tarefaAlheia->id]), [
        'status' => 'completed',
    ]);
    $r->assertStatus(404);
    expect($tarefaAlheia->fresh()->status)->toBe('todo');
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
