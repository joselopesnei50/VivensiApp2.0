<?php

namespace App\Mail;

use App\Models\Attachment;
use App\Models\RegraAvaliacao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentoVencendoMail extends Mailable
{
    use Queueable, SerializesModels;

    public User          $admin;
    public Attachment    $documento;
    public RegraAvaliacao $regra;
    public int           $diasRestantes;

    public function __construct(User $admin, Attachment $documento, RegraAvaliacao $regra, int $diasRestantes)
    {
        $this->admin         = $admin;
        $this->documento     = $documento;
        $this->regra         = $regra;
        $this->diasRestantes = $diasRestantes;
    }

    public function build(): self
    {
        $urgencia = $this->diasRestantes <= 0
            ? 'VENCIDO'
            : ($this->diasRestantes <= 7 ? 'URGENTE' : 'Atenção');

        $subject = "[Conformidade] {$urgencia}: documento \"{$this->regra->tipo_documento_obrigatorio}\" "
            . ($this->diasRestantes <= 0
                ? "está vencido"
                : "vence em {$this->diasRestantes} dia(s)");

        return $this->subject($subject)
                    ->view('emails.documento_vencendo');
    }
}
