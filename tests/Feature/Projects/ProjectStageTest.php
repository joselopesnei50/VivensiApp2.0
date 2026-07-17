<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

/**
 * Etapas de Projeto (ProjectStage) — cobertura Fase 5.
 *
 * Cobre:
 *   - CRUD via HTTP (index/store/update/destroy/reorder/complete)
 *   - Tenant guard IDOR: stage cross-project e cross-tenant retornam 404
 *   - Regra determinista de current_stage: atual / atrasada / proxima / null
 *   - calculated_budget: soma stages ativas com tolerancia +-0.01
 *   - complete() cria Transaction sugerida (approval_status=pending) quando
 *     planned_value > 0; nao cria quando 0
 *   - Soft delete preserva stage (deleted_at)
 */

uses(RefreshDatabase::class);

function stageOwner(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'ngo',
    ]);
}

function projectFor(User $user, string $name = 'Projeto Cultural'): Project
{
    return Project::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name'      => $name,
    ]);
}

function stageFor(Project $project, array $attrs = []): ProjectStage
{
    return ProjectStage::create(array_merge([
        'tenant_id'     => $project->tenant_id,
        'project_id'    => $project->id,
        'title'         => 'Etapa X',
        'planned_value' => 0,
        'order'         => 1,
        'status'        => 'pending',
    ], $attrs));
}

// ── CRUD ────────────────────────────────────────────────────────────────────

it('lista stages via JSON', function () {
    $user = stageOwner();
    $p = projectFor($user);
    stageFor($p, ['title' => 'A', 'order' => 1, 'planned_value' => 100]);
    stageFor($p, ['title' => 'B', 'order' => 2, 'planned_value' => 250]);

    $r = $this->actingAs($user)
        ->getJson("/projects/{$p->id}/stages");

    $r->assertOk()
        ->assertJsonPath('project_id', $p->id)
        ->assertJsonCount(2, 'stages');
    expect((float) $r->json('calculated_budget'))->toBe(350.0);
});

it('cria stage nova via POST', function () {
    $user = stageOwner();
    $p = projectFor($user);

    $r = $this->actingAs($user)
        ->postJson("/projects/{$p->id}/stages", [
            'title'         => 'Pre-producao',
            'planned_value' => 1500.5,
            'status'        => 'in_progress',
        ]);

    $r->assertCreated()
        ->assertJsonPath('stage.title', 'Pre-producao')
        ->assertJsonPath('stage.status', 'in_progress');

    $stage = ProjectStage::withoutGlobalScope('tenant')->first();
    expect((int) $stage->project_id)->toBe($p->id);
    expect((int) $stage->order)->toBe(1); // auto-assign primeira
});

it('atribui order incremental automatico ao criar sem order', function () {
    $user = stageOwner();
    $p = projectFor($user);
    stageFor($p, ['order' => 1]);
    stageFor($p, ['order' => 2]);

    $this->actingAs($user)
        ->postJson("/projects/{$p->id}/stages", ['title' => 'Nova'])
        ->assertCreated()
        ->assertJsonPath('stage.order', 3);
});

it('atualiza stage via PUT', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p, ['title' => 'antigo']);

    $this->actingAs($user)
        ->putJson("/projects/{$p->id}/stages/{$s->id}", [
            'title'         => 'novo titulo',
            'planned_value' => 999.99,
        ])
        ->assertOk()
        ->assertJsonPath('stage.title', 'novo titulo')
        ->assertJsonPath('stage.planned_value', 999.99);
});

it('soft delete via DELETE (deleted_at nao null)', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p);

    $this->actingAs($user)
        ->deleteJson("/projects/{$p->id}/stages/{$s->id}")
        ->assertOk();

    // Volta a bater com scope de soft delete
    expect(ProjectStage::withoutGlobalScope('tenant')->find($s->id))->toBeNull();
    expect(ProjectStage::withoutGlobalScope('tenant')->withTrashed()->find($s->id))
        ->not->toBeNull()
        ->and(ProjectStage::withoutGlobalScope('tenant')->withTrashed()->find($s->id)->trashed())
        ->toBeTrue();
});

it('reordena stages via POST /reorder', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s1 = stageFor($p, ['title' => 'A', 'order' => 1]);
    $s2 = stageFor($p, ['title' => 'B', 'order' => 2]);
    $s3 = stageFor($p, ['title' => 'C', 'order' => 3]);

    $this->actingAs($user)
        ->postJson("/projects/{$p->id}/stages/reorder", [
            'stages' => [
                ['id' => $s3->id, 'order' => 1],
                ['id' => $s1->id, 'order' => 2],
                ['id' => $s2->id, 'order' => 3],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('updated', 3);

    expect((int) $s3->fresh()->order)->toBe(1);
    expect((int) $s1->fresh()->order)->toBe(2);
    expect((int) $s2->fresh()->order)->toBe(3);
});

// ── Complete + Transaction sugerida ─────────────────────────────────────────

it('completar stage com planned_value > 0 cria Transaction sugerida', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p, ['planned_value' => 750.25, 'status' => 'in_progress']);

    $r = $this->actingAs($user)
        ->postJson("/projects/{$p->id}/stages/{$s->id}/complete");

    $r->assertOk()
        ->assertJsonPath('stage.status', 'completed');

    $suggested = $r->json('suggested_transaction_id');
    expect($suggested)->not->toBeNull();

    $tx = Transaction::withoutGlobalScope('tenant')->find($suggested);
    expect($tx)->not->toBeNull();
    expect((int) $tx->stage_id)->toBe($s->id);
    expect($tx->type)->toBe('income');
    expect($tx->status)->toBe('pending');
    expect($tx->approval_status)->toBe('pending');
    expect((float) $tx->amount)->toBe(750.25);
});

it('completar stage com planned_value = 0 NAO cria Transaction', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p, ['planned_value' => 0]);

    $r = $this->actingAs($user)
        ->postJson("/projects/{$p->id}/stages/{$s->id}/complete");

    $r->assertOk()->assertJsonPath('suggested_transaction_id', null);
    expect(Transaction::withoutGlobalScope('tenant')->where('stage_id', $s->id)->count())->toBe(0);
});

// ── Tenant isolation (IDOR proof) ───────────────────────────────────────────

it('bloqueia acesso a stages de projeto de outro tenant', function () {
    $userA = stageOwner();
    $userB = stageOwner();
    $projectB = projectFor($userB);
    stageFor($projectB);

    $this->actingAs($userA)
        ->getJson("/projects/{$projectB->id}/stages")
        ->assertNotFound();
});

it('bloqueia editar stage que pertence a outro projeto', function () {
    $user = stageOwner();
    $projA = projectFor($user, 'A');
    $projB = projectFor($user, 'B');
    $stageA = stageFor($projA);

    // Tentar editar stageA usando o id do projB → deve 404
    $this->actingAs($user)
        ->putJson("/projects/{$projB->id}/stages/{$stageA->id}", ['title' => 'hack'])
        ->assertNotFound();

    expect($stageA->fresh()->title)->toBe('Etapa X');
});

it('bloqueia deletar stage de projeto de outro tenant', function () {
    $userA = stageOwner();
    $userB = stageOwner();
    $projectB = projectFor($userB);
    $stageB = stageFor($projectB);

    $this->actingAs($userA)
        ->deleteJson("/projects/{$projectB->id}/stages/{$stageB->id}")
        ->assertNotFound();

    expect(ProjectStage::withoutGlobalScope('tenant')->find($stageB->id))->not->toBeNull();
});

// ── Regra current_stage (4 cenarios) ────────────────────────────────────────

it('current_stage: retorna stage in_progress dentro do range (ATUAL)', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $atual = stageFor($p, [
        'title'      => 'atual',
        'order'      => 1,
        'start_date' => Carbon::now()->subDays(5),
        'end_date'   => Carbon::now()->addDays(10),
        'status'     => 'in_progress',
    ]);
    stageFor($p, [
        'title'      => 'futura',
        'order'      => 2,
        'start_date' => Carbon::now()->addDays(20),
        'end_date'   => Carbon::now()->addDays(40),
        'status'     => 'pending',
    ]);

    expect($p->fresh()->current_stage?->id)->toBe($atual->id);
});

it('current_stage: retorna stage vencida quando nenhuma esta in_progress no range (ATRASADA)', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $atrasada = stageFor($p, [
        'title'      => 'atrasada',
        'order'      => 1,
        'start_date' => Carbon::now()->subDays(30),
        'end_date'   => Carbon::now()->subDays(5),
        'status'     => 'pending',
    ]);

    expect($p->fresh()->current_stage?->id)->toBe($atrasada->id);
});

it('current_stage: retorna primeira futura quando nenhuma esta em andamento (PROXIMA)', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $futura = stageFor($p, [
        'title'      => 'futura',
        'order'      => 1,
        'start_date' => Carbon::now()->addDays(5),
        'end_date'   => Carbon::now()->addDays(20),
        'status'     => 'pending',
    ]);

    expect($p->fresh()->current_stage?->id)->toBe($futura->id);
});

it('current_stage: retorna null quando tudo completed', function () {
    $user = stageOwner();
    $p = projectFor($user);
    stageFor($p, [
        'order'        => 1,
        'start_date'   => Carbon::now()->subDays(20),
        'end_date'     => Carbon::now()->subDays(5),
        'status'       => 'completed',
        'completed_at' => Carbon::now()->subDays(4),
    ]);

    expect($p->fresh()->current_stage)->toBeNull();
});

// ── calculated_budget + tolerancia ──────────────────────────────────────────

it('calculated_budget soma stages nao canceladas', function () {
    $user = stageOwner();
    $p = projectFor($user);
    stageFor($p, ['planned_value' => 100.10, 'order' => 1]);
    stageFor($p, ['planned_value' => 250.30, 'order' => 2]);
    stageFor($p, ['planned_value' => 999.99, 'order' => 3, 'status' => 'cancelled']);

    expect((float) $p->fresh()->calculated_budget)->toBe(350.40);
});

it('calculated_budget retorna null se projeto nao tem stages', function () {
    $user = stageOwner();
    $p = projectFor($user);

    expect($p->fresh()->calculated_budget)->toBeNull();
});

// ── Espaco de trabalho da etapa (show) ───────────────────────────────────────

it('renderiza pagina de espaco da etapa com tarefas e transactions', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p, ['title' => 'Producao', 'planned_value' => 5000, 'status' => 'in_progress']);

    Task::create([
        'tenant_id' => $user->tenant_id, 'project_id' => $p->id, 'stage_id' => $s->id,
        'title' => 'Contratar audio', 'status' => 'todo', 'priority' => 'high',
        'created_by' => $user->id,
    ]);
    Transaction::create([
        'tenant_id' => $user->tenant_id, 'project_id' => $p->id, 'stage_id' => $s->id,
        'description' => 'Aluguel de camera', 'amount' => 1200,
        'type' => 'expense', 'date' => now()->toDateString(),
        'status' => 'paid', 'approval_status' => 'approved',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$p->id}/stages/{$s->id}")
        ->assertOk()
        ->assertSee('Producao')
        ->assertSee('Contratar audio')
        ->assertSee('Aluguel de camera');
});

it('show da etapa bloqueia cross-tenant (404)', function () {
    $ownerA = stageOwner();
    $ownerB = stageOwner();
    $pB = projectFor($ownerB);
    $sB = stageFor($pB);

    $this->actingAs($ownerA)
        ->get("/projects/{$pB->id}/stages/{$sB->id}")
        ->assertNotFound();
});

// ── stage_id em Task/Transaction stores ─────────────────────────────────────

it('POST /tasks aceita stage_id quando pertence ao mesmo projeto', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p);

    $this->actingAs($user)->post('/tasks', [
        'project_id' => $p->id,
        'stage_id'   => $s->id,
        'title'      => 'Nova tarefa',
        'status'     => 'todo',
    ]);

    $t = Task::withoutGlobalScope('tenant')->where('title', 'Nova tarefa')->first();
    expect($t)->not->toBeNull();
    expect((int) $t->stage_id)->toBe($s->id);
});

it('POST /tasks rejeita stage_id de outro projeto (anti-IDOR)', function () {
    $user = stageOwner();
    $projA = projectFor($user, 'A');
    $projB = projectFor($user, 'B');
    $stageB = stageFor($projB);

    // project_id=A + stage_id=B -> stage rejeitada (fica null)
    $this->actingAs($user)->post('/tasks', [
        'project_id' => $projA->id,
        'stage_id'   => $stageB->id,
        'title'      => 'IDOR test',
        'status'     => 'todo',
    ]);

    $t = Task::withoutGlobalScope('tenant')->where('title', 'IDOR test')->first();
    expect($t)->not->toBeNull();
    expect($t->stage_id)->toBeNull();
});

it('POST /transactions aceita stage_id quando pertence ao mesmo projeto', function () {
    $user = stageOwner();
    $p = projectFor($user);
    $s = stageFor($p);

    $this->actingAs($user)->post('/transactions', [
        'project_id'  => $p->id,
        'stage_id'    => $s->id,
        'description' => 'Compra material',
        'amount'      => '150,00',
        'date'        => now()->toDateString(),
        'type'        => 'expense',
    ]);

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'Compra material')->first();
    expect($tx)->not->toBeNull();
    expect((int) $tx->stage_id)->toBe($s->id);
});

it('POST /transactions rejeita stage_id de outro projeto (anti-IDOR)', function () {
    $user = stageOwner();
    $projA = projectFor($user, 'A');
    $projB = projectFor($user, 'B');
    $stageB = stageFor($projB);

    $this->actingAs($user)->post('/transactions', [
        'project_id'  => $projA->id,
        'stage_id'    => $stageB->id,
        'description' => 'IDOR tx test',
        'amount'      => '99,00',
        'date'        => now()->toDateString(),
        'type'        => 'expense',
    ]);

    $tx = Transaction::withoutGlobalScope('tenant')->where('description', 'IDOR tx test')->first();
    expect($tx)->not->toBeNull();
    expect($tx->stage_id)->toBeNull();
});
