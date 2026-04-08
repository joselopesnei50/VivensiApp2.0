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
    public $ticket;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Raffle $raffle, RaffleTicket $ticket)
    {
        $this->raffle = $raffle;
        $this->ticket = $ticket;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Aviso de Reserva: Bilhete #' . str_pad($this->ticket->number, 3, '0', STR_PAD_LEFT) . ' - ' . $this->raffle->title)
                    ->view('emails.raffle_ticket_released');
    }
}
