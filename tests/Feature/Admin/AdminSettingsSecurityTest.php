<?php

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;

/**
 * Auditoria Seguranca 2026-07-11 (C2) — proteje contra regressao futura.
 *
 * Regra invariante: NENHUM secret armazenado no SystemSetting pode aparecer
 * no HTML renderizado de /admin/settings, mesmo dentro de <input type="password">.
 *
 * Ataque coberto: usuario com DevTools abre Elements, busca padrao "sk_" ou
 * "AIza" e copia a chave. Se este teste passar, nao consegue.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function spAdminForSettings(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
}

/**
 * @param  array<string,string>  $settings  key => secret_value
 */
function seedSettings(array $settings): void
{
    foreach ($settings as $key => $value) {
        SystemSetting::setValue($key, $value, 'api');
    }
}

it('nao expoe nenhum secret configurado no HTML da /admin/settings', function () {
    $admin = spAdminForSettings();

    // Semeia SystemSetting com valores facilmente detectaveis
    $secrets = [
        'deepseek_api_key'          => 'sk-deepseek-CANARY-SECRET-1234567890',
        'gemini_api_key'            => 'AIzaSyGeminiCANARY-9876543210',
        'together_ai_api_key'       => 'together-CANARY-abcdef123456',
        'unsplash_access_key'       => 'unsplash-CANARY-xyzuvw789',
        'serper_api_key'            => 'serper-CANARY-key-42',
        'google_maps_api_key'       => 'AIzaGoogleMapsCANARY',
        'openpix_app_id'            => 'openpix-CANARY-appid',
        'brevo_api_key'             => 'xkeysib-CANARY-brevo-key',
        'zapi_token'                => 'zapi-CANARY-token',
        'zapi_client_token'         => 'zapi-CANARY-clienttoken',
        'meta_app_secret'           => 'meta-CANARY-appsecret',
        'meta_social_app_secret'    => 'meta-social-CANARY',
        'abacatepay_api_key'        => 'abacatepay-CANARY-sk-live',
        'abacatepay_webhook_secret' => 'abacatepay-CANARY-whsec',
        'pusher_app_secret'         => 'pusher-CANARY-secret',
        'dev_page_password'         => 'devpage-CANARY-password',
    ];

    seedSettings($secrets);

    $response = $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get('/admin/settings')
        ->assertOk();

    $html = $response->getContent();

    foreach ($secrets as $key => $value) {
        expect($html)->not->toContain(
            $value,
            "Secret vazado no HTML: {$key} contem valor real. Verifique AdminSettingsController::index — deve retornar somente <key>_configured (bool), nunca o valor plaintext."
        );
    }
});

it('mostra badge "Configurada" quando secret ja esta setado', function () {
    $admin = spAdminForSettings();

    seedSettings([
        'deepseek_api_key' => 'sk-real-value',
        'brevo_api_key'    => 'xkeysib-real',
    ]);

    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('Configurada'); // badge de status
});

it('nao sobrescreve secret existente quando input vem vazio', function () {
    $admin = spAdminForSettings();

    // Semeia valor existente
    SystemSetting::setValue('deepseek_api_key', 'valor-original-nao-tocar', 'api');

    // Submete form sem informar a chave (simula usuario que so mudou email_from)
    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->post('/admin/settings', [
            'deepseek_api_key' => '',       // vazio!
            'email_from'       => 'foo@bar.com',
        ])
        ->assertRedirect();

    // Valor original preservado
    expect(SystemSetting::getValue('deepseek_api_key'))->toBe('valor-original-nao-tocar');
});

it('sobrescreve secret quando input traz valor novo', function () {
    $admin = spAdminForSettings();

    SystemSetting::setValue('deepseek_api_key', 'valor-antigo', 'api');

    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->post('/admin/settings', [
            'deepseek_api_key' => 'sk-novo-valor-substituto',
        ])
        ->assertRedirect();

    expect(SystemSetting::getValue('deepseek_api_key'))->toBe('sk-novo-valor-substituto');
});

it('dev_page_password e sempre armazenado como bcrypt hash', function () {
    $admin = spAdminForSettings();

    $this->actingAs($admin)->withSession(['2fa_verified' => true])
        ->post('/admin/settings', [
            'dev_page_password' => 'MinhaSenh@Forte123',
        ])
        ->assertRedirect();

    $stored = SystemSetting::getValue('dev_page_password');

    expect($stored)->not->toBe('MinhaSenh@Forte123');
    expect(\Illuminate\Support\Facades\Hash::check('MinhaSenh@Forte123', $stored))->toBeTrue();
});

it('bloqueia non-super_admin de acessar settings', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'manager',
    ]);

    // Middleware super_admin (EnsureSuperAdmin) barra com 403 antes de chegar no controller.
    $this->actingAs($user)
        ->get('/admin/settings')
        ->assertForbidden();
});

it('bloqueia non-super_admin de submeter settings via POST', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'manager',
    ]);

    $this->actingAs($user)
        ->post('/admin/settings', ['deepseek_api_key' => 'ataque'])
        ->assertForbidden();
});
