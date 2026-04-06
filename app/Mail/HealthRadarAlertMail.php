<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HealthRadarAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $project;
    public $currentScore;
    public $previousScore;

    public function __construct(Project $project, $currentScore, $previousScore)
    {
        $this->project = $project;
        $this->currentScore = $currentScore;
        $this->previousScore = $previousScore;
    }

    public function build()
    {
        return $this->subject("⚠️ Alerta de Saúde do Projeto: {$this->project->name}")
            ->view('emails.health_radar_alert')
            ->with([
                'drop' => $this->previousScore - $this->currentScore,
            ]);
    }
}
