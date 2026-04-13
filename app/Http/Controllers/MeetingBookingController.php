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

        // Envia e-mail de confirmação via Brevo API (SystemSettings)
        app(BrevoService::class)->sendMeetingConfirmationEmail($booking, $formattedDate);

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
