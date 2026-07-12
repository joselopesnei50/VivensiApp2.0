<?php

namespace App\Mail;

use App\Models\LgpdDataRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LgpdExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public LgpdDataRequest $request,
        public string $token,
    ) {}

    public function build()
    {
        $downloadUrl = url("/eu/dados/download/{$this->token}");
        $expiresAt   = $this->request->export_expires_at->format('d/m/Y H:i');

        return $this->subject('Seus dados estão prontos para download — LGPD')
            ->view('emails.lgpd.export_ready', [
                'userName'    => $this->user->name,
                'downloadUrl' => $downloadUrl,
                'expiresAt'   => $expiresAt,
            ]);
    }
}
