<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $publicLink;

    public function __construct(Contract $contract, $publicLink)
    {
        $this->contract = $contract;
        $this->publicLink = $publicLink;
    }

    public function build()
    {
        return $this->subject('Solicitação de Assinatura: ' . $this->contract->title)
                    ->view('emails.contract_request');
    }
}
