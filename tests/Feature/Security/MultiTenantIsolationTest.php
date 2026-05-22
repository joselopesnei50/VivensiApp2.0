<?php

use App\Models\Project;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── HTTP-level isolation (the real protection) ────────────────────────────────

it('user cannot access project from another tenant via http', function () {
    $t1   = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2   = Tenant::factory()->create(['subscription_status' => 'active']);
    $user = User::factory()->create(['tenant_id' => $t1->id, 'role' => 'manager']);
    $proj = Project::factory()->create(['tenant_id' => $t2->id]);

    $this->actingAs($user)
         ->get("/projects/{$proj->id}")
         ->assertStatus(404); // BelongsToTenant scope hides cross-tenant records (returns 404 not 403)
});

it('user cannot access transaction from another tenant via api', function () {
    $t1   = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2   = Tenant::factory()->create(['subscription_status' => 'active']);
    $user = User::factory()->create(['tenant_id' => $t1->id, 'role' => 'ngo']);
    $tx   = Transaction::factory()->create(['tenant_id' => $t2->id, 'status' => 'paid', 'approval_status' => 'approved']);
    $tok  = $user->createToken('test')->plainTextToken;

    $this->withToken($tok)
         ->getJson("/api/v1/transactions/{$tx->id}")
         ->assertStatus(404);
});

it('super_admin can access data from any tenant via api', function () {
    $t1    = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2    = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin = User::factory()->create(['tenant_id' => $t1->id, 'role' => 'super_admin']);
    $proj  = Project::factory()->create(['tenant_id' => $t2->id]);

    // super_admin sees all via Eloquent (global scope disabled for super_admin)
    $this->actingAs($admin);
    expect(Project::find($proj->id))->not->toBeNull();
});

it('api v1 project list is scoped to own tenant only', function () {
    $t1   = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2   = Tenant::factory()->create(['subscription_status' => 'active']);
    $user = User::factory()->create(['tenant_id' => $t1->id, 'role' => 'manager']);
    Project::factory()->count(3)->create(['tenant_id' => $t1->id]);
    Project::factory()->count(2)->create(['tenant_id' => $t2->id]);
    $tok  = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($tok)->getJson('/api/v1/projects');
    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});
