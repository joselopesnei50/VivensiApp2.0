<?php

namespace Tests\Feature\StrategyRoom;

use App\Jobs\RunStrategyDebateJob;
use App\Models\KanbanCard;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Sala de Estrategia — Fase 3: decisao do Chefe → card no Kanban + cota diaria.
 */
class StrategyRoomTaskTest extends TestCase
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

    private function makeConcludedSession(array $overrides = []): StrategySession
    {
        return StrategySession::create(array_merge([
            'tenant_id'       => $this->tenant->id,
            'trigger_type'    => 'manual_ui',
            'status'          => 'concluida',
            'proposed_action' => [
                'titulo'    => 'Revisar orçamento do projeto Horta',
                'descricao' => 'Olga apontou despesa acima do previsto.',
            ],
        ], $overrides));
    }

    /** @test */
    public function cria_card_no_kanban_a_partir_da_decisao(): void
    {
        $session = $this->makeConcludedSession();

        $this->actingAs($this->user)
            ->post(route('strategy-room.create-task', $session))
            ->assertRedirect(route('manager.kanban.index'));

        $card = KanbanCard::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->first();

        $this->assertNotNull($card);
        $this->assertSame('Revisar orçamento do projeto Horta', $card->title);
        $this->assertStringContainsString('Olga apontou despesa acima do previsto.', $card->description);
        $this->assertSame('strategy_room', $card->meta['source'] ?? null);
        $this->assertSame($session->id, $card->meta['strategy_session_id'] ?? null);
        $this->assertSame($card->id, (int) $session->fresh()->kanban_card_id);
    }

    /** @test */
    public function nao_duplica_card_para_a_mesma_sessao(): void
    {
        $session = $this->makeConcludedSession();

        $this->actingAs($this->user)->post(route('strategy-room.create-task', $session));
        $this->actingAs($this->user)->post(route('strategy-room.create-task', $session))
            ->assertRedirect(route('manager.kanban.index'));

        $this->assertSame(1, KanbanCard::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function nao_cria_card_com_sessao_em_andamento(): void
    {
        $session = $this->makeConcludedSession(['status' => 'em_andamento', 'proposed_action' => null]);

        $this->actingAs($this->user)
            ->post(route('strategy-room.create-task', $session))
            ->assertSessionHas('error');

        $this->assertSame(0, KanbanCard::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function usa_fala_do_chefe_como_fallback_sem_proposed_action(): void
    {
        $session = $this->makeConcludedSession(['proposed_action' => null]);

        StrategyMessage::create([
            'tenant_id'           => $this->tenant->id,
            'strategy_session_id' => $session->id,
            'agent'               => 'estrategista_chefe',
            'content'             => 'Priorizar a inscrição no edital municipal. O Financeiro confirmou caixa positivo.',
            'facts_used'          => ['financeiro'],
            'confidence'          => 'media',
        ]);

        $this->actingAs($this->user)->post(route('strategy-room.create-task', $session));

        $card = KanbanCard::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($card);
        $this->assertSame('Priorizar a inscrição no edital municipal.', $card->title);
    }

    /** @test */
    public function outro_tenant_nao_cria_card(): void
    {
        $session = $this->makeConcludedSession();

        $outsider = User::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'role'      => 'ngo',
        ]);

        $this->actingAs($outsider)
            ->post(route('strategy-room.create-task', $session))
            ->assertForbidden();
    }

    /** @test */
    public function cota_diaria_bloqueia_novo_debate(): void
    {
        Bus::fake();
        config(['strategy_room.daily_quota' => 1]);

        $this->makeConcludedSession();

        $this->actingAs($this->user)
            ->post(route('strategy-room.store'))
            ->assertRedirect(route('strategy-room.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, StrategySession::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        Bus::assertNotDispatched(RunStrategyDebateJob::class);
    }

    /** @test */
    public function debate_passa_quando_cota_nao_atingida(): void
    {
        Bus::fake();
        config(['strategy_room.daily_quota' => 5]);

        $this->actingAs($this->user)
            ->post(route('strategy-room.store'))
            ->assertSessionHas('success');

        Bus::assertDispatched(RunStrategyDebateJob::class);
    }
}
