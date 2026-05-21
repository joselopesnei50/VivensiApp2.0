<?php

use App\Models\User;
use App\Models\Tenant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('unauthenticated user is redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('unauthenticated user cannot access admin panel', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('non-super-admin gets 403 on admin panel', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

it('non-super-admin manager gets 403 on admin panel', function () {
    $tenant  = Tenant::factory()->create();
    $manager = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($manager)
        ->get('/admin')
        ->assertStatus(403);
});

it('authenticated user can access dashboard', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});
