<?php

namespace Tests\Feature;

use App\Jobs\ProcessEvolutionWebhook;
use App\Models\Raffle;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Fase 2 do hardening: BelongsToTenant é fail-closed — contexto web sem
 * autenticação não vê NENHUM registro tenant-scoped. Rotas públicas legítimas
 * (transparência, rifas, webhook Evolution) usam withoutGlobalScope('tenant')
 * explicitamente e continuam funcionando.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // PHPUnit roda em CLI e o scope se auto-desliga em console
        // (runningInConsole). Força o caminho HTTP para exercitar o
        // fail-closed exatamente como em produção.
        $prop = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $prop->setAccessible(true);
        $prop->setValue($this->app, false);
    }

    private function tenant(): Tenant
    {
        return Tenant::factory()->create(['subscription_status' => 'active']);
    }

    /** @test */
    public function guest_nao_ve_registros_tenant_scoped(): void
    {
        $tenant = $this->tenant();
        Raffle::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertSame(1, (int) Raffle::withoutGlobalScope('tenant')->count());
        $this->assertSame(0, (int) Raffle::count()); // fail-closed
    }

    /** @test */
    public function usuario_autenticado_ve_apenas_seu_tenant(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();
        $raffleA = Raffle::factory()->create(['tenant_id' => $tenantA->id]);
        Raffle::factory()->create(['tenant_id' => $tenantB->id]);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role'      => 'ngo',
            'email'     => 'iso_a_' . uniqid() . '@example.com',
        ]);

        $this->actingAs($userA);

        $this->assertSame(1, (int) Raffle::count());
        $this->assertSame((int) $raffleA->id, (int) Raffle::first()->id);
    }

    /** @test */
    public function super_admin_ve_todos_os_tenants(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();
        Raffle::factory()->create(['tenant_id' => $tenantA->id]);
        Raffle::factory()->create(['tenant_id' => $tenantB->id]);

        $admin = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role'      => 'super_admin',
            'email'     => 'iso_admin_' . uniqid() . '@example.com',
        ]);

        $this->actingAs($admin);

        $this->assertSame(2, (int) Raffle::count());
    }

    /** @test */
    public function bypass_explicito_continua_funcionando_sem_auth(): void
    {
        Raffle::factory()->create(['tenant_id' => $this->tenant()->id]);
        Raffle::factory()->create(['tenant_id' => $this->tenant()->id]);

        $this->assertSame(2, (int) Raffle::withoutGlobalScope('tenant')->count());
    }

    /** @test */
    public function creating_continua_preenchendo_tenant_id(): void
    {
        $tenant = $this->tenant();
        $user   = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role'      => 'ngo',
            'email'     => 'iso_create_' . uniqid() . '@example.com',
        ]);

        $this->actingAs($user);

        $raffle = Raffle::factory()->create(['tenant_id' => null]);

        $this->assertSame((int) $tenant->id, (int) $raffle->tenant_id);
    }

    /** @test */
    public function rota_publica_de_transparencia_segue_acessivel(): void
    {
        $tenant = $this->tenant();
        DB::table('transparency_portals')->insert([
            'tenant_id'    => $tenant->id,
            'slug'         => 'ong-teste',
            'title'        => 'ONG Teste',
            'is_published' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->get('/t/ong-teste')
            ->assertRedirect(route('transparency.portal', ['slug' => 'ong-teste']));
    }

    /** @test */
    public function webhook_evolution_localiza_instancia_com_fail_closed(): void
    {
        Queue::fake();

        $tenant = $this->tenant();
        $token  = 'iso_' . bin2hex(random_bytes(18));

        DB::table('whatsapp_instances')->insert([
            'tenant_id'           => $tenant->id,
            'instance_name'       => 'iso_instance_' . uniqid(),
            'instance_token'      => $token,
            'instance_token_bidx' => hash_hmac('sha256', $token, whatsapp_bidx_key()),
            'status'              => 'open',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->postJson("/api/evo/webhook/{$token}", ['event' => 'messages.upsert'])
            ->assertOk()
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessEvolutionWebhook::class);
    }
}
