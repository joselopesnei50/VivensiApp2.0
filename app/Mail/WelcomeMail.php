<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $planName;

    public function __construct(User $user, $planName = 'Plano Básico')
    {
        $this->user = $user;
        $this->planName = $planName;
    }

    public function build()
    {
        return $this->subject('Bem-vindo à Revolução do Terceiro Setor - Vivensi')
                    ->view('emails.welcome');
    }
}
