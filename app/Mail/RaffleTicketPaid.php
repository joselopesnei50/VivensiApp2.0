<?php

namespace App\Mail;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RaffleTicketPaid extends Mailable
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
        return $this->subject('Seu Bilhete da Sorte: ' . $this->raffle->title)
                    ->view('emails.raffle_ticket_paid');
    }
}
