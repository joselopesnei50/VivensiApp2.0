<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mails da cobrança recorrente. $kind:
 *  nova | lembrete | vencida | bloqueio | paga
 */
class InvoiceNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public const SUBJECTS = [
        'nova'     => 'Sua fatura Vivensi está disponível',
        'lembrete' => 'Lembrete: sua fatura Vivensi vence amanhã',
        'vencida'  => 'Sua fatura Vivensi venceu',
        'bloqueio' => 'Acesso restrito: fatura Vivensi em atraso',
        'paga'     => 'Pagamento recebido — obrigado!',
    ];

    public function __construct(
        public Invoice $invoice,
        public Tenant $tenant,
        public string $kind,
        public string $recipientName,
    ) {
    }

    public function build()
    {
        return $this->subject(self::SUBJECTS[$this->kind] ?? 'Fatura Vivensi')
            ->view('emails.invoice_notice');
    }
}
