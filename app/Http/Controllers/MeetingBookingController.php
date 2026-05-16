<?php

namespace App\Http\Controllers;

use App\Models\MeetingBooking;
use App\Models\Notification;
use App\Models\User;
use App\Services\BrevoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingBookingController extends Controller
{
    /** Página pública de agendamento */
    public function index()
    {
        return view('booking.index');
    }

    /** API: retorna slots disponíveis para uma data */
    public function slots(Request $request)
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $slots = MeetingBooking::availableSlotsFor($request->date);

        return response()->json(['slots' => $slots]);
    }

    /** API: confirma o agendamento */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:120',
            'email'        => 'required|email|max:180',
            'phone'        => 'nullable|string|max:30',
            'notes'        => 'nullable|string|max:800',
            'meeting_date' => 'required|date|after_or_equal:today',
            'meeting_time' => 'required|date_format:H:i',
        ]);

        // Valida disponibilidade antes de salvar
        $available = MeetingBooking::availableSlotsFor($data['meeting_date']);
        if (!in_array($data['meeting_time'], $available)) {
            return response()->json([
                'error' => 'Este horário não está mais disponível. Por favor, escolha outro.',
            ], 422);
        }

        $booking = MeetingBooking::create($data);

        $formattedDate = Carbon::parse($booking->meeting_date)
            ->locale('pt_BR')
            ->isoFormat('dddd, D [de] MMMM [de] YYYY');

        // Envia e-mail de confirmação para o visitante
        app(BrevoService::class)->sendMeetingConfirmationEmail($booking, $formattedDate);

        // Alerta a equipe Vivensi por e-mail
        $this->alertTeamByEmail($booking, $formattedDate);

        // Notifica todos os super_admins no painel
        $this->notifyAdmins($booking);

        return response()->json([
            'success' => true,
            'token'   => $booking->confirmation_token,
            'date'    => $formattedDate,
            'time'    => $booking->meeting_time,
            'name'    => $booking->name,
        ]);
    }

    /** Link de cancelamento (via e-mail) */
    public function cancel(string $token)
    {
        $booking = MeetingBooking::where('confirmation_token', $token)
            ->where('status', 'confirmed')
            ->firstOrFail();

        $booking->update(['status' => 'cancelled']);

        return view('booking.cancelled', compact('booking'));
    }

    // ─────────────────────────────────────────────────────────────────────
    private function alertTeamByEmail(MeetingBooking $booking, string $formattedDate): void
    {
        try {
            $teamEmail = \App\Models\SystemSetting::getValue('email_from');
            $teamName  = \App\Models\SystemSetting::getValue('email_from_name', 'Vivensi');

            if (!$teamEmail) return;

            $adminUrl = url('/admin/settings'); // redireciona para bookings
            $phone    = $booking->phone ? "<p style='margin:5px 0;'><strong>Telefone:</strong> {$booking->phone}</p>" : '';
            $notes    = $booking->notes ? "<p style='margin:5px 0;'><strong>Observações:</strong> {$booking->notes}</p>" : '';

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

            app(BrevoService::class)->sendEmail(
                $teamEmail,
                $teamName,
                "📅 Novo agendamento: {$booking->name} — {$formattedDate} às {$booking->meeting_time}",
                $html
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('alertTeamByEmail failed: ' . $e->getMessage());
        }
    }

    private function notifyAdmins(MeetingBooking $booking): void
    {
        $admins = User::where('role', 'super_admin')->pluck('id');

        $date = Carbon::parse($booking->meeting_date)
            ->locale('pt_BR')
            ->isoFormat('D [de] MMMM');

        foreach ($admins as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'title'   => 'Novo Agendamento',
                'message' => "📅 {$booking->name} agendou uma reunião para {$date} às {$booking->meeting_time}",
                'type'    => 'booking',
                'link'    => '/admin/bookings',
                'read_at' => null,
            ]);
        }
    }
}
