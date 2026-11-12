<?php

namespace App\Mail;

use App\Models\User;
use App\Support\TeamAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Convite para entrar na equipe da organização: a pessoa cria a própria senha pelo link (72 h). */
class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $orgName;
    public string $roleLabel;
    public string $roleDescription;

    public function __construct(public User $member, public User $invitedBy, public string $url, public ?string $projectName = null)
    {
        $this->orgName = (string) optional($member->tenant)->name;
        $this->roleLabel = TeamAccess::label($member->role);
        $this->roleDescription = TeamAccess::ROLE_DESCRIPTIONS[$member->role] ?? '';
        if ($member->role === 'credenciado') {
            $this->roleLabel = 'Acesso exclusivo ao projeto';
            $this->roleDescription = "Você verá só o projeto {$projectName}: suas tarefas, o diário e os marcos.";
        }
    }

    public function build()
    {
        return $this->subject("Convite para a equipe {$this->orgName} na Vivensi")
                    ->view('emails.team_invite');
    }
}
