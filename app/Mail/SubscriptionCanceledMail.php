<?php

namespace App\Mail;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Confirmação do cancelamento pedido pelo cliente. */
class SubscriptionCanceledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant, public Carbon $accessUntil, public bool $refund)
    {
    }

    public function build()
    {
        return $this->subject($this->refund
            ? 'Cancelamento confirmado — vamos devolver o valor integral'
            : 'Cancelamento confirmado — seu acesso vai até ' . $this->accessUntil->format('d/m/Y'))
            ->view('emails.subscription_canceled');
    }
}
