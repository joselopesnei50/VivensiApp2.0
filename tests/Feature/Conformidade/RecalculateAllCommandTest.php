<?php

namespace Tests\Feature\Conformidade;

use App\Jobs\RecalcularConformidadeJob;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComplianceCalculationService;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateAllCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);
    }

    public function test_comando_despacha_jobs_para_tenants_ngo(): void
    {
        Queue::fake();

        $ngo1 = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $ngo2 = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'common']); // deve ser ignorado

        $this->artisan('conformidade:recalculate-all')
             ->assertSuccessful();

        Queue::assertPushed(RecalcularConformidadeJob::class, 2);
    }

    public function test_dry_run_nao_despacha_jobs(): void
    {
        Queue::fake();

        Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);

        $this->artisan('conformidade:recalculate-all', ['--dry-run' => true])
             ->assertSuccessful();

        Queue::assertNotPushed(RecalcularConformidadeJob::class);
    }

    public function test_tenant_inativo_e_ignorado(): void
    {
        Queue::fake();

        Tenant::factory()->create(['subscription_status' => 'suspended', 'type' => 'ngo']);

        $this->artisan('conformidade:recalculate-all')
             ->assertSuccessful();

        Queue::assertNotPushed(RecalcularConformidadeJob::class);
    }

    public function test_opção_tenant_processa_apenas_um(): void
    {
        Queue::fake();

        $ngo1 = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $ngo2 = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);

        $this->artisan('conformidade:recalculate-all', ['--tenant' => $ngo1->id])
             ->assertSuccessful();

        Queue::assertPushed(RecalcularConformidadeJob::class, 1);
    }
}
