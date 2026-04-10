<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\RaffleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenPix\PhpSdk\Client as OpenPixClient;
use App\Mail\RaffleTicketReserved;
use Illuminate\Support\Facades\Mail;

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
        try {
            \Illuminate\Support\Facades\Log::info("Iniciando reserva para rifa: $slug", $request->all());
        } catch (\Exception $e) {}
        $raffle = Raffle::where('slug', $slug)->firstOrFail();
        $tenant = $raffle->tenant;

        // Ensure NGO has at least one payment method configured
        if (empty($tenant->pix_key) && empty($tenant->openpix_app_id)) {
            return back()->with('error', 'Esta organização ainda não configurou os meios de pagamento (PIX). Por favor, entre em contato com o suporte da ONG.');
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

            // ── Multi-Tenant Payment Integration ─────────────────────────────
            $tenant = $raffle->tenant;
            $totalAmount = count($selectedNumbers) * $raffle->ticket_price;
            $correlationID = 'raffle_' . $raffle->id . '_' . Str::random(10);
            
            $pixPayload = '';
            $qrCodeImage = null;
            $paymentMethod = 'static';

            // Check if Tenant has OpenPix configured (Automatic)
            if (!empty($tenant->openpix_app_id)) {
                try {
                    $openpix = \OpenPix\PhpSdk\Client::create($tenant->openpix_app_id);
                    $customer = [
                        "name"  => $request->buyer_name,
                        "email" => $request->buyer_email,
                        "phone" => $request->buyer_phone,
                    ];

                    $chargeData = [
                        "correlationID" => $correlationID,
                        "value"         => (int) ($totalAmount * 100),
                        "customer"      => $customer,
                        "comment"       => "Rifa: " . $raffle->title,
                    ];

                    $result = $openpix->charges()->create($chargeData);
                    $pixPayload = $result['charge']['brCode'] ?? '';
                    $qrCodeImage = $result['charge']['qrCodeImage'] ?? null;
                    $paymentMethod = $pixPayload ? 'dynamic' : 'static';
                } catch (\Exception $e) {
                    \Log::error("OpenPix Error (Tenant: {$tenant->id}): " . $e->getMessage());
                }
            }

            // Fallback: Static PIX
            if (empty($pixPayload)) {
                $pixPayload = $this->generatePixPayload($raffle, $totalAmount);
                $paymentMethod = 'static';
            }

            // Update tickets with correlationID (Done outside main transaction for safety)
            try {
                RaffleTicket::whereIn('id', $availableTickets->pluck('id'))->update([
                    'transaction_id' => $correlationID
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to update transaction_id: " . $e->getMessage());
            }

            // Send Email (Fails Silently to not break Checkout)
            try {
                Mail::to($request->buyer_email)->send(new RaffleTicketReserved($raffle, $availableTickets, $pixPayload, $totalAmount));
            } catch (\Exception $e) {
                \Log::error("Email Error on Reservation: " . $e->getMessage());
            }

            return view('public.raffles.checkout', [
                'raffle' => $raffle,
                'tenant' => $tenant,
                'tickets' => $availableTickets,
                'pixPayload' => $pixPayload,
                'qrCodeImage' => $qrCodeImage,
                'totalAmount' => $totalAmount,
                'paymentMethod' => $paymentMethod,
                'whatsappSupport' => $tenant->whatsapp_support ?? $tenant->support_phone ?? ''
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Erro crítico na reserva da rifa ($slug): " . $e->getMessage());
            return back()->with('error', 'Desculpe, ocorreu um erro ao processar sua reserva: ' . $e->getMessage());
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

        // Assemble payload without CRC (Lean version for maximum compatibility)
        $payload = '000201'            // Payload Format Indicator
            . $mai                      // Merchant Account Info
            . '52040000'               // Merchant Category Code
            . '5303986'                // Transaction Currency (BRL)
            . $tlv('54', $amountStr)   // Transaction Amount
            . '5802BR'                 // Country Code
            . $tlv('59', $merchantName) // Merchant Name
            . $tlv('60', $merchantCity) // Merchant City
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
