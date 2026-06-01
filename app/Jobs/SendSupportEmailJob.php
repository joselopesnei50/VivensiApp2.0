<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use App\Services\BrevoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSupportEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(
        private int    $ticketId,
        private bool   $isAdminReply,
        private string $replierName = ''
    ) {}

    public function handle(BrevoService $brevo): void
    {
        $ticket = SupportTicket::with('user')->find($this->ticketId);
        if (!$ticket) return;

        try {
            if ($this->isAdminReply) {
                // Admin respondeu → notifica o usuário do ticket
                $brevo->sendTicketReplyToUser($ticket->user, $ticket->id, $ticket->tenant_id);
            } else {
                // Usuário respondeu → notifica o admin
                $adminEmail = \App\Models\SystemSetting::getValue('email_from');
                if (!$adminEmail) return;

                $subject = $this->replierName
                    ? "Resposta no chamado #{$ticket->id}"
                    : $ticket->subject;

                $brevo->sendNewTicketToAdmin(
                    $adminEmail,
                    $this->replierName ?: ($ticket->user->name ?? 'Usuário'),
                    $subject,
                    $ticket->id,
                    $ticket->tenant_id
                );
            }
        } catch (\Throwable $e) {
            Log::warning("SendSupportEmailJob ticket #{$this->ticketId}: {$e->getMessage()}");
            throw $e;
        }
    }
}
