<?php

use App\Models\Tenant;
use App\Models\User;

function dashUser(string $role): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

// ── GET /dashboard — unauthenticated ─────────────────────────────────────────

it('dashboard redirects unauthenticated user to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

// ── Dashboard loads per role ──────────────────────────────────────────────────

it('common user sees their dashboard', function () {
    $user = dashUser('common');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk()->assertSee('Nova Transação');
});

it('manager user sees command center', function () {
    $user = dashUser('manager');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk()->assertSee('Centro de Comando');
});

it('ngo user sees impact dashboard header', function () {
    $user = dashUser('ngo');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk()->assertSee('Impacto');
});

// ── Subscription gate ─────────────────────────────────────────────────────────

it('super_admin is redirected from dashboard to admin panel', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    // super_admin sem 2FA e mandado pro /profile/2fa pelo RequireTwoFactor —
    // habilita 2FA + sessao verificada pra chegar no redirect do dashboard.
    $admin  = User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    $this->actingAs($admin);

    $response = $this->withSession(['2fa_verified' => true])->get('/dashboard');
    $response->assertRedirect('/admin');
});

// ── Bruce AI quick-action endpoints on dashboard ──────────────────────────────

it('bruce insight endpoint accessible from dashboard for authenticated user', function () {
    $user = dashUser('ngo');
    $this->actingAs($user);

    // Pre-seed context cache so it doesn't hit DB during insight generation
    \Illuminate\Support\Facades\Cache::put("bruce.ctx.{$user->tenant_id}", [
        'income' => 1000, 'expense' => 400, 'balance' => 600,
        'active_projects' => 2, 'open_tasks' => 5, 'overdue_tasks' => 1,
    ], 300);
    // Pre-seed daily insight so DeepSeek isn't called
    $cacheKey = "bruce.insight.{$user->tenant_id}." . now()->format('Y-m-d');
    \Illuminate\Support\Facades\Cache::put($cacheKey, 'Insight de teste para ONG.', 21600);

    $response = $this->getJson('/api/bruce/insight');
    $response->assertOk()->assertJsonStructure(['insight']);
});

// ── NGO quick donation modal form ────────────────────────────────────────────

it('ngo dashboard renders quick donation button', function () {
    $user = dashUser('ngo');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk()->assertSee('Registrar Doação');
});

// ── Manager dashboard renders quick task button ───────────────────────────────

it('manager dashboard renders quick task button', function () {
    $user = dashUser('manager');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk()->assertSee('Criar Tarefa Rápida');
});
