<?php

namespace App\Jobs;

use App\Models\MeetingBooking;
use App\Services\BrevoService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMeetingEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(private int $bookingId) {}

    public function handle(BrevoService $brevo): void
    {
        $booking = MeetingBooking::find($this->bookingId);
        if (!$booking) return;

        $formattedDate = Carbon::parse($booking->meeting_date)
            ->locale('pt_BR')
            ->isoFormat('dddd, D [de] MMMM [de] YYYY');

        // E-mail de confirmação para o visitante
        try {
            $brevo->sendMeetingConfirmationEmail($booking, $formattedDate);
        } catch (\Throwable $e) {
            Log::warning("SendMeetingEmailsJob: confirmation email failed — {$e->getMessage()}");
        }

        // Alerta para a equipe
        try {
            $teamEmail = \App\Models\SystemSetting::getValue('email_from');
            $teamName  = \App\Models\SystemSetting::getValue('email_from_name', 'Vivensi');
            if (!$teamEmail) return;

            $phone = $booking->phone ? "<p style='margin:5px 0;'><strong>Telefone:</strong> {$booking->phone}</p>" : '';
            $notes = $booking->notes ? "<p style='margin:5px 0;'><strong>Observações:</strong> {$booking->notes}</p>" : '';

            $html = "<!doctype html><html lang='pt-br'><head><meta charset='utf-8'></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:Inter,Segoe UI,Arial,sans-serif;'>
<div style='max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;'>
  <div style='background:linear-gradient(135deg,#4f46e5,#3730a3);padding:32px;text-align:center;'>
    <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800;'>📅 Novo Agendamento</h1>
  </div>
  <div style='padding:32px;'>
    <p style='color:#475569;font-size:15px;margin:0 0 20px;'>Um visitante acabou de agendar uma reunião pela página pública.</p>
    <div style='background:#f8fafc;border-radius:12px;padding:20px;border:1px solid #e2e8f0;margin-bottom:24px;'>
      <p style='margin:5px 0;'><strong>Nome:</strong> {$booking->name}</p>
      <p style='margin:5px 0;'><strong>E-mail:</strong> {$booking->email}</p>
      {$phone}
      <p style='margin:5px 0;'><strong>Data:</strong> {$formattedDate}</p>
      <p style='margin:5px 0;'><strong>Horário:</strong> {$booking->meeting_time}</p>
      {$notes}
    </div>
    <div style='text-align:center;'>
      <a href='" . url('/admin/bookings') . "' style='background:#4f46e5;color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-block;'>
        Ver na Agenda do Painel
      </a>
    </div>
  </div>
  <div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;'>
    Notificação automática — Vivensi
  </div>
</div>
</body></html>";

            $brevo->sendEmail(
                $teamEmail,
                $teamName,
                "📅 Novo agendamento: {$booking->name} — {$formattedDate} às {$booking->meeting_time}",
                $html
            );
        } catch (\Throwable $e) {
            Log::warning("SendMeetingEmailsJob: team alert failed — {$e->getMessage()}");
        }
    }
}
