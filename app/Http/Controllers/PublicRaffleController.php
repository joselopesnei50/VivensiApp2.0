<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\RaffleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\RaffleTicketReserved;
use App\Mail\RaffleReservationAlert;
use Illuminate\Support\Facades\Mail;

class PublicRaffleController extends Controller
{
    public function show(Request $request, $slug)
    {
        // withoutGlobalScopes: rota pública — slug único + status active isolam o registro
        $raffle = Raffle::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with(['tenant', 'tickets' => function($q) {
                $q->orderBy('number', 'asc');
            }])
            ->firstOrFail();

        // Track visit (throttled: 1 per IP per hour per raffle)
        RaffleVisit::record($raffle, $request);

        $pixConfigured = !empty($raffle->tenant->pix_key);
        return view('public.raffles.show', compact('raffle', 'pixConfigured'));
    }

    public function reserve(Request $request, $slug)
    {
        try {
            \Illuminate\Support\Facades\Log::info("Iniciando reserva para rifa: $slug");
        } catch (\Exception $e) {}
        $raffle = Raffle::withoutGlobalScope('tenant')->where('slug', $slug)->firstOrFail();
        $tenant = $raffle->tenant;

        // Ensure NGO has PIX configured
        if (empty($tenant->pix_key)) {
            return back()->with('error', 'Esta organização ainda não configurou a chave PIX. Por favor, entre em contato com o suporte da ONG.');
        }
        
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
                DB::rollBack();
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
            try {
                \Illuminate\Support\Facades\Log::info("Números reservados com sucesso para: " . $request->buyer_email);
            } catch (\Exception $e) {}

            // ── Payment: Static PIX ───────────────────────────────────────────
            $tenant = $raffle->tenant;
            $totalAmount = count($selectedNumbers) * $raffle->ticket_price;

            $pixPayload = $this->generatePixPayload($raffle, $totalAmount);

            // Send Email to buyer (Fails Silently to not break Checkout)
            try {
                Mail::to($request->buyer_email)->send(new RaffleTicketReserved($raffle, $availableTickets, $pixPayload, $totalAmount));
            } catch (\Exception $e) {
                \Log::error("Email Error on Reservation: " . $e->getMessage());
            }

            // Notify tenant owner about new reservation
            try {
                $tenantOwner = $tenant->users()->orderBy('id')->first();
                $notifyEmail = $tenant->report_email ?? ($tenantOwner?->email);
                if ($notifyEmail) {
                    Mail::to($notifyEmail)->send(new RaffleReservationAlert(
                        $raffle, $availableTickets,
                        $request->buyer_name, $request->buyer_phone, $totalAmount
                    ));
                }
            } catch (\Exception $e) {
                \Log::error("Tenant Notification Error on Reservation: " . $e->getMessage());
            }

            return view('public.raffles.checkout', [
                'raffle'          => $raffle,
                'tenant'          => $tenant,
                'tickets'         => $availableTickets,
                'pixPayload'      => $pixPayload,
                'totalAmount'     => $totalAmount,
                'whatsappSupport' => $tenant->whatsapp_support ?? $tenant->support_phone ?? '',
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Erro crítico na reserva da rifa ($slug): " . $e->getMessage());
            return back()->with('error', 'Desculpe, ocorreu um erro ao processar sua reserva. Tente novamente ou entre em contato com a organização.');
        }
    }

    /**
     * Handle receipt upload
     */
    public function uploadReceipt(Request $request, $ticketId)
    {
        $ticket = RaffleTicket::findOrFail($ticketId);

        $request->validate([
            'receipt'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'buyer_email'  => 'required|email',
        ]);

        // Validação de ownership: email do comprador deve coincidir com o bilhete
        if (strtolower(trim($ticket->buyer_email ?? '')) !== strtolower(trim($request->buyer_email))) {
            abort(403, 'Acesso negado: este bilhete não pertence ao e-mail informado.');
        }

        if ($ticket->status !== 'pending') {
            return back()->with('error', 'Este bilhete não está aguardando pagamento.');
        }
        if ($ticket->reserved_at && \Carbon\Carbon::parse($ticket->reserved_at)->lt(now()->subMinutes(30))) {
            return back()->with('error', 'Sua reserva expirou. Por favor, realize uma nova reserva.');
        }

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

        // ── Sanitization per BACEN/EMV Spec ──────────────────────────────────
        
        // Clean key: remove spaces. Emails must remain intact, others numeric-only or specific formats.
        $pixKey = trim($pixKey);
        
        // Normalize Merchant Name: MUST BE ASCII ONLY
        $merchantName = Str::ascii($tenant->name ?? 'EMPRESA');
        $merchantName = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $merchantName));
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

        // Additional Data Field (ID 62) — txid = '***' for static PIX (required by some banks)
        $additionalData = $tlv('62', $tlv('05', '***'));

        // Assemble payload — field 62 (txid='***') required by BACEN spec
        $payload = '000201'             // Payload Format Indicator
            . $mai                       // Merchant Account Info
            . '52040000'                // Merchant Category Code
            . '5303986'                 // Transaction Currency (BRL)
            . $tlv('54', $amountStr)    // Transaction Amount
            . '5802BR'                  // Country Code
            . $tlv('59', $merchantName) // Merchant Name
            . $tlv('60', $merchantCity) // Merchant City
            . $additionalData           // Additional Data (ID 62 — txid required)
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
