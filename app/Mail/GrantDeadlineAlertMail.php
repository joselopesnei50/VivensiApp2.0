<?php

namespace App\Mail;

use App\Models\NgoGrant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GrantDeadlineAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public NgoGrant $grant;
    public User     $manager;
    public int      $daysLeft;

    public function __construct(NgoGrant $grant, User $manager, int $daysLeft)
    {
        $this->grant    = $grant;
        $this->manager  = $manager;
        $this->daysLeft = $daysLeft;
    }

    public function build(): self
    {
        $urgency = $this->daysLeft <= 1 ? '🚨 URGENTE' : '⚠️ Atenção';
        $subject = "{$urgency}: Edital \"{$this->grant->title}\" vence em {$this->daysLeft} dia(s)";

        return $this->subject($subject)
                    ->view('emails.grant_deadline_alert');
    }
}
