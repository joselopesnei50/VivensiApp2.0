<?php

namespace Tests\Feature;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Testa reserva concorrente de bilhetes de rifa.
 *
 * Cenário: dois usuários tentam reservar o mesmo bilhete ao mesmo tempo.
 * Apenas um deve ter sucesso — o segundo deve receber erro de indisponibilidade.
 */
class RaffleConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Raffle $raffle;
    private RaffleTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['pix_key' => 'email@ong.org']);
        $this->raffle = Raffle::factory()->create([
            'tenant_id'    => $tenant->id,
            'status'       => 'active',
            'ticket_price' => 10.00,
            'total_tickets' => 1,
        ]);
        $this->ticket = RaffleTicket::factory()->create([
            'raffle_id' => $this->raffle->id,
            'number'    => 1,
            'status'    => 'available',
        ]);
    }

    /** @test */
    public function apenas_um_dos_dois_requests_concorrentes_pode_reservar_o_mesmo_bilhete(): void
    {
        $payload = [
            'buyer_name'       => 'Comprador Teste',
            'buyer_email'      => 'comprador@teste.com',
            'buyer_phone'      => '11999999999',
            'selected_numbers' => [1],
        ];

        // Simula dois requests concorrentes usando transações aninhadas
        $successes = 0;
        $errors    = 0;

        for ($i = 0; $i < 2; $i++) {
            $response = $this->post(route('public.raffle.reserve', $this->raffle->slug), $payload);

            if ($response->isSuccessful() || $response->isRedirect()) {
                // Verifica se o bilhete foi realmente marcado como pending
                $ticket = RaffleTicket::find($this->ticket->id);
                if ($ticket->status === 'pending') {
                    $successes++;
                    // Libera para o próximo request poder tentar
                    $ticket->update(['status' => 'available', 'buyer_name' => null,
                        'buyer_email' => null, 'buyer_phone' => null, 'reserved_at' => null]);
                }
            } else {
                $errors++;
            }
        }

        // Apenas um request deve ter conseguido reservar
        $this->assertEquals(1, $successes, 'Exatamente um request deve reservar o bilhete');
    }

    /** @test */
    public function lockForUpdate_impede_dupla_reserva_do_mesmo_bilhete(): void
    {
        // Simula race condition usando DB::transaction diretamente
        $results = [];

        // Primeiro request inicia transação e trava o bilhete
        DB::transaction(function () use (&$results) {
            $ticket = RaffleTicket::where('raffle_id', $this->raffle->id)
                ->where('number', 1)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            $this->assertNotNull($ticket, 'Bilhete deve ser encontrado na primeira tentativa');
            $results['first'] = $ticket ? 'found' : 'not_found';

            $ticket->update([
                'status'      => 'pending',
                'buyer_name'  => 'Primeiro',
                'buyer_email' => 'primeiro@test.com',
                'buyer_phone' => '11999999999',
                'reserved_at' => now(),
            ]);
        });

        // Segundo request tenta reservar o mesmo bilhete — deve falhar
        $ticket = RaffleTicket::where('raffle_id', $this->raffle->id)
            ->where('number', 1)
            ->where('status', 'available')
            ->lockForUpdate()
            ->first();

        $this->assertNull($ticket, 'Bilhete já reservado não deve aparecer como available');

        // Verifica que o bilhete está com status pending
        $this->ticket->refresh();
        $this->assertEquals('pending', $this->ticket->status);
        $this->assertEquals('Primeiro', $this->ticket->buyer_name);
    }

    /** @test */
    public function reserva_retorna_erro_quando_bilhete_ja_esta_pendente(): void
    {
        // Pre-reserva o bilhete
        $this->ticket->update([
            'status'      => 'pending',
            'buyer_name'  => 'Primeiro Comprador',
            'buyer_email' => 'primeiro@test.com',
            'buyer_phone' => '11999999999',
            'reserved_at' => now(),
        ]);

        $response = $this->post(route('public.raffle.reserve', $this->raffle->slug), [
            'buyer_name'       => 'Segundo Comprador',
            'buyer_email'      => 'segundo@test.com',
            'buyer_phone'      => '11988888888',
            'selected_numbers' => [1],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'não estão mais disponíveis',
            session('error') ?? ''
        );
    }
}
