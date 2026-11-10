<?php

namespace App\Mail;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Pagamento confirmado: assinatura ativa e acesso liberado. */
class SubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public ?SubscriptionPlan $plan,
        public string $recipientName,
        public ?float $amount = null,
    ) {
    }

    public function build()
    {
        return $this->subject('Pagamento confirmado — sua assinatura Vivensi está ativa')
            ->view('emails.subscription_activated');
    }
}
