<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StageOverdueAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public User       $manager;
    public Collection $stages;

    public function __construct(User $manager, Collection $stages)
    {
        $this->manager = $manager;
        $this->stages  = $stages;
    }

    public function build(): self
    {
        $count = $this->stages->count();
        return $this->subject("⚠️ {$count} etapa(s) de projeto em atraso")
                    ->view('emails.stage_overdue_alert');
    }
}
