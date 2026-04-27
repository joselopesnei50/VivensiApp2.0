<?php

namespace Tests\Unit;

use App\Console\Commands\CleanupRaffleReservations;
use App\Models\RaffleTicket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários para o comando de limpeza de reservas expiradas.
 * Foca em edge cases: bilhetes sem reserved_at, bilhetes pagos, exatamente no limite.
 */
class RaffleCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bilhete_pending_sem_reserved_at_nao_e_liberado(): void
    {
        $ticket = RaffleTicket::factory()->create([
            'status'      => 'pending',
            'reserved_at' => null, // sem data de reserva
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status);
    }

    /** @test */
    public function bilhete_reservado_exatamente_no_limite_nao_e_liberado(): void
    {
        // Reservado há exatamente 30 minutos — ainda no prazo
        $ticket = RaffleTicket::factory()->create([
            'status'      => 'pending',
            'reserved_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status);
    }

    /** @test */
    public function bilhete_reservado_um_segundo_apos_limite_e_liberado(): void
    {
        // Reservado há 30 minutos e 1 segundo
        $ticket = RaffleTicket::factory()->create([
            'status'      => 'pending',
            'buyer_name'  => 'Expirado',
            'buyer_email' => 'exp@test.com',
            'buyer_phone' => '11999999999',
            'reserved_at' => Carbon::now()->subMinutes(30)->subSecond(),
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('available', $ticket->status);
        $this->assertNull($ticket->buyer_name);
        $this->assertNull($ticket->buyer_email);
        $this->assertNull($ticket->buyer_phone);
        $this->assertNull($ticket->reserved_at);
    }

    /** @test */
    public function bilhete_pago_nunca_e_liberado_mesmo_com_reserved_at_antigo(): void
    {
        $ticket = RaffleTicket::factory()->create([
            'status'      => 'paid',
            'buyer_name'  => 'Pago',
            'buyer_email' => 'pago@test.com',
            'reserved_at' => Carbon::now()->subHours(5),
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('paid', $ticket->status);
        $this->assertEquals('Pago', $ticket->buyer_name);
    }

    /** @test */
    public function bilhete_disponivel_nao_e_alterado(): void
    {
        $ticket = RaffleTicket::factory()->create([
            'status'      => 'available',
            'reserved_at' => null,
        ]);

        $this->artisan('raffles:cleanup-reservations')->assertExitCode(0);

        $ticket->refresh();
        $this->assertEquals('available', $ticket->status);
    }
}
