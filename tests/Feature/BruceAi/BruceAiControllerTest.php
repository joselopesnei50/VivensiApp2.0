<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\BruceAiService;
use Illuminate\Support\Facades\Cache;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeTenantUser(string $role = 'common'): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

// ── POST /api/bruce/chat ──────────────────────────────────────────────────────

it('bruce chat requires authentication', function () {
    $response = $this->postJson('/api/bruce/chat', ['message' => 'Olá']);
    $response->assertStatus(401);
});

it('bruce chat requires message field', function () {
    $user = makeTenantUser();
    $this->actingAs($user);

    $response = $this->postJson('/api/bruce/chat', []);
    $response->assertStatus(422)->assertJsonValidationErrors(['message']);
});

it('bruce chat rejects message over 2000 chars', function () {
    $user = makeTenantUser();
    $this->actingAs($user);

    $response = $this->postJson('/api/bruce/chat', ['message' => str_repeat('a', 2001)]);
    $response->assertStatus(422)->assertJsonValidationErrors(['message']);
});

it('bruce chat returns reply from mocked service', function () {
    $user = makeTenantUser('manager');
    $this->actingAs($user);

    $mock = Mockery::mock(BruceAiService::class);
    $mock->shouldReceive('chat')->once()->andReturn([
        'reply'     => 'Seu saldo está positivo.',
        'tokens'    => 30,
        'timestamp' => now()->toIso8601String(),
    ]);
    $this->app->instance(BruceAiService::class, $mock);

    $response = $this->postJson('/api/bruce/chat', ['message' => 'Como está meu saldo?']);
    $response->assertOk()->assertJsonStructure(['reply', 'tokens', 'timestamp']);
    expect($response->json('reply'))->toBe('Seu saldo está positivo.');
});

it('bruce chat returns 503 when service errors', function () {
    $user = makeTenantUser();
    $this->actingAs($user);

    $mock = Mockery::mock(BruceAiService::class);
    $mock->shouldReceive('chat')->once()->andReturn(['error' => 'API indisponível']);
    $this->app->instance(BruceAiService::class, $mock);

    $response = $this->postJson('/api/bruce/chat', ['message' => 'Teste']);
    $response->assertStatus(503)->assertJson(['error' => 'API indisponível']);
});

// ── DELETE /api/bruce/chat/history ────────────────────────────────────────────

it('bruce clear history requires authentication', function () {
    $response = $this->deleteJson('/api/bruce/chat/history');
    $response->assertStatus(401);
});

it('bruce clear history returns success', function () {
    $user = makeTenantUser();
    $this->actingAs($user);

    $mock = Mockery::mock(BruceAiService::class);
    $mock->shouldReceive('clearHistory')->once()->with($user->tenant_id);
    $this->app->instance(BruceAiService::class, $mock);

    $response = $this->deleteJson('/api/bruce/chat/history');
    $response->assertOk()->assertJson(['success' => true]);
});

// ── GET /api/bruce/insight ────────────────────────────────────────────────────

it('bruce insight requires authentication', function () {
    $response = $this->getJson('/api/bruce/insight');
    $response->assertStatus(401);
});

it('bruce insight returns insight string', function () {
    $user = makeTenantUser('ngo');
    $this->actingAs($user);

    $mock = Mockery::mock(BruceAiService::class);
    $mock->shouldReceive('dailyInsight')->once()->andReturn('Suas doações cresceram 15% este mês.');
    $this->app->instance(BruceAiService::class, $mock);

    $response = $this->getJson('/api/bruce/insight');
    $response->assertOk()->assertJsonStructure(['insight']);
    expect($response->json('insight'))->toContain('doações');
});

afterEach(fn () => Mockery::close());
