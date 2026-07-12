<?php

namespace Tests\Feature;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rotas publicas de rifa devem operar apenas sobre rifas ativas.
 */
class PublicRaffleStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeRaffle(string $status): Raffle
    {
        $tenant = Tenant::factory()->create(['pix_key' => 'email@ong.org']);
        $raffle = Raffle::factory()->create([
            'tenant_id'     => $tenant->id,
            'status'        => $status,
            'ticket_price'  => 10.00,
            'total_tickets' => 1,
        ]);
        RaffleTicket::factory()->create([
            'raffle_id' => $raffle->id,
            'number'    => 1,
            'status'    => 'available',
        ]);
        return $raffle;
    }

    private function reservePayload(): array
    {
        return [
            'buyer_name'       => 'Comprador Teste',
            'buyer_email'      => 'comprador@teste.com',
            'buyer_phone'      => '11999999999',
            'selected_numbers' => [1],
        ];
    }

    /** @test */
    public function nao_permite_reservar_em_rifa_nao_ativa(): void
    {
        foreach (['draft', 'paused', 'finished', 'cancelled'] as $status) {
            $raffle = $this->makeRaffle($status);

            $this->post(route('public.raffle.reserve', $raffle->slug), $this->reservePayload())
                ->assertNotFound();

            $this->assertSame(
                'available',
                RaffleTicket::where('raffle_id', $raffle->id)->first()->status,
                "Bilhete de rifa '{$status}' nao deveria ser reservavel"
            );
        }
    }

    /** @test */
    public function permite_reservar_em_rifa_ativa(): void
    {
        $raffle = $this->makeRaffle('active');

        $this->post(route('public.raffle.reserve', $raffle->slug), $this->reservePayload());

        $this->assertSame(
            'pending',
            RaffleTicket::where('raffle_id', $raffle->id)->first()->status
        );
    }
}
