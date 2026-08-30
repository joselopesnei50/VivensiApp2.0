<?php

namespace App\Http\Controllers;

use App\Jobs\SendMeetingEmailsJob;
use App\Models\MeetingBooking;
use App\Models\Notification;
use App\Models\User;
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

        // E-mails enviados em background (não bloqueiam o worker)
        SendMeetingEmailsJob::dispatch($booking->id);

        // Notificações no painel (DB write rápido — síncrono)
        MeetingBooking::notifyAdmins($booking, 'via página pública');

        return response()->json([
            'success' => true,
            'token'   => $booking->confirmation_token,
            'date'    => $formattedDate,
            'time'    => $booking->meeting_time,
            'name'    => $booking->name,
        ]);
    }

    /** Link de cancelamento (via e-mail).
     * Auditoria 2026-08-29 P3.b.3: lookup por bidx (HMAC) + hash_equals
     * no compare final. Backwards-compat: mesma URL, mesmo token — so
     * a comparacao ficou timing-safe e o token plaintext do DB para de
     * servir se um dump vazar (attacker precisa da app.key tambem).
     */
    public function cancel(string $token)
    {
        $bidx = MeetingBooking::hashToken($token);

        $booking = MeetingBooking::where('confirmation_token_bidx', $bidx)
            ->where('status', 'confirmed')
            ->firstOrFail();

        // Belt+suspenders contra colisao/bug de hash — compara plaintext em
        // tempo constante.
        abort_unless(hash_equals((string) $booking->confirmation_token, $token), 404);

        $booking->update(['status' => 'cancelled']);

        return view('booking.cancelled', compact('booking'));
    }

    // ─────────────────────────────────────────────────────────────────────
}
