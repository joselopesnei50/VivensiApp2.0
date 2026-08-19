<?php

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;

/**
 * Feature: super admin troca plano do tenant em /admin/tenants/{id}.
 *
 * Cobertura:
 *  - GET /admin/tenants/{id} renderiza (smoke test — pega undefined var/blade broken)
 *  - POST change-plan de cortesia -> pago: awaiting_contract + contract_signed_at reset
 *  - POST change-plan de pago -> cortesia SEM checkbox: active direto (admin bypassa)
 *  - POST change-plan mesmo plano COM checkbox: forca awaiting_contract
 *  - POST change-plan sem super_admin: 403
 */

function acpCourtesyPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'            => 'Cortesia Teste',
        'target_audience' => 'ngo',
        'price'           => 0,
        'is_active'       => true,
        'is_courtesy'     => true,
    ]);
}

function acpPaidPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'                  => 'Pago Teste',
        'target_audience'       => 'ngo',
        'price'                 => 199.90,
        'is_active'             => true,
        'is_courtesy'           => false,
        'abacatepay_product_id' => 'prod_test_acp',
    ]);
}

function acpSuperAdmin(): User
{
    return User::factory()->create([
        'role'                    => 'super_admin',
        'tenant_id'               => null,
        'two_factor_confirmed_at' => now(),
        'two_factor_secret'       => 'test-secret',
    ]);
}

/** Marca sessao como 2FA verificada — evita redirect pra /profile/2fa. */
function acpAsSuperAdmin(User $admin)
{
    return test()->actingAs($admin)->withSession(['2fa_verified' => true]);
}

test('GET /admin/tenants/{id} renderiza pra super admin', function () {
    $courtesy = acpCourtesyPlan();
    $tenant   = Tenant::factory()->create([
        'plan_id' => $courtesy->id,
        'subscription_status' => 'active',
    ]);
    User::factory()->forTenant($tenant)->create(['role' => 'ngo']);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->get("/admin/tenants/{$tenant->id}");

    $response->assertOk();
    $response->assertSee('Alterar Plano');
    $response->assertSee($courtesy->name);
});

test('change-plan cortesia -> pago vira awaiting_contract e zera contract_signed_at', function () {
    $courtesy = acpCourtesyPlan();
    $paid     = acpPaidPlan();
    $tenant   = Tenant::factory()->create([
        'plan_id' => $courtesy->id,
        'subscription_status' => 'active',
        'contract_signed_at' => now(),
    ]);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id' => $paid->id,
    ]);

    $response->assertRedirect();
    $tenant->refresh();
    expect((int) $tenant->plan_id)->toBe((int) $paid->id);
    expect($tenant->subscription_status)->toBe('awaiting_contract');
    expect($tenant->contract_signed_at)->toBeNull();
});

test('change-plan pago -> cortesia SEM checkbox vira active direto', function () {
    $courtesy = acpCourtesyPlan();
    $paid     = acpPaidPlan();
    $tenant   = Tenant::factory()->create([
        'plan_id' => $paid->id,
        'subscription_status' => 'active',
        'contract_signed_at' => now(),
    ]);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id' => $courtesy->id,
    ]);

    $response->assertRedirect();
    $tenant->refresh();
    expect((int) $tenant->plan_id)->toBe((int) $courtesy->id);
    expect($tenant->subscription_status)->toBe('active');
});

test('change-plan mesmo plano COM checkbox forca awaiting_contract', function () {
    $paid   = acpPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $paid->id,
        'subscription_status' => 'active',
        'contract_signed_at' => now(),
    ]);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id'         => $paid->id,
        'require_contract'=> '1',
    ]);

    $response->assertRedirect();
    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('awaiting_contract');
    expect($tenant->contract_signed_at)->toBeNull();
});

test('change-plan cortesia -> cortesia COM checkbox tambem forca awaiting_contract', function () {
    $courtesy1 = acpCourtesyPlan();
    $courtesy2 = SubscriptionPlan::create([
        'name' => 'Cortesia 2',
        'target_audience' => 'ngo',
        'price' => 0, 'is_active' => true, 'is_courtesy' => true,
    ]);
    $tenant = Tenant::factory()->create([
        'plan_id' => $courtesy1->id,
        'subscription_status' => 'active',
        'contract_signed_at' => now(),
    ]);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id'          => $courtesy2->id,
        'require_contract' => '1',
    ]);

    $response->assertRedirect();
    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('awaiting_contract');
});

test('change-plan sem super_admin retorna 403', function () {
    $paid   = acpPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => acpCourtesyPlan()->id,
        'subscription_status' => 'active',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $response = $this->actingAs($user)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id' => $paid->id,
    ]);

    $response->assertStatus(403);
});

test('change-plan com plano inativo devolve erro', function () {
    $courtesy = acpCourtesyPlan();
    $inactive = SubscriptionPlan::create([
        'name' => 'Inativo', 'target_audience' => 'ngo', 'price' => 100,
        'is_active' => false, 'is_courtesy' => false,
    ]);
    $tenant = Tenant::factory()->create([
        'plan_id' => $courtesy->id,
        'subscription_status' => 'active',
    ]);
    $admin = acpSuperAdmin();

    $response = acpAsSuperAdmin($admin)->post("/admin/tenants/{$tenant->id}/change-plan", [
        'plan_id' => $inactive->id,
    ]);

    $response->assertSessionHasErrors('plan_id');
});
