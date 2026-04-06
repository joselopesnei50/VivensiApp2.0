<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $publicLink;
    public $code;
    public $recipientName;

    public function __construct(Contract $contract, $publicLink, $code, $recipientName)
    {
        $this->contract = $contract;
        $this->publicLink = $publicLink;
        $this->code = $code;
        $this->recipientName = $recipientName;
    }

    public function build()
    {
        return $this->subject('Confirmação de Assinatura: ' . $this->contract->title)
                    ->view('emails.contract_signed');
    }
}
