<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DevController;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Dev Portal expõe schema/rotas internas — gate exige super admin com 2FA
 * ativo E verificado na sessão, acesso é auditado sem PII e a sessão expira.
 */
class DevPortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(array $extra = []): User
    {
        $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

        return User::factory()->create(array_merge([
            'tenant_id'               => $tenant->id,
            'role'                    => 'super_admin',
            'two_factor_confirmed_at' => now(),
            'email'                   => 'admin_' . uniqid() . '@example.com',
        ], $extra));
    }

    /** @test */
    public function gate_exige_super_admin(): void
    {
        $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
        $user   = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role'      => 'ngo',
            'email'     => 'ngo_' . uniqid() . '@example.com',
        ]);

        $this->actingAs($user)->get('/admin/dev/gate')->assertForbidden();
    }

    /** @test */
    public function gate_redireciona_admin_sem_2fa_ativo(): void
    {
        $admin = $this->superAdmin(['two_factor_confirmed_at' => null]);

        $this->actingAs($admin)
            ->get('/admin/dev/gate')
            ->assertRedirect(route('2fa.show'));
    }

    /** @test */
    public function gate_exige_sessao_2fa_verificada(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get('/admin/dev/gate')
            ->assertRedirect(route('2fa.challenge'));
    }

    /** @test */
    public function gate_abre_para_admin_com_2fa_verificado(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/dev/gate')
            ->assertOk();
    }

    /** @test */
    public function sessao_dev_expira_apos_30_minutos(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->withSession([
                '2fa_verified'         => true,
                'dev_authenticated'    => true,
                'dev_authenticated_at' => now()->subMinutes(31)->toDateTimeString(),
            ])
            ->get('/admin/dev')
            ->assertRedirect(route('admin.dev.gate'));
    }

    /** @test */
    public function senha_correta_autentica_e_audita_sem_ip_cru(): void
    {
        Log::spy();

        $admin = $this->superAdmin();
        SystemSetting::create(['key' => 'dev_page_password', 'value' => Hash::make('senha-dev')]);

        $response = $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post('/admin/dev/gate', ['dev_password' => 'senha-dev']);

        $response->assertRedirect(route('admin.dev.dashboard'));
        $this->assertTrue(session('dev_authenticated'));

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return str_contains($message, 'Dev Portal')
                && isset($context['ip_hash'])
                && !isset($context['ip'])
                && strlen($context['ip_hash']) === 64; // sha256, nunca IP cru
        })->once();
    }

    /** @test */
    public function schema_dump_oculta_colunas_de_credenciais_e_tokens(): void
    {
        $this->assertTrue(DevController::isSensitiveColumn('password'));
        $this->assertTrue(DevController::isSensitiveColumn('remember_token'));
        $this->assertTrue(DevController::isSensitiveColumn('two_factor_secret'));
        $this->assertTrue(DevController::isSensitiveColumn('two_factor_recovery_codes'));
        $this->assertTrue(DevController::isSensitiveColumn('cpf_bidx'));
        $this->assertTrue(DevController::isSensitiveColumn('instance_token_bidx'));
        $this->assertTrue(DevController::isSensitiveColumn('public_receipt_token'));

        $this->assertFalse(DevController::isSensitiveColumn('name'));
        $this->assertFalse(DevController::isSensitiveColumn('tenant_id'));
        $this->assertFalse(DevController::isSensitiveColumn('created_at'));
    }
}
