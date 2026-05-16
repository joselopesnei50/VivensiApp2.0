<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingBooking;
use App\Services\BrevoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingBookingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'confirmed');

        $bookings = MeetingBooking::when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN meeting_date >= CURDATE() THEN 0 ELSE 1 END")
            ->orderBy('meeting_date')
            ->orderBy('meeting_time')
            ->paginate(20);

        $counts = [
            'confirmed'  => MeetingBooking::where('status', 'confirmed')->count(),
            'cancelled'  => MeetingBooking::where('status', 'cancelled')->count(),
            'all'        => MeetingBooking::count(),
        ];

        $upcoming = MeetingBooking::where('status', 'confirmed')
            ->where('meeting_date', '>=', today())
            ->count();

        return view('admin.bookings.index', compact('bookings', 'status', 'counts', 'upcoming'));
    }

    public function updateStatus(Request $request, MeetingBooking $booking)
    {
        $request->validate(['status' => 'required|in:confirmed,cancelled']);

        $booking->update(['status' => $request->status]);

        return back()->with('success', 'Status atualizado.');
    }

    public function updateLink(Request $request, MeetingBooking $booking)
    {
        $request->validate([
            'meeting_link' => 'nullable|url|max:500',
            'admin_notes'  => 'nullable|string|max:500',
        ]);

        $booking->update([
            'meeting_link' => $request->meeting_link,
            'admin_notes'  => $request->admin_notes,
        ]);

        // Envia o link da reunião para o visitante por e-mail
        if ($request->filled('meeting_link') && $request->boolean('notify_guest')) {
            $this->sendLinkToGuest($booking);
        }

        return back()->with('success', 'Reunião atualizada' . ($request->boolean('notify_guest') && $request->filled('meeting_link') ? ' e link enviado ao participante.' : '.'));
    }

    private function sendLinkToGuest(MeetingBooking $booking): void
    {
        try {
            $date = Carbon::parse($booking->meeting_date)
                ->locale('pt_BR')
                ->isoFormat('dddd, D [de] MMMM [de] YYYY');

            $html = "<!doctype html><html lang='pt-br'><head><meta charset='utf-8'></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:Inter,Segoe UI,Arial,sans-serif;'>
<div style='max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;'>
  <div style='background:linear-gradient(135deg,#4f46e5,#3730a3);padding:32px;text-align:center;'>
    <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800;'>🎯 Link da sua Reunião</h1>
  </div>
  <div style='padding:32px;'>
    <p style='color:#475569;font-size:15px;margin:0 0 16px;'>Olá, <strong>{$booking->name}</strong>!</p>
    <p style='color:#475569;font-size:15px;margin:0 0 20px;'>O link para a sua reunião com a equipe Vivensi está pronto.</p>
    <div style='background:#f8fafc;border-radius:12px;padding:20px;border:1px solid #e2e8f0;margin-bottom:24px;'>
      <p style='margin:5px 0;'><strong>Data:</strong> {$date}</p>
      <p style='margin:5px 0;'><strong>Horário:</strong> {$booking->meeting_time} (Horário de Brasília)</p>
    </div>
    <div style='text-align:center;'>
      <a href='{$booking->meeting_link}' style='background:#4f46e5;color:#fff;padding:16px 32px;border-radius:10px;text-decoration:none;font-weight:800;font-size:16px;display:inline-block;'>
        🎥 Entrar na Reunião
      </a>
    </div>
    <p style='color:#94a3b8;font-size:12px;text-align:center;margin-top:20px;'>
      Guarde este e-mail para acessar o link no dia da reunião.
    </p>
  </div>
  <div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;'>
    Vivensi · Gestão com Propósito
  </div>
</div>
</body></html>";

            app(BrevoService::class)->sendEmail(
                $booking->email,
                $booking->name,
                "🎥 Link da sua reunião — {$booking->meeting_time} · Vivensi",
                $html
            );
        } catch (\Throwable $e) {
            Log::warning('sendLinkToGuest failed: ' . $e->getMessage());
        }
    }
}
