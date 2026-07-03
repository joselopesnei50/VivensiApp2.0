<?php

namespace Tests\Feature\StrategyRoom;

use App\Enums\StrategyRoomMode;
use App\Http\Controllers\StrategyRoomController;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sala de Estrategia — resolucao do modo (institucional|negocio) por tipo
 * de tenant e o casting da diretoria adaptado na UI.
 *
 * Regra: negocio so pra type common/personal (MEI/autonomo/PJ). Gestor
 * (type=business) usa Chamada e editais — fica institucional. Fallback
 * de type desconhecido/null e institucional (comportamento original).
 */
class StrategyRoomModeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tenant_ngo_resolve_institucional(): void
    {
        $tenant = Tenant::factory()->create(['type' => 'ngo']);

        $this->assertSame(StrategyRoomMode::Institucional, $tenant->strategyRoomMode());
    }

    /** @test */
    public function tenant_business_gestor_resolve_institucional(): void
    {
        $tenant = Tenant::factory()->create(['type' => 'business']);

        $this->assertSame(StrategyRoomMode::Institucional, $tenant->strategyRoomMode());
    }

    /** @test */
    public function tenant_common_e_personal_resolvem_negocio(): void
    {
        $common   = Tenant::factory()->create(['type' => 'common']);
        $personal = Tenant::factory()->create(['type' => 'personal']);

        $this->assertSame(StrategyRoomMode::Negocio, $common->strategyRoomMode());
        $this->assertSame(StrategyRoomMode::Negocio, $personal->strategyRoomMode());
    }

    /** @test */
    public function type_desconhecido_cai_no_fallback_institucional(): void
    {
        $tenant = Tenant::factory()->create(['type' => 'whatever']);

        $this->assertSame(StrategyRoomMode::Institucional, $tenant->strategyRoomMode());
    }

    /** @test */
    public function agents_meta_no_modo_negocio_adapta_maria_e_sofia(): void
    {
        $meta = StrategyRoomController::agentsMeta(StrategyRoomMode::Negocio);

        $this->assertSame('Mapa de Mercado', $meta['inteligencia']['sub_role']);
        $this->assertStringContainsString('clientes', $meta['inteligencia']['bio']);

        $this->assertSame('Diretora de Operações', $meta['programas']['role']);
        $this->assertSame('fa-gauge-high', $meta['programas']['icon']);
        $this->assertStringContainsString('teto anual do MEI', $meta['programas']['bio']);
    }

    /** @test */
    public function agents_meta_default_mantem_casting_institucional(): void
    {
        $meta = StrategyRoomController::agentsMeta();

        $this->assertSame('Mapa de Oportunidades', $meta['inteligencia']['sub_role']);
        $this->assertSame('Diretora de Programas', $meta['programas']['role']);
        $this->assertSame('fa-hands-holding-child', $meta['programas']['icon']);
    }
}
