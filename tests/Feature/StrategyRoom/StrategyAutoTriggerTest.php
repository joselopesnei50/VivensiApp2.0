<?php

namespace Tests\Feature\StrategyRoom;

use App\Console\Commands\StrategyAutoTrigger;
use App\Jobs\RunStrategyDebateJob;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\StrategySession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sala de Estrategia — Fase 3.1: trigger automatico por queda de health score.
 */
class StrategyAutoTriggerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Mail::fake();

        config([
            'strategy_room.enabled'                       => true,
            'strategy_room.auto_trigger'                  => true,
            'strategy_room.auto_trigger_drop_threshold'   => 10,
            'strategy_room.auto_trigger_cooldown_days'    => 7,
            'strategy_room.auto_trigger_global_daily_cap' => 20,
            'strategy_room.daily_quota'                   => 10,
        ]);

        $this->tenant  = Tenant::factory()->create();
        $this->user    = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
        ]);
        $this->project = Project::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'active',
        ]);
    }

    /** Snapshot de ontem + de hoje ja gravados — o comando so compara. */
    private function seedDrop(int $from = 80, int $to = 60): void
    {
        ProjectHealthHistory::withoutGlobalScopes()->insert([
            [
                'project_id'       => $this->project->id,
                'tenant_id'        => $this->tenant->id,
                'financial_score'  => $from,
                'execution_score'  => $from,
                'team_score'       => $from,
                'compliance_score' => $from,
                'overall_score'    => $from,
                'recorded_at'      => now()->subDay(),
                'created_at'       => now()->subDay(),
                'updated_at'       => now()->subDay(),
            ],
            [
                'project_id'       => $this->project->id,
                'tenant_id'        => $this->tenant->id,
                'financial_score'  => $to,
                'execution_score'  => $to,
                'team_score'       => $to,
                'compliance_score' => $to,
                'overall_score'    => $to,
                'recorded_at'      => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }

    private function autoSessions()
    {
        return StrategySession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('trigger_type', StrategyAutoTrigger::TRIGGER_TYPE);
    }

    /** @test */
    public function flag_desligada_nao_faz_nada(): void
    {
        config(['strategy_room.auto_trigger' => false]);
        $this->seedDrop();

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(0, $this->autoSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function queda_acima_do_threshold_convoca_reuniao_e_notifica(): void
    {
        $this->seedDrop(80, 60);

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $session = $this->autoSessions()->first();
        $this->assertNotNull($session);
        $this->assertSame('em_andamento', $session->status);

        Bus::assertDispatched(RunStrategyDebateJob::class);

        $notif = Notification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertNotNull($notif);
        $this->assertSame('Diretoria convocada automaticamente', $notif->title);
        $this->assertStringContainsString($this->project->name, $notif->message);
        $this->assertStringContainsString((string) $session->id, $notif->link);
    }

    /** @test */
    public function queda_abaixo_do_threshold_nao_convoca(): void
    {
        $this->seedDrop(80, 75); // -5 pts < threshold 10

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(0, $this->autoSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function grava_snapshot_de_hoje_quando_nao_existe(): void
    {
        // Sem snapshot nenhum: comando grava o primeiro (sem base pra comparar).
        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(1, ProjectHealthHistory::withoutGlobalScopes()
            ->where('project_id', $this->project->id)
            ->whereDate('recorded_at', today())
            ->count());
        $this->assertSame(0, $this->autoSessions()->count());
    }

    /** @test */
    public function cooldown_impede_nova_reuniao_automatica(): void
    {
        $anterior = StrategySession::create([
            'tenant_id'    => $this->tenant->id,
            'trigger_type' => StrategyAutoTrigger::TRIGGER_TYPE,
            'status'       => 'concluida',
        ]);
        $anterior->created_at = now()->subDays(3); // created_at nao e fillable
        $anterior->save();

        $this->seedDrop(80, 60);

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(1, $this->autoSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function cota_diaria_do_tenant_impede_convocacao(): void
    {
        config(['strategy_room.daily_quota' => 1]);
        StrategySession::create([
            'tenant_id'    => $this->tenant->id,
            'trigger_type' => 'manual_ui',
            'status'       => 'concluida',
        ]);
        $this->seedDrop(80, 60);

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(0, $this->autoSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function cap_global_diario_impede_convocacao(): void
    {
        config(['strategy_room.auto_trigger_global_daily_cap' => 1]);

        $outroTenant = Tenant::factory()->create();
        StrategySession::create([
            'tenant_id'    => $outroTenant->id,
            'trigger_type' => StrategyAutoTrigger::TRIGGER_TYPE,
            'status'       => 'concluida',
        ]);
        $this->seedDrop(80, 60);

        $this->artisan('strategy:auto-trigger')->assertSuccessful();

        $this->assertSame(0, $this->autoSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function dry_run_detecta_mas_nao_convoca_nem_notifica(): void
    {
        $this->seedDrop(80, 60);

        $this->artisan('strategy:auto-trigger --dry-run')->assertSuccessful();

        $this->assertSame(0, $this->autoSessions()->count());
        $this->assertSame(0, Notification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }
}
