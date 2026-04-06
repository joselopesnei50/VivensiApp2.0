<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\RaffleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicRaffleController extends Controller
{
    public function show(Request $request, $slug)
    {
        $raffle = Raffle::where('slug', $slug)
            ->where('status', 'active')
            ->with(['tenant', 'tickets' => function($q) {
                $q->orderBy('number', 'asc');
            }])
            ->firstOrFail();

        // Track visit (throttled: 1 per IP per hour per raffle)
        RaffleVisit::record($raffle, $request);

        return view('public.raffles.show', compact('raffle'));
    }

    public function reserve(Request $request, $slug)
    {
        $raffle = Raffle::where('slug', $slug)->firstOrFail();
        
        $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'required|string|max:20',
            'selected_numbers' => 'required|array|min:1',
        ]);

        $selectedNumbers = $request->selected_numbers;

        try {
            DB::beginTransaction();

            // Verify if numbers are still available
            $availableTickets = RaffleTicket::where('raffle_id', $raffle->id)
                ->whereIn('number', $selectedNumbers)
                ->where('status', 'available')
                ->lockForUpdate()
                ->get();

            if ($availableTickets->count() !== count($selectedNumbers)) {
                return back()->with('error', 'Alguns dos números selecionados não estão mais disponíveis. Por favor, tente outros.');
            }

            foreach ($availableTickets as $ticket) {
                $ticket->update([
                    'buyer_name' => $request->buyer_name,
                    'buyer_email' => $request->buyer_email,
                    'buyer_phone' => $request->buyer_phone,
                    'status' => 'pending',
                    'reserved_at' => now(),
                ]);
            }

            DB::commit();

            // Generate PIX Payload (Static for now based on Tenant's key)
            $pixPayload = $this->generatePixPayload($raffle, count($selectedNumbers) * $raffle->ticket_price);

            return view('public.raffles.checkout', [
                'raffle' => $raffle,
                'tickets' => $availableTickets,
                'pixPayload' => $pixPayload,
                'totalAmount' => count($selectedNumbers) * $raffle->ticket_price,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocorreu um erro ao reservar seus números. Tente novamente.');
        }
    }

    /**
     * Handle receipt upload
     */
    public function uploadReceipt(Request $request, $ticketId)
    {
        $ticket = RaffleTicket::findOrFail($ticketId);
        if ($ticket->status !== 'pending') {
            return back()->with('error', 'Este bilhete não está aguardando pagamento.');
        }
        if ($ticket->reserved_at && \Carbon\Carbon::parse($ticket->reserved_at)->lt(now()->subMinutes(30))) {
            return back()->with('error', 'Sua reserva expirou. Por favor, realize uma nova reserva.');
        }
        
        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('raffle_receipts', 'public');
            $ticket->update([
                'payment_receipt_path' => $path,
            ]);
        }

        return back()->with('success', 'Comprovante enviado com sucesso! Aguarde a validação da nossa equipe.');
    }

    /**
     * BRCode (PIX Static) Generator — EMV QRCPS Merchant Presented Mode.
     * Implements the full CRC16-CCITT as required by the Brazilian Central Bank specification.
     */
    private function generatePixPayload(Raffle $raffle, float $amount): string
    {
        $tenant   = $raffle->tenant;
        $pixKey   = $tenant->pix_key ?? null;

        if (!$pixKey) {
            return 'Chave PIX não configurada pela organização.';
        }

        // Sanitize fields per BACEN spec (ASCII, max 25 chars for name, 15 for city)
        $merchantName = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $tenant->name ?? 'EMPRESA'));
        $merchantName = substr(trim($merchantName), 0, 25) ?: 'EMPRESA';
        $merchantCity = 'SAO PAULO';
        $amountStr    = number_format($amount, 2, '.', '');

        // ── Field builders ────────────────────────────────────────────────────
        $tlv = fn(string $id, string $value): string =>
            $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;

        // ID 26 — Merchant Account Information (GUI + key)
        $gui        = 'br.gov.bcb.pix';
        $pixKeyField = $tlv('01', $pixKey);
        $mai         = $tlv('26', $tlv('00', $gui) . $pixKeyField);

        // Additional Data Field (ID 62) — txid = '***' for static PIX
        $additionalData = $tlv('62', $tlv('05', '***'));

        // Assemble payload without CRC
        $payload = '000201'            // Payload Format Indicator
            . $mai                      // Merchant Account Info
            . '52040000'               // Merchant Category Code
            . '5303986'                // Transaction Currency (BRL)
            . $tlv('54', $amountStr)   // Transaction Amount
            . '5802BR'                 // Country Code
            . $tlv('59', $merchantName) // Merchant Name
            . $tlv('60', $merchantCity) // Merchant City
            . $additionalData           // Additional Data Field
            . '6304';                   // CRC placeholder (value appended below)

        // ── CRC16-CCITT (polynomial 0x1021, init 0xFFFF) ─────────────────────
        $crc  = 0xFFFF;
        $data = $payload;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return $payload . strtoupper(sprintf('%04X', $crc));
    }
}
