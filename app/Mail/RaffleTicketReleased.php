<?php

namespace App\Mail;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RaffleTicketReleased extends Mailable
{
    use Queueable, SerializesModels;

    public $raffle;
    public $ticket;   // primeiro número (compatibilidade com o template)
    public $tickets;  // todos os números da mesma compra

    /**
     * Create a new message instance.
     *
     * @return void
     */
    /** @param RaffleTicket|\Illuminate\Support\Collection $tickets */
    public function __construct(Raffle $raffle, $tickets)
    {
        $this->raffle  = $raffle;
        $this->tickets = $tickets instanceof RaffleTicket ? collect([$tickets]) : collect($tickets)->values();
        $this->ticket  = $this->tickets->first();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Sua reserva foi cancelada: ' . $this->raffle->title)
                    ->view('emails.raffle_ticket_released');
    }
}
