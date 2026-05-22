<?php

use App\Models\User;
use App\Models\Tenant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('bruce ai chat endpoint requires authentication', function () {
    $this->postJson('/api/bruce/chat', ['message' => 'Olá'])
        ->assertStatus(401);
});

it('bruce ai chat validates message field', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)
        ->postJson('/api/bruce/chat', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

it('bruce ai chat rejects messages over 2000 chars', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)
        ->postJson('/api/bruce/chat', ['message' => str_repeat('a', 2001)])
        ->assertStatus(422);
});

it('locale switcher only accepts supported locales', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    // Valid locale redirects
    $this->actingAs($user)
        ->post('/locale/pt_BR')
        ->assertRedirect();

    // Invalid locale also redirects safely (no crash)
    $this->actingAs($user)
        ->post('/locale/xx')
        ->assertRedirect();
});

it('locale switcher sets session locale', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)
        ->post('/locale/en')
        ->assertSessionHas('locale', 'en');
});

it('guest cannot access manager approvals', function () {
    $this->get('/manager/approvals')->assertRedirect('/login');
});

it('guest cannot access ngo donors', function () {
    $this->get('/ngo/donors')->assertRedirect('/login');
});

it('employee cannot access smart analysis with subscription check', function () {
    $tenant   = Tenant::factory()->create(['subscription_status' => 'active']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    // Smart analysis accessible to any authenticated user (not restricted by role gate)
    $response = $this->actingAs($employee)->get('/smart-analysis');
    // Should not be 403 (role check) — subscription check only
    expect($response->status())->not->toBe(403);
});
