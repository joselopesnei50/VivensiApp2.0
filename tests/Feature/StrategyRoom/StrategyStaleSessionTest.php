<?php

namespace Tests\Feature\StrategyRoom;

use App\Models\StrategySession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sala de Estrategia — autocorrecao de sessao orfa.
 *
 * Se o worker da queue morre no meio do debate (ex: restart do supervisor
 * durante deploy), a sessao fica em_andamento pra sempre e o polling da
 * pagina gira eterno. index/show/status curam sessoes com mais de
 * STALE_MINUTES marcando-as como concluida.
 */
class StrategyStaleSessionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['strategy_room.enabled' => true]);

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
        ]);
    }

    private function makeSession(string $status, int $minutesAgo): StrategySession
    {
        $session = StrategySession::create([
            'tenant_id'    => $this->tenant->id,
            'trigger_type' => 'manual_ui',
            'status'       => $status,
        ]);
        // created_at nao e fillable — ajusta depois do create.
        $session->created_at = now()->subMinutes($minutesAgo);
        $session->save();

        return $session;
    }

    /** @test */
    public function status_endpoint_cura_sessao_orfa(): void
    {
        $session = $this->makeSession('em_andamento', 60);

        $this->actingAs($this->user)
            ->get(route('strategy-room.status', $session))
            ->assertOk()
            ->assertJson(['status' => 'concluida', 'is_running' => false]);

        $this->assertSame('concluida', $session->fresh()->status);
    }

    /** @test */
    public function show_cura_sessao_orfa(): void
    {
        $session = $this->makeSession('em_andamento', 60);

        $this->actingAs($this->user)
            ->get(route('strategy-room.show', $session))
            ->assertOk();

        $this->assertSame('concluida', $session->fresh()->status);
    }

    /** @test */
    public function index_cura_orfas_em_lote(): void
    {
        $orfa1 = $this->makeSession('em_andamento', 30);
        $orfa2 = $this->makeSession('em_andamento', 120);

        $this->actingAs($this->user)
            ->get(route('strategy-room.index'))
            ->assertOk();

        $this->assertSame('concluida', $orfa1->fresh()->status);
        $this->assertSame('concluida', $orfa2->fresh()->status);
    }

    /** @test */
    public function sessao_recente_em_andamento_nao_e_curada(): void
    {
        $recente = $this->makeSession('em_andamento', 2);

        $this->actingAs($this->user)
            ->get(route('strategy-room.status', $recente))
            ->assertOk()
            ->assertJson(['status' => 'em_andamento', 'is_running' => true]);

        $this->assertSame('em_andamento', $recente->fresh()->status);
    }

    /** @test */
    public function cura_em_lote_nao_toca_sessao_de_outro_tenant(): void
    {
        $outroTenant = Tenant::factory()->create();
        $orfaAlheia  = StrategySession::create([
            'tenant_id'    => $outroTenant->id,
            'trigger_type' => 'manual_ui',
            'status'       => 'em_andamento',
        ]);
        $orfaAlheia->created_at = now()->subHour();
        $orfaAlheia->save();

        $this->actingAs($this->user)
            ->get(route('strategy-room.index'))
            ->assertOk();

        $this->assertSame('em_andamento', $orfaAlheia->fresh()->status);
    }
}
