<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Confirmação do contrato de adesão assinado + próximo passo (pagamento ou acesso). */
class AdesaoSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Contract $contract,
        public ?SubscriptionPlan $plan,
        public string $recipientName,
    ) {
    }

    public function build()
    {
        return $this->subject('Contrato de adesão assinado — Vivensi')
            ->view('emails.adesao_signed');
    }
}
