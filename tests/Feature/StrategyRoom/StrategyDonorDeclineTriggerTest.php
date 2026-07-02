<?php

namespace Tests\Feature\StrategyRoom;

use App\Console\Commands\StrategyAutoTrigger;
use App\Console\Commands\StrategyDonorDeclineTrigger;
use App\Jobs\RunStrategyDebateJob;
use App\Models\NgoDonor;
use App\Models\Notification;
use App\Models\StrategySession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Sala de Estrategia — trigger automatico por doador recorrente em declinio.
 *
 * Recorrente = doou em >= 3 meses distintos nos ultimos 6 meses.
 * Declinio = ultima doacao ha mais de 45 dias.
 */
class StrategyDonorDeclineTriggerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        config([
            'strategy_room.enabled'                       => true,
            'strategy_room.auto_trigger_donor'            => true,
            'strategy_room.donor_recurring_min_months'    => 3,
            'strategy_room.donor_decline_days'            => 45,
            'strategy_room.auto_trigger_cooldown_days'    => 7,
            'strategy_room.auto_trigger_global_daily_cap' => 20,
            'strategy_room.daily_quota'                   => 10,
        ]);

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
        ]);
    }

    private function makeDonor(?int $tenantId = null): NgoDonor
    {
        return NgoDonor::create([
            'tenant_id' => $tenantId ?? $this->tenant->id,
            'name'      => 'Doador Fulano Sigiloso',
        ]);
    }

    private function donate(NgoDonor $donor, string $date): void
    {
        Transaction::create([
            'tenant_id'    => $donor->tenant_id,
            'ngo_donor_id' => $donor->id,
            'description'  => 'Doacao teste',
            'amount'       => 100.00,
            'type'         => 'income',
            'date'         => $date,
            'status'       => 'paid',
        ]);
    }

    /**
     * Dia 15 de N meses atras — evita overflow de fim de mes do Carbon e
     * garante meses distintos.
     */
    private function midOfMonthsAgo(int $months): string
    {
        return now()->startOfMonth()->subMonths($months)->addDays(14)->toDateString();
    }

    /** Doou em 3 meses distintos, ultima ha ~2,5 meses: recorrente em declinio. */
    private function seedDecliningDonor(?int $tenantId = null): NgoDonor
    {
        $donor = $this->makeDonor($tenantId);
        $this->donate($donor, $this->midOfMonthsAgo(5));
        $this->donate($donor, $this->midOfMonthsAgo(4));
        $this->donate($donor, $this->midOfMonthsAgo(3));

        return $donor;
    }

    private function donorSessions()
    {
        return StrategySession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('trigger_type', StrategyDonorDeclineTrigger::TRIGGER_TYPE);
    }

    /** @test */
    public function flag_desligada_nao_faz_nada(): void
    {
        config(['strategy_room.auto_trigger_donor' => false]);
        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function recorrente_em_declinio_convoca_reuniao_e_notifica_sem_pii(): void
    {
        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $session = $this->donorSessions()->first();
        $this->assertNotNull($session);
        $this->assertSame('em_andamento', $session->status);

        Bus::assertDispatched(RunStrategyDebateJob::class);

        $notif = Notification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('1 doador recorrente', $notif->message);
        $this->assertStringContainsString((string) $session->id, $notif->link);
        // LGPD: nunca nome de doador na notificacao.
        $this->assertStringNotContainsString('Fulano', $notif->title . ' ' . $notif->message);
    }

    /** @test */
    public function doador_nao_recorrente_nao_convoca(): void
    {
        // So 2 meses distintos (< 3), mesmo parado ha meses.
        $donor = $this->makeDonor();
        $this->donate($donor, $this->midOfMonthsAgo(5));
        $this->donate($donor, $this->midOfMonthsAgo(4));

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function recorrente_ativo_nao_convoca(): void
    {
        // 3 meses distintos, mas ultima doacao ha 10 dias (< 45): saudavel.
        $donor = $this->makeDonor();
        $this->donate($donor, $this->midOfMonthsAgo(2));
        $this->donate($donor, $this->midOfMonthsAgo(1));
        $this->donate($donor, now()->subDays(10)->toDateString());

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function cooldown_impede_nova_reuniao(): void
    {
        $anterior = StrategySession::create([
            'tenant_id'    => $this->tenant->id,
            'trigger_type' => StrategyDonorDeclineTrigger::TRIGGER_TYPE,
            'status'       => 'concluida',
        ]);
        $anterior->created_at = now()->subDays(3); // created_at nao e fillable
        $anterior->save();

        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(1, $this->donorSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function cap_global_e_compartilhado_com_trigger_de_health(): void
    {
        config(['strategy_room.auto_trigger_global_daily_cap' => 1]);

        // Reuniao de HEALTH hoje (outro tenant) ja consome o cap compartilhado.
        StrategySession::create([
            'tenant_id'    => Tenant::factory()->create()->id,
            'trigger_type' => StrategyAutoTrigger::TRIGGER_TYPE,
            'status'       => 'concluida',
        ]);

        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
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
        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function dry_run_detecta_mas_nao_convoca_nem_notifica(): void
    {
        $this->seedDecliningDonor();

        $this->artisan('strategy:donor-decline-trigger --dry-run')->assertSuccessful();

        $this->assertSame(0, $this->donorSessions()->count());
        $this->assertSame(0, Notification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function doador_de_outro_tenant_nao_convoca_para_este(): void
    {
        $outroTenant = Tenant::factory()->create();
        $this->seedDecliningDonor($outroTenant->id);

        $this->artisan('strategy:donor-decline-trigger')->assertSuccessful();

        // Convoca pro outro tenant, nunca pro tenant deste teste.
        $this->assertSame(0, $this->donorSessions()->count());
        $this->assertSame(1, StrategySession::withoutGlobalScopes()
            ->where('tenant_id', $outroTenant->id)
            ->where('trigger_type', StrategyDonorDeclineTrigger::TRIGGER_TYPE)
            ->count());
    }
}
