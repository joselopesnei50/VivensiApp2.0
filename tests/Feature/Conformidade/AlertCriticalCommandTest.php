<?php

namespace Tests\Feature\Conformidade;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConfig;
use App\Services\ComplianceCalculationService;
use App\Services\EvolutionApiService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlertCriticalCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);
    }

    private function ngoTenantComConfig(string $status = 'active'): Tenant
    {
        $tenant = Tenant::factory()->create(['subscription_status' => $status, 'type' => 'ngo']);
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role'      => 'ngo',
            'phone'     => '11999990001',
        ]);
        WhatsappConfig::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        return $tenant;
    }

    public function test_dry_run_nao_envia_mensagem(): void
    {
        $tenant = $this->ngoTenantComConfig();

        $this->mock(ComplianceCalculationService::class, function ($mock) {
            $mock->shouldReceive('dashboard')
                 ->andReturn(['indice_geral' => 30, 'avaliacoes' => [], 'pendencias' => [], 'indices_por_eixo' => [], 'ciclos' => []]);
            $mock->shouldReceive('invalidarCache')->never();
        });

        $this->mock(EvolutionApiService::class, function ($mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        $this->artisan('conformidade:alert-critical', ['--dry-run' => true])
             ->assertSuccessful();
    }

    public function test_tenant_acima_de_50_nao_recebe_alerta(): void
    {
        $tenant = $this->ngoTenantComConfig();

        $this->mock(ComplianceCalculationService::class, function ($mock) {
            $mock->shouldReceive('dashboard')
                 ->andReturn(['indice_geral' => 75, 'avaliacoes' => [], 'pendencias' => [], 'indices_por_eixo' => [], 'ciclos' => []]);
        });

        $this->mock(EvolutionApiService::class, function ($mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        $this->artisan('conformidade:alert-critical')
             ->assertSuccessful();
    }

    public function test_alerta_ja_enviado_hoje_nao_reenvia(): void
    {
        $tenant = $this->ngoTenantComConfig();
        $cacheKey = "conformidade.critical_alert.{$tenant->id}." . now()->format('Y-m-d');
        Cache::put($cacheKey, true, now()->addHour());

        $this->mock(ComplianceCalculationService::class, function ($mock) {
            $mock->shouldReceive('dashboard')
                 ->andReturn(['indice_geral' => 20, 'avaliacoes' => [], 'pendencias' => [], 'indices_por_eixo' => [], 'ciclos' => []]);
        });

        $this->mock(EvolutionApiService::class, function ($mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        $this->artisan('conformidade:alert-critical')
             ->assertSuccessful();
    }

    public function test_tenant_nao_ngo_e_ignorado(): void
    {
        $tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'common']);

        $this->mock(ComplianceCalculationService::class, function ($mock) {
            $mock->shouldNotReceive('dashboard');
        });

        $this->artisan('conformidade:alert-critical')
             ->assertSuccessful();
    }

    public function test_comando_aceita_tenant_especifico(): void
    {
        $tenant = $this->ngoTenantComConfig();

        $this->mock(ComplianceCalculationService::class, function ($mock) {
            $mock->shouldReceive('dashboard')
                 ->once()
                 ->andReturn(['indice_geral' => 80, 'avaliacoes' => [], 'pendencias' => [], 'indices_por_eixo' => [], 'ciclos' => []]);
        });

        $this->artisan('conformidade:alert-critical', ['--tenant' => $tenant->id, '--dry-run' => true])
             ->assertSuccessful();
    }
}
