<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso para a ONG: reservas de rifa perto de vencer, ainda sem pagamento confirmado.
 */
class RaffleReservationsExpiring extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, array{raffle:string, buyer:string, phone:?string, numbers:string, total:float, expires:string}> $orders */
    public function __construct(public Tenant $tenant, public array $orders)
    {
    }

    public function build()
    {
        $n = count($this->orders);
        return $this->subject($n > 1 ? "{$n} reservas de rifa vencem em breve" : 'Uma reserva de rifa vence em breve')
                    ->view('emails.raffle_reservations_expiring');
    }
}
