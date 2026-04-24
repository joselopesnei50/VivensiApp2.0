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
        $numbers = collect($this->tickets)->map(fn($t) => '#' . str_pad($t->number, 2, '0', STR_PAD_LEFT))->implode(', ');

        return $this->subject("🎟️ Nova Reserva na Rifa: {$this->raffle->title}")
                    ->html("
                        <h2 style='color:#4f46e5;'>Nova Reserva na Rifa!</h2>
                        <p><strong>Rifa:</strong> {$this->raffle->title}</p>
                        <p><strong>Comprador:</strong> {$this->buyerName}</p>
                        <p><strong>WhatsApp:</strong> {$this->buyerPhone}</p>
                        <p><strong>Números:</strong> {$numbers}</p>
                        <p><strong>Total:</strong> R$ " . number_format($this->totalAmount, 2, ',', '.') . "</p>
                        <p style='color:#64748b;font-size:0.9rem;'>O comprador recebeu o código PIX por e-mail. Aguarde o comprovante de pagamento.</p>
                    ");
    }
}
