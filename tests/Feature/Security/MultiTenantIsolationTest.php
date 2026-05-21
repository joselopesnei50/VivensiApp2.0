<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Project;
use App\Models\Transaction;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('user cannot access projects from another tenant via model', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user    = User::factory()->create(['tenant_id' => $tenant1->id, 'role' => 'manager']);
    $project = Project::factory()->create(['tenant_id' => $tenant2->id]);

    $this->actingAs($user);

    expect(Project::find($project->id))->toBeNull();
});

it('user cannot access transactions from another tenant via model', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user        = User::factory()->create(['tenant_id' => $tenant1->id, 'role' => 'ngo']);
    $transaction = Transaction::factory()->create(['tenant_id' => $tenant2->id]);

    $this->actingAs($user);

    expect(Transaction::find($transaction->id))->toBeNull();
});

it('super_admin can access data from any tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $admin   = User::factory()->create(['tenant_id' => $tenant1->id, 'role' => 'super_admin']);
    $project = Project::factory()->create(['tenant_id' => $tenant2->id]);

    $this->actingAs($admin);

    expect(Project::find($project->id))->not->toBeNull();
});

it('project query only returns records from own tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user = User::factory()->create(['tenant_id' => $tenant1->id, 'role' => 'manager']);
    Project::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
    Project::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

    $this->actingAs($user);

    expect(Project::count())->toBe(3);
});
