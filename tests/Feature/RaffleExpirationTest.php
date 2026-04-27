<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupRaffleReservations;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa expiração de reservas de bilhetes e idempotência do comando de limpeza.
 */
class RaffleExpirationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function comando_libera_bilhetes_com_reserva_expirada(): void
    {
        $tenant = Tenant::factory()->create();
        $raffle = Raffle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);

        // Bilhete expirado (reservado há 31 minutos)
        $expiredTicket = RaffleTicket::factory()->create([
            'raffle_id'   => $raffle->id,
            'number'      => 1,
            'status'      => 'pending',
            'buyer_name'  => 'Comprador Expirado',
            'buyer_email' => 'exp@test.com',
            'buyer_phone' => '11999999999',
            'reserved_at' => Carbon::now()->subMinutes(31),
        ]);

        // Bilhete válido (reservado há 10 minutos — dentro do prazo)
        $validTicket = RaffleTicket::factory()->create([
            'raffle_id'   => $raffle->id,
            'number'      => 2,
            'status'      => 'pending',
            'buyer_name'  => 'Comprador Válido',
            'buyer_email' => 'valid@test.com',
            'buyer_phone' => '11988888888',
            'reserved_at' => Carbon::now()->subMinutes(10),
        ]);

        // Bilhete pago — nunca deve ser liberado
        $paidTicket = RaffleTicket::factory()->create([
            'raffle_id'   => $raffle->id,
            'number'      => 3,
            'status'      => 'paid',
            'buyer_name'  => 'Comprador Pago',
            'buyer_email' => 'paid@test.com',
            'buyer_phone' => '11977777777',
            'reserved_at' => Carbon::now()->subHours(2),
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        // Bilhete expirado deve ser liberado
        $expiredTicket->refresh();
        $this->assertEquals('available', $expiredTicket->status);
        $this->assertNull($expiredTicket->buyer_name);
        $this->assertNull($expiredTicket->buyer_email);
        $this->assertNull($expiredTicket->reserved_at);

        // Bilhete válido NÃO deve ser liberado
        $validTicket->refresh();
        $this->assertEquals('pending', $validTicket->status);
        $this->assertEquals('Comprador Válido', $validTicket->buyer_name);

        // Bilhete pago NUNCA deve ser liberado
        $paidTicket->refresh();
        $this->assertEquals('paid', $paidTicket->status);
        $this->assertEquals('Comprador Pago', $paidTicket->buyer_name);
    }

    /** @test */
    public function comando_e_idempotente_executado_multiplas_vezes(): void
    {
        $tenant = Tenant::factory()->create();
        $raffle = Raffle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);

        $ticket = RaffleTicket::factory()->create([
            'raffle_id'   => $raffle->id,
            'number'      => 1,
            'status'      => 'pending',
            'buyer_name'  => 'Comprador',
            'buyer_email' => 'x@test.com',
            'buyer_phone' => '11999999999',
            'reserved_at' => Carbon::now()->subMinutes(31),
        ]);

        // Executar 3 vezes — o resultado deve ser o mesmo
        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);
        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);
        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('available', $ticket->status);
        $this->assertNull($ticket->buyer_name);
    }

    /** @test */
    public function bilhete_expirado_pode_ser_reservado_novamente(): void
    {
        $tenant = Tenant::factory()->create(['pix_key' => 'pix@ong.org']);
        $raffle = Raffle::factory()->create([
            'tenant_id'    => $tenant->id,
            'status'       => 'active',
            'ticket_price' => 10.00,
        ]);

        RaffleTicket::factory()->create([
            'raffle_id'   => $raffle->id,
            'number'      => 1,
            'status'      => 'pending',
            'buyer_name'  => 'Expirado',
            'buyer_email' => 'exp@test.com',
            'buyer_phone' => '11999999999',
            'reserved_at' => Carbon::now()->subMinutes(31),
        ]);

        // Roda a limpeza
        $this->artisan('raffles:cleanup-reservations');

        // Tenta nova reserva — deve funcionar
        $response = $this->post(route('public.raffle.reserve', $raffle->slug), [
            'buyer_name'       => 'Novo Comprador',
            'buyer_email'      => 'novo@test.com',
            'buyer_phone'      => '11988888888',
            'selected_numbers' => [1],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('raffle_tickets', [
            'raffle_id'  => $raffle->id,
            'number'     => 1,
            'status'     => 'pending',
            'buyer_name' => 'Novo Comprador',
        ]);
    }
}
