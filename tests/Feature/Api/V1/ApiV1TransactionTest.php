<?php

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;

function apiUser(string $role = 'manager'): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
    $token  = $user->createToken('test')->plainTextToken;
    return [$user, $token, $tenant];
}

// ── Authentication guard ──────────────────────────────────────────────────────

it('v1 api requires bearer token', function () {
    $this->getJson('/api/v1/transactions')->assertStatus(401);
});

it('v1 me returns authenticated user data', function () {
    [, $token] = apiUser();

    $this->withToken($token)
         ->getJson('/api/v1/me')
         ->assertOk()
         ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'tenant']]);
});

// ── GET /api/v1/transactions ──────────────────────────────────────────────────

it('v1 transactions lists only tenant transactions', function () {
    [$user, $token, $tenant] = apiUser();

    Transaction::factory()->count(3)->create(['tenant_id' => $tenant->id, 'type' => 'income', 'status' => 'paid', 'approval_status' => 'approved']);

    $otherTenant = Tenant::factory()->create(['subscription_status' => 'active']);
    Transaction::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->withToken($token)->getJson('/api/v1/transactions');

    $response->assertOk()
             ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page']]);

    expect($response->json('meta.total'))->toBe(3);
});

it('v1 transactions supports type filter', function () {
    [$user, $token, $tenant] = apiUser();

    Transaction::factory()->count(2)->create(['tenant_id' => $tenant->id, 'type' => 'income', 'status' => 'paid', 'approval_status' => 'approved']);
    Transaction::factory()->count(1)->create(['tenant_id' => $tenant->id, 'type' => 'expense', 'status' => 'paid', 'approval_status' => 'approved']);

    $response = $this->withToken($token)->getJson('/api/v1/transactions?type=income');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

it('v1 transactions pagination caps at 100', function () {
    [$user, $token] = apiUser();

    $response = $this->withToken($token)->getJson('/api/v1/transactions?per_page=999');

    $response->assertOk();
    expect($response->json('meta.per_page'))->toBeLessThanOrEqual(100);
});

// ── POST /api/v1/transactions ─────────────────────────────────────────────────

it('v1 creates transaction via api', function () {
    [$user, $token, $tenant] = apiUser();

    $response = $this->withToken($token)->postJson('/api/v1/transactions', [
        'description' => 'Venda de produto via API',
        'amount'      => 250.00,
        'date'        => now()->toDateString(),
        'type'        => 'income',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.description', 'Venda de produto via API')
             ->assertJsonPath('data.type', 'income');

    $this->assertDatabaseHas('transactions', [
        'tenant_id'   => $tenant->id,
        'description' => 'Venda de produto via API',
    ]);
});

it('v1 transaction creation validates required fields', function () {
    [, $token] = apiUser();

    $this->withToken($token)
         ->postJson('/api/v1/transactions', [])
         ->assertStatus(422)
         ->assertJsonValidationErrors(['description', 'amount', 'date', 'type']);
});

it('v1 transaction creation rejects invalid type', function () {
    [, $token] = apiUser();

    $this->withToken($token)
         ->postJson('/api/v1/transactions', [
             'description' => 'Test',
             'amount' => 10,
             'date' => now()->toDateString(),
             'type' => 'invalid_type',
         ])
         ->assertStatus(422)
         ->assertJsonValidationErrors(['type']);
});

// ── GET /api/v1/transactions/{id} ─────────────────────────────────────────────

it('v1 shows single transaction', function () {
    [$user, $token, $tenant] = apiUser();

    $tx = Transaction::factory()->create(['tenant_id' => $tenant->id, 'status' => 'paid', 'approval_status' => 'approved']);

    $this->withToken($token)
         ->getJson("/api/v1/transactions/{$tx->id}")
         ->assertOk()
         ->assertJsonPath('data.id', $tx->id);
});

it('v1 transaction cross-tenant access returns 404', function () {
    [, $token] = apiUser();

    $otherTenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $tx = Transaction::factory()->create(['tenant_id' => $otherTenant->id, 'status' => 'paid', 'approval_status' => 'approved']);

    $this->withToken($token)
         ->getJson("/api/v1/transactions/{$tx->id}")
         ->assertStatus(404);
});

afterEach(fn () => Mockery::close());
