<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;

function apiProjectUser(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    $token  = $user->createToken('test')->plainTextToken;
    return [$user, $token, $tenant];
}

// ── GET /api/v1/projects ──────────────────────────────────────────────────────

it('v1 projects lists only tenant projects', function () {
    [$user, $token, $tenant] = apiProjectUser();

    Project::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $otherTenant = Tenant::factory()->create(['subscription_status' => 'active']);
    Project::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->withToken($token)->getJson('/api/v1/projects');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

it('v1 projects supports status filter', function () {
    [$user, $token, $tenant] = apiProjectUser();

    Project::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
    Project::factory()->create(['tenant_id' => $tenant->id, 'status' => 'completed']);

    $response = $this->withToken($token)->getJson('/api/v1/projects?status=active');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

it('v1 shows single project', function () {
    [$user, $token, $tenant] = apiProjectUser();

    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $this->withToken($token)
         ->getJson("/api/v1/projects/{$project->id}")
         ->assertOk()
         ->assertJsonPath('data.id', $project->id);
});

it('v1 project cross-tenant returns 404', function () {
    [, $token] = apiProjectUser();

    $other   = Tenant::factory()->create(['subscription_status' => 'active']);
    $project = Project::factory()->create(['tenant_id' => $other->id]);

    $this->withToken($token)
         ->getJson("/api/v1/projects/{$project->id}")
         ->assertStatus(404);
});

it('v1 project tasks returns tasks for that project', function () {
    [$user, $token, $tenant] = apiProjectUser();

    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    Task::factory()->count(3)->create(['tenant_id' => $tenant->id, 'project_id' => $project->id]);

    $this->withToken($token)
         ->getJson("/api/v1/projects/{$project->id}/tasks")
         ->assertOk()
         ->assertJsonCount(3, 'data');
});

// ── GET /api/v1/tasks ─────────────────────────────────────────────────────────

it('v1 tasks lists only tenant tasks', function () {
    [$user, $token, $tenant] = apiProjectUser();

    Task::factory()->count(4)->create(['tenant_id' => $tenant->id]);

    $response = $this->withToken($token)->getJson('/api/v1/tasks');
    $response->assertOk();
    expect($response->json('meta.total'))->toBe(4);
});

it('v1 tasks supports priority filter', function () {
    [$user, $token, $tenant] = apiProjectUser();

    Task::factory()->count(2)->create(['tenant_id' => $tenant->id, 'priority' => 'high']);
    Task::factory()->create(['tenant_id' => $tenant->id, 'priority' => 'low']);

    $response = $this->withToken($token)->getJson('/api/v1/tasks?priority=high');
    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

// ── POST /api/v1/tasks ────────────────────────────────────────────────────────

it('v1 creates task via api', function () {
    [$user, $token, $tenant] = apiProjectUser();

    $response = $this->withToken($token)->postJson('/api/v1/tasks', [
        'title'    => 'Tarefa via API',
        'priority' => 'high',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.title', 'Tarefa via API')
             ->assertJsonPath('data.status', 'todo');

    $this->assertDatabaseHas('tasks', [
        'tenant_id' => $tenant->id,
        'title'     => 'Tarefa via API',
    ]);
});

it('v1 task creation requires title', function () {
    [, $token] = apiProjectUser();

    $this->withToken($token)
         ->postJson('/api/v1/tasks', [])
         ->assertStatus(422)
         ->assertJsonValidationErrors(['title']);
});

it('v1 task cross-tenant project_id rejected', function () {
    [, $token] = apiProjectUser();

    $other   = Tenant::factory()->create(['subscription_status' => 'active']);
    $project = Project::factory()->create(['tenant_id' => $other->id]);

    $this->withToken($token)
         ->postJson('/api/v1/tasks', [
             'title'      => 'Hack attempt',
             'project_id' => $project->id,
         ])
         ->assertStatus(422)
         ->assertJsonValidationErrors(['project_id']);
});
