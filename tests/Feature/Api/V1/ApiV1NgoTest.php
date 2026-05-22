<?php

use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Models\Tenant;
use App\Models\User;

function ngoApiUser(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
    $token  = $user->createToken('test')->plainTextToken;
    return [$user, $token, $tenant];
}

// ── Access control ────────────────────────────────────────────────────────────

it('ngo endpoints require authentication', function () {
    $this->getJson('/api/v1/ngo/donors')->assertStatus(401);
});

it('manager role cannot access ngo endpoints', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    $token  = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/ngo/donors')->assertStatus(403);
});

it('common role cannot access ngo endpoints', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'common']);
    $token  = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/ngo/summary')->assertStatus(403);
});

// ── GET /api/v1/ngo/summary ───────────────────────────────────────────────────

it('ngo summary returns donor and grant counts', function () {
    [$user, $token, $tenant] = ngoApiUser();

    NgoDonor::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'type' => 'company']);

    $response = $this->withToken($token)->getJson('/api/v1/ngo/summary');

    $response->assertOk()
             ->assertJsonStructure(['data' => [
                 'donors_total', 'donors_by_type', 'grants_total', 'grants_by_status', 'grants_value_total',
             ]]);

    expect($response->json('data.donors_total'))->toBe(4);
});

// ── GET /api/v1/ngo/donors ────────────────────────────────────────────────────

it('ngo donors list scoped to tenant', function () {
    [$user, $token, $tenant] = ngoApiUser();

    NgoDonor::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    $other = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoDonor::factory()->count(2)->create(['tenant_id' => $other->id]);

    $response = $this->withToken($token)->getJson('/api/v1/ngo/donors');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

it('ngo donors supports type filter', function () {
    [$user, $token, $tenant] = ngoApiUser();

    NgoDonor::factory()->count(2)->create(['tenant_id' => $tenant->id, 'type' => 'individual']);
    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'type' => 'company']);

    $response = $this->withToken($token)->getJson('/api/v1/ngo/donors?type=individual');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

it('ngo donors supports search filter', function () {
    [$user, $token, $tenant] = ngoApiUser();

    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'João Silva']);
    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Maria Souza']);

    $response = $this->withToken($token)->getJson('/api/v1/ngo/donors?search=João');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

// ── POST /api/v1/ngo/donors ───────────────────────────────────────────────────

it('ngo can create donor via api', function () {
    [$user, $token, $tenant] = ngoApiUser();

    $response = $this->withToken($token)->postJson('/api/v1/ngo/donors', [
        'name'  => 'Doador API',
        'email' => 'doador@teste.com',
        'type'  => 'individual',
    ]);

    $response->assertStatus(201)->assertJsonPath('data.name', 'Doador API');

    $this->assertDatabaseHas('ngo_donors', [
        'tenant_id' => $tenant->id,
        'name'      => 'Doador API',
    ]);
});

it('ngo donor creation requires name', function () {
    [, $token] = ngoApiUser();

    $this->withToken($token)
         ->postJson('/api/v1/ngo/donors', ['email' => 'test@test.com'])
         ->assertStatus(422)
         ->assertJsonValidationErrors(['name']);
});

// ── GET /api/v1/ngo/donors/{id} ───────────────────────────────────────────────

it('ngo can show single donor', function () {
    [$user, $token, $tenant] = ngoApiUser();

    $donor = NgoDonor::factory()->create(['tenant_id' => $tenant->id]);

    $this->withToken($token)
         ->getJson("/api/v1/ngo/donors/{$donor->id}")
         ->assertOk()
         ->assertJsonPath('data.id', $donor->id);
});

it('ngo donor cross-tenant returns 404', function () {
    [, $token] = ngoApiUser();

    $other = Tenant::factory()->create(['subscription_status' => 'active']);
    $donor = NgoDonor::factory()->create(['tenant_id' => $other->id]);

    $this->withToken($token)
         ->getJson("/api/v1/ngo/donors/{$donor->id}")
         ->assertStatus(404);
});

// ── GET /api/v1/ngo/grants ────────────────────────────────────────────────────

it('ngo grants list scoped to tenant', function () {
    [$user, $token, $tenant] = ngoApiUser();

    NgoGrant::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $response = $this->withToken($token)->getJson('/api/v1/ngo/grants');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

afterEach(fn () => Mockery::close());
