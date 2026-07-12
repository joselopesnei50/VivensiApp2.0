<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;

/**
 * Fase 5.1 — Dashboard admin /admin/whatsapp-billing.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function spAdminBilling(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
}

function regularUserBilling(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

// ── access control ────────────────────────────────────────────────────────────

it('bloqueia acesso a nao-super_admin', function () {
    $this->actingAs(regularUserBilling())
        ->get('/admin/whatsapp-billing')
        ->assertStatus(403);
});

it('renderiza dashboard vazio quando nao ha conversas', function () {
    $this->actingAs(spAdminBilling())->withSession(['2fa_verified' => true])
        ->get('/admin/whatsapp-billing')
        ->assertOk()
        ->assertSee('Consumo WhatsApp Cloud API')
        ->assertSee('Conversas faturáveis');
});

// ── aggregation ───────────────────────────────────────────────────────────────

it('agrega custo total corretamente por periodo', function () {
    $admin    = spAdminBilling();
    $instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $admin->tenant_id]);

    // 3 conversas nos ultimos 30 dias
    WhatsappConversation::factory()->count(3)->create([
        'tenant_id'            => $admin->tenant_id,
        'whatsapp_instance_id' => $instance->id,
        'category'             => 'utility',
        'cost_usd_micros'      => 8000,
        'is_billable'          => true,
        'started_at'           => now()->subDays(5),
    ]);

    // 1 conversa fora do periodo
    WhatsappConversation::factory()->create([
        'tenant_id'            => $admin->tenant_id,
        'whatsapp_instance_id' => $instance->id,
        'category'             => 'marketing',
        'cost_usd_micros'      => 62_500,
        'is_billable'          => true,
        'started_at'           => now()->subDays(95),
    ]);

    $response = $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get('/admin/whatsapp-billing?days=30');

    $response->assertOk();
    // 3 conversas x 8000 = 24000 micros = $0.024
    $response->assertSee('$0.0240');
});

it('exclui conversas nao-faturaveis do agregado', function () {
    $admin    = spAdminBilling();
    $instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $admin->tenant_id]);

    WhatsappConversation::factory()->create([
        'tenant_id'            => $admin->tenant_id,
        'whatsapp_instance_id' => $instance->id,
        'category'             => 'utility',
        'cost_usd_micros'      => 8000,
        'is_billable'          => true,
        'started_at'           => now(),
    ]);

    WhatsappConversation::factory()->create([
        'tenant_id'            => $admin->tenant_id,
        'whatsapp_instance_id' => $instance->id,
        'category'             => 'service',
        'cost_usd_micros'      => 0,
        'is_billable'          => false,
        'started_at'           => now(),
    ]);

    $response = $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get('/admin/whatsapp-billing?days=30');

    $response->assertOk();
    $response->assertSee('$0.0080');
});

it('filtra por tenant_id quando fornecido', function () {
    $admin       = spAdminBilling();
    $tenantOutro = Tenant::factory()->create();
    $instance1   = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $admin->tenant_id]);
    $instance2   = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $tenantOutro->id]);

    WhatsappConversation::factory()->create([
        'tenant_id'            => $admin->tenant_id,
        'whatsapp_instance_id' => $instance1->id,
        'is_billable'          => true,
        'cost_usd_micros'      => 8000,
        'started_at'           => now(),
    ]);

    WhatsappConversation::factory()->create([
        'tenant_id'            => $tenantOutro->id,
        'whatsapp_instance_id' => $instance2->id,
        'is_billable'          => true,
        'cost_usd_micros'      => 62_500,
        'started_at'           => now(),
    ]);

    // Sem filtro: soma os dois (8000 + 62500 = 70500 micros = $0.0705)
    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get('/admin/whatsapp-billing?days=30')
        ->assertSee('$0.0705');

    // Com filtro do admin: só o de 8000
    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get("/admin/whatsapp-billing?days=30&tenant_id={$admin->tenant_id}")
        ->assertSee('$0.0080');
});
