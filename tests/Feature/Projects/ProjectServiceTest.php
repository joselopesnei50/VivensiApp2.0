<?php

use App\Models\Tenant;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Validation\ValidationException;

// ── create ────────────────────────────────────────────────────────────────────

it('creates a project for the given tenant', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $service = new ProjectService();

    $project = $service->create([
        'name'       => 'Projeto Teste',
        'budget'     => '10000',
        'start_date' => now()->toDateString(),
        'status'     => 'active',
    ], $tenant->id);

    expect($project->tenant_id)->toBe($tenant->id)
        ->and($project->name)->toBe('Projeto Teste')
        ->and((float) $project->budget)->toBe(10000.0);

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'tenant_id' => $tenant->id]);
});

it('sanitizes brazilian budget format on create', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $project = (new ProjectService())->create([
        'name'       => 'Orçamento PT-BR',
        'budget'     => '50.000,75',
        'start_date' => now()->toDateString(),
        'status'     => 'active',
    ], $tenant->id);

    expect((float) $project->budget)->toBe(50000.75);
});

it('throws validation exception when name is missing', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    expect(fn() => (new ProjectService())->create([
        'budget'     => '1000',
        'start_date' => now()->toDateString(),
        'status'     => 'active',
    ], $tenant->id))->toThrow(ValidationException::class);
});

it('throws validation exception for invalid status', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    expect(fn() => (new ProjectService())->create([
        'name'       => 'Inválido',
        'budget'     => '1000',
        'start_date' => now()->toDateString(),
        'status'     => 'invalid-status',
    ], $tenant->id))->toThrow(ValidationException::class);
});

// ── update ────────────────────────────────────────────────────────────────────

it('updates project fields', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $project = Project::factory()->create([
        'tenant_id' => $tenant->id,
        'name'      => 'Antes',
        'status'    => 'active',
    ]);

    $updated = (new ProjectService())->update($project, [
        'name'       => 'Depois',
        'budget'     => '20000',
        'start_date' => now()->toDateString(),
        'status'     => 'paused',
    ]);

    expect($updated->name)->toBe('Depois')
        ->and($updated->status)->toBe('paused');
});

// ── listForTenant ─────────────────────────────────────────────────────────────

it('listForTenant returns only projects from the given tenant', function () {
    $t1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2 = Tenant::factory()->create(['subscription_status' => 'active']);

    Project::factory()->count(3)->create(['tenant_id' => $t1->id]);
    Project::factory()->count(2)->create(['tenant_id' => $t2->id]);

    $result = (new ProjectService())->listForTenant($t1->id);

    expect($result->total())->toBe(3);
});

it('listForTenant filters by status', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    Project::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => 'active']);
    Project::factory()->count(1)->create(['tenant_id' => $tenant->id, 'status' => 'completed']);

    $result = (new ProjectService())->listForTenant($tenant->id, ['status' => 'completed']);

    expect($result->total())->toBe(1)
        ->and($result->first()->status)->toBe('completed');
});

it('listForTenant filters by search name', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    Project::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Construção Escola']);
    Project::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Saúde Comunidade']);

    $result = (new ProjectService())->listForTenant($tenant->id, ['search' => 'Escola']);

    expect($result->total())->toBe(1)
        ->and($result->first()->name)->toBe('Construção Escola');
});

// ── flushCache ────────────────────────────────────────────────────────────────

it('flushCache clears cache keys without error', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    // Prime cache with a dummy value then flush
    \Illuminate\Support\Facades\Cache::put("dashboard.stats.{$tenant->id}", 'test', 60);

    (new ProjectService())->flushCache($tenant->id);

    expect(\Illuminate\Support\Facades\Cache::get("dashboard.stats.{$tenant->id}"))->toBeNull();
});
