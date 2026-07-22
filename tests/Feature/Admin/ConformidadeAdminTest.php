<?php

namespace Tests\Feature\Admin;

use App\Models\RegraAvaliacao;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConformidadeAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $ngoUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);

        $adminTenant    = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'admin']);
        $this->superAdmin = User::factory()->create([
            'tenant_id'               => $adminTenant->id,
            'role'                    => 'super_admin',
            'two_factor_confirmed_at' => now(),
        ]);
        $this->ngoUser    = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
    }

    // ── GET /admin/conformidade ─────────────────────────────────────────────────

    public function test_index_renderiza_para_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
             ->withSession(['2fa_verified' => true])
             ->get(route('admin.conformidade.index'))
             ->assertStatus(200)
             ->assertSee('Thresholds Globais');
    }

    public function test_index_bloqueado_para_ngo(): void
    {
        $this->actingAs($this->ngoUser)
             ->get(route('admin.conformidade.index'))
             ->assertStatus(403);
    }

    // ── PUT /admin/conformidade/regra/{id} ──────────────────────────────────────

    public function test_update_threshold_editavel(): void
    {
        $regra = RegraAvaliacao::where('threshold_editavel_admin', true)->firstOrFail();
        $original = (float) $regra->threshold;
        $novo = $original >= 50 ? 25.0 : 30.0;

        $this->actingAs($this->superAdmin)
             ->withSession(['2fa_verified' => true])
             ->put(route('admin.conformidade.regra.update', $regra->id), [
                 'threshold' => $novo,
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertEquals($novo, (float) $regra->fresh()->threshold);
    }

    public function test_update_threshold_nao_editavel_retorna_404(): void
    {
        $regra = RegraAvaliacao::where('threshold_editavel_admin', false)
                               ->whereNotNull('threshold')
                               ->first();

        if (! $regra) {
            $this->markTestSkipped('Nenhuma regra com threshold_editavel_admin=false encontrada.');
        }

        $this->actingAs($this->superAdmin)
             ->withSession(['2fa_verified' => true])
             ->put(route('admin.conformidade.regra.update', $regra->id), [
                 'threshold' => 10,
             ])
             ->assertStatus(404);
    }

    public function test_update_threshold_valida_intervalo(): void
    {
        $regra = RegraAvaliacao::where('threshold_editavel_admin', true)->firstOrFail();

        $this->actingAs($this->superAdmin)
             ->withSession(['2fa_verified' => true])
             ->put(route('admin.conformidade.regra.update', $regra->id), [
                 'threshold' => 150,
             ])
             ->assertSessionHasErrors('threshold');
    }

    public function test_update_threshold_bloqueado_para_ngo(): void
    {
        $regra = RegraAvaliacao::where('threshold_editavel_admin', true)->firstOrFail();

        $this->actingAs($this->ngoUser)
             ->put(route('admin.conformidade.regra.update', $regra->id), [
                 'threshold' => 25,
             ])
             ->assertStatus(403);
    }
}
