<?php

namespace App\Mail;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RaffleTicketReserved extends Mailable
{
    use Queueable, SerializesModels;

    public $raffle;
    public $tickets;
    public $pixPayload;
    public $totalAmount;
    public $orderUrl;

    public function __construct(Raffle $raffle, $tickets, string $pixPayload, float $totalAmount, ?string $orderUrl = null)
    {
        $this->orderUrl = $orderUrl;
        $this->raffle = $raffle;
        $this->tickets = $tickets;
        $this->pixPayload = $pixPayload;
        $this->totalAmount = $totalAmount;
    }

    public function build()
    {
        return $this->subject('Reserva Confirmada: ' . $this->raffle->title)
                    ->view('emails.raffle_ticket_reserved');
    }
}
