<?php

use App\Models\BroadcastCampaign;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\DB;

/**
 * Broadcast — 3 fixes de observabilidade (2026-08-19).
 *
 *  1. WhatsappAuditLog::create aceita chat_id=null com wa_id em details
 *     -> antes o if (!empty($recipient->id)) engolia skips de numeros
 *     avulsos e cliente via "N ignorados" sem rastro.
 *
 *  2. Controller campaigns() retorna $skipReasons breakdown ->
 *     view mostra tooltip "40 sem opt-in, 5 opt-out" no lugar de
 *     "N ignorados" pelado.
 *
 *  3. sendBroadcast() flasha session('warning_optin') quando o cliente
 *     digita numeros avulsos e require_opt_in=1 -> avisa ANTES que ele
 *     descubra sozinho olhando o placar da campanha.
 */

function bobPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => 'Test Plan', 'target_audience' => 'ngo', 'price' => 100,
        'is_active' => true, 'is_courtesy' => false,
    ]);
}

function bobTenantWithConfig(bool $requireOptIn = true): Tenant
{
    $plan = bobPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'active',
    ]);
    WhatsappConfig::create([
        'tenant_id' => $tenant->id,
        'require_opt_in' => $requireOptIn,
    ]);
    WhatsappInstance::create([
        'tenant_id'      => $tenant->id,
        'instance_name'  => 'test_' . $tenant->id,
        'instance_token' => 'test-token-' . $tenant->id,
        'status'         => 'open',
    ]);
    return $tenant;
}

function bobUser(Tenant $tenant, string $role = 'ngo'): User
{
    return User::factory()->forTenant($tenant)->create([
        'role' => $role,
        'two_factor_confirmed_at' => now(),
        'two_factor_secret' => 'test-secret',
    ]);
}

function bobActingAs(User $user)
{
    return test()->actingAs($user)->withSession(['2fa_verified' => true]);
}

/** ────────────────────────────────────────────────────────────────
 *  Fix #1 — WhatsappAuditLog aceita chat_id null + wa_id em details
 *  (schema check: garante que a mudanca no Job nao vai bater 500)
 *  ──────────────────────────────────────────────────────────────── */
test('WhatsappAuditLog aceita chat_id=null com wa_id em details', function () {
    $tenant = bobTenantWithConfig();

    WhatsappAuditLog::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => null,
        'actor_type' => 'system',
        'event'      => 'broadcast_skipped',
        'details'    => [
            'reason'      => 'OPTIN_REQUIRED',
            'campaign_id' => 99,
            'wa_id'       => '5516997618695',
        ],
    ]);

    $log = WhatsappAuditLog::where('tenant_id', $tenant->id)
        ->where('event', 'broadcast_skipped')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->chat_id)->toBeNull();
    expect($log->details['wa_id'])->toBe('5516997618695');
    expect($log->details['reason'])->toBe('OPTIN_REQUIRED');
});

/** ────────────────────────────────────────────────────────────────
 *  Fix #2 — campaigns() retorna $skipReasons + view mostra breakdown
 *  ──────────────────────────────────────────────────────────────── */
test('GET /whatsapp/broadcast/campaigns renderiza breakdown de motivos', function () {
    $tenant = bobTenantWithConfig();
    $user   = bobUser($tenant, 'ngo');

    $campaign = BroadcastCampaign::create([
        'tenant_id'      => $tenant->id,
        'created_by'     => $user->id,
        'message'        => 'teste',
        'audience_type'  => 'selected',
        'phones'         => '5516997618695',
        'status'         => 'completed',
        'total_sent'     => 0,
        'total_failed'   => 0,
        'total_skipped'  => 3,
    ]);

    // Seed 3 skips com motivos diferentes pra ver o breakdown
    foreach ([
        ['OPTIN_REQUIRED', '5516111111111'],
        ['OPTIN_REQUIRED', '5516222222222'],
        ['CONTACT_OPTOUT', '5516333333333'],
    ] as [$reason, $wa]) {
        WhatsappAuditLog::create([
            'tenant_id'  => $tenant->id,
            'chat_id'    => null,
            'actor_type' => 'system',
            'event'      => 'broadcast_skipped',
            'details'    => ['reason' => $reason, 'campaign_id' => $campaign->id, 'wa_id' => $wa],
        ]);
    }

    $response = bobActingAs($user)->get('/whatsapp/broadcast/campaigns');

    $response->assertOk();
    // Breakdown visivel: "2 sem opt-in, 1 opt-out"
    $response->assertSee('sem opt-in');
    $response->assertSee('opt-out');
});

test('campaigns view sem skips nao quebra (skipReasons vazio)', function () {
    $tenant = bobTenantWithConfig();
    $user   = bobUser($tenant, 'ngo');

    BroadcastCampaign::create([
        'tenant_id'      => $tenant->id,
        'created_by'     => $user->id,
        'audience_type'  => 'all',
        'status'         => 'completed',
        'total_sent'     => 10,
        'total_failed'   => 0,
        'total_skipped'  => 0,
    ]);

    $response = bobActingAs($user)->get('/whatsapp/broadcast/campaigns');
    $response->assertOk();
});

/** ────────────────────────────────────────────────────────────────
 *  Fix #3 — sendBroadcast flasha warning_optin
 *  ──────────────────────────────────────────────────────────────── */
test('sendBroadcast avisa quando phones frios com require_opt_in=1', function () {
    $tenant = bobTenantWithConfig(requireOptIn: true);
    $user   = bobUser($tenant, 'ngo');

    // 2 numeros digitados, 0 cadastrados em whatsapp_chats -> 2 frios
    $response = bobActingAs($user)->post('/whatsapp/broadcast', [
        'message'  => 'ola',
        'audience' => 'selected',
        'phones'   => '5516997618695,5511955027871',
        'cadence'  => 3,
    ]);

    $response->assertSessionHas('warning_optin');
    $flash = session('warning_optin');
    expect($flash)->toContain('2 de 2');
});

test('sendBroadcast NAO avisa quando phones tem opt_in cadastrado', function () {
    $tenant = bobTenantWithConfig(requireOptIn: true);
    $user   = bobUser($tenant, 'ngo');

    // Cadastra ambos numeros com opt_in valido -> 0 frios
    WhatsappChat::create([
        'tenant_id' => $tenant->id, 'wa_id' => '5516997618695',
        'name' => 'A', 'opt_in_at' => now(),
    ]);
    WhatsappChat::create([
        'tenant_id' => $tenant->id, 'wa_id' => '5511955027871',
        'name' => 'B', 'opt_in_at' => now(),
    ]);

    $response = bobActingAs($user)->post('/whatsapp/broadcast', [
        'message'  => 'ola',
        'audience' => 'selected',
        'phones'   => '5516997618695,5511955027871',
        'cadence'  => 3,
    ]);

    $response->assertSessionMissing('warning_optin');
});

test('sendBroadcast NAO avisa quando require_opt_in=0', function () {
    $tenant = bobTenantWithConfig(requireOptIn: false);
    $user   = bobUser($tenant, 'ngo');

    $response = bobActingAs($user)->post('/whatsapp/broadcast', [
        'message'  => 'ola',
        'audience' => 'selected',
        'phones'   => '5516997618695',
        'cadence'  => 3,
    ]);

    $response->assertSessionMissing('warning_optin');
});
