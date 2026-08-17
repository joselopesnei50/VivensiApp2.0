<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao 2026-08-17: /admin/bot renderizava webhookUrl sem ?bot_token=,
 * o que fazia a Evolution API bater no endpoint com 401 silencioso. Fix:
 * inclui token na URL exibida quando services.whatsapp.bot_secret esta setado.
 */

uses(RefreshDatabase::class);

function spAdminBot(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
}

it('webhookUrl inclui ?bot_token= quando bot_secret esta configurado', function () {
    config(['services.whatsapp.bot_secret' => 'super-secret-token-abc-123']);

    $this->actingAs(spAdminBot())->withSession(['2fa_verified' => true])
        ->get('/admin/bot')
        ->assertOk()
        ->assertSee('/api/whatsapp/bot?bot_token=super-secret-token-abc-123', false);
});

it('webhookUrl sem query string quando bot_secret nao esta configurado', function () {
    config(['services.whatsapp.bot_secret' => null]);

    $response = $this->actingAs(spAdminBot())->withSession(['2fa_verified' => true])
        ->get('/admin/bot')
        ->assertOk();

    // Confere no input#webhookUrlInput especificamente — assertDontSee no body
    // inteiro daria falso positivo (Meta Pixel etc. mencionam query strings).
    $response->assertSee('value="' . config('app.url') . '/api/whatsapp/bot"', false);
});

it('bloqueia non-super_admin de acessar /admin/bot', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)->get('/admin/bot')->assertForbidden();
});
