<?php

namespace App\Mail;

use App\Models\Raffle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RaffleReservationAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $raffle;
    public $tickets;
    public $buyerName;
    public $buyerPhone;
    public $totalAmount;

    public function __construct(Raffle $raffle, $tickets, string $buyerName, string $buyerPhone, float $totalAmount)
    {
        $this->raffle      = $raffle;
        $this->tickets     = $tickets;
        $this->buyerName   = $buyerName;
        $this->buyerPhone  = $buyerPhone;
        $this->totalAmount = $totalAmount;
    }

    public function build()
    {
        return $this->subject("🎟️ Nova Reserva na Rifa: {$this->raffle->title}")
                    ->view('emails.raffle_reservation_alert');
    }
}
