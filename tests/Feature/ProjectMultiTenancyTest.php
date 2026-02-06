<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Test: Users cannot access projects from other tenants
 */
test('users cannot access other tenants projects', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user1 = User::factory()->forTenant($tenant1)->create();
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $project1 = Project::factory()->create([
        'tenant_id' => $tenant1->id,
        'name' => 'Secret Project Alpha',
    ]);
    
    // Act: Login as user from tenant 2
    $this->actingAs($user2);
    $response = $this->get('/projects');
    
    // Assert
    $response->assertDontSee('Secret Project Alpha');
});

/**
 * Test: Direct project access is blocked for other tenants
 */
test('direct project access is blocked for other tenants', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $project1 = Project::factory()->create([
        'tenant_id' => $tenant1->id,
    ]);
    
    // Act
    $this->actingAs($user2);
    $response = $this->get("/projects/{$project1->id}");
    
    // Assert: Should be forbidden
    $response->assertStatus(403);
});

/**
 * Test: Project creation is scoped to tenant
 */
test('project creation is scoped to authenticated users tenant', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    
    // Act
    $this->actingAs($user);
    $response = $this->post('/projects', [
        'name' => 'New Project',
        'description' => 'Test project',
        'budget' => 5000.00,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->addMonths(3)->format('Y-m-d'),
    ]);
    
    // Assert
    $this->assertDatabaseHas('projects', [
        'name' => 'New Project',
        'tenant_id' => $tenant->id,
    ]);
});

/**
 * Test: Project update respects tenant isolation
 */
test('project update respects tenant isolation', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $project1 = Project::factory()->create([
        'tenant_id' => $tenant1->id,
        'name' => 'Original Name',
    ]);
    
    // Act
    $this->actingAs($user2);
    $response = $this->put("/projects/{$project1->id}", [
        'name' => 'Hacked Name',
    ]);
    
    // Assert
    $response->assertStatus(403);
    
    $this->assertDatabaseHas('projects', [
        'id' => $project1->id,
        'name' => 'Original Name',
    ]);
});
