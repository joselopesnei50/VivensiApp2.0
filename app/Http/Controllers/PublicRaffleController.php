<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\RaffleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\PixPayload;
use App\Mail\RaffleTicketReserved;
use App\Mail\RaffleReservationAlert;
use Illuminate\Support\Facades\Mail;

class PublicRaffleController extends Controller
{
    public function show(Request $request, $slug)
    {
        // withoutGlobalScopes: rota pública — slug único + status active isolam o registro
        // Rifa sorteada continua no ar, mostrando o resultado (sem vender).
        $raffle = Raffle::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->whereIn('status', ['active', 'finished'])
            ->with(['tenant', 'winner', 'tickets' => function($q) {
                $q->select('id', 'raffle_id', 'number', 'status')->orderBy('number', 'asc');
            }])
            ->firstOrFail();

        // Track visit (throttled: 1 per IP per hour per raffle)
        RaffleVisit::record($raffle, $request);

        $pixConfigured = !empty($raffle->tenant->pix_key);
        return view('public.raffles.show', compact('raffle', 'pixConfigured'));
    }

    public function reserve(Request $request, $slug)
    {
        $raffle = Raffle::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
        $tenant = $raffle->tenant;

        // Ensure NGO has PIX configured
        if (empty($tenant->pix_key)) {
            return back()->with('error', 'Esta organização ainda não configurou a chave PIX. Por favor, entre em contato com o suporte da ONG.');
        }
        
        $max = RaffleTicket::MAX_PER_RESERVATION;
        $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'required|string|max:20',
            'selected_numbers' => "required|array|min:1|max:{$max}",
            'selected_numbers.*' => 'integer|min:1|distinct',
        ], [
            'selected_numbers.max' => "Você pode reservar até {$max} números por vez.",
            'selected_numbers.required' => 'Escolha pelo menos um número.',
        ]);

        $selectedNumbers = array_map('intval', $request->selected_numbers);

        // Limite por comprador: o mesmo e-mail não segura mais que $max números pendentes nesta rifa.
        $alreadyPending = RaffleTicket::where('raffle_id', $raffle->id)
            ->where('status', 'pending')
            ->where('buyer_email', $request->buyer_email)
            ->count();
        if ($alreadyPending + count($selectedNumbers) > $max) {
            return back()->withInput()->with('error', "Você já tem {$alreadyPending} números reservados aguardando pagamento. O limite é {$max} por pessoa — pague os anteriores ou escolha menos números.");
        }
        $token = Str::random(40);

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
                    'reservation_token' => $token,
                    'reminder_sent_at' => null,
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
            $orderUrl   = route('public.raffle.order', [$raffle->slug, $token]);

            // Send Email to buyer (Fails Silently to not break Checkout)
            try {
                Mail::to($request->buyer_email)->send(new RaffleTicketReserved($raffle, $availableTickets, $pixPayload, $totalAmount, $orderUrl));
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

            // Página do pedido em GET: pode ser atualizada, reaberta pelo e-mail ou compartilhada pelo comprador.
            return redirect()->to($orderUrl);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Erro crítico na reserva da rifa ($slug): " . $e->getMessage());
            return back()->with('error', 'Desculpe, ocorreu um erro ao processar sua reserva. Tente novamente ou entre em contato com a organização.');
        }
    }

    /**
     * Página do pedido (PIX e situação dos números). O token de 40 caracteres é o acesso:
     * quem tem o link (o comprador, pelo e-mail) vê o pedido.
     */
    public function order(string $slug, string $token)
    {
        $raffle = Raffle::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->whereIn('status', ['active', 'finished'])
            ->with('tenant')
            ->firstOrFail();

        $tickets = RaffleTicket::where('raffle_id', $raffle->id)
            ->where('reservation_token', $token)
            ->whereIn('status', ['pending', 'paid'])
            ->orderBy('number')
            ->get();

        if ($tickets->isEmpty()) {
            return redirect()->route('public.raffle.show', $raffle->slug)
                ->with('error', 'Esta reserva não está mais ativa: o prazo de pagamento terminou ou ela foi cancelada. Você pode escolher novos números.');
        }

        $tenant  = $raffle->tenant;
        $pending = $tickets->where('status', 'pending');
        $amount  = $pending->count() * (float) $raffle->ticket_price;

        return view('public.raffles.checkout', [
            'raffle'          => $raffle,
            'tenant'          => $tenant,
            'tickets'         => $tickets,
            'pendingTickets'  => $pending,
            'pixPayload'      => $pending->isNotEmpty() ? $this->generatePixPayload($raffle, $amount) : '',
            'totalAmount'     => $pending->isNotEmpty() ? $amount : $tickets->count() * (float) $raffle->ticket_price,
            'expiresAt'       => optional($pending->min('reserved_at'))->copy()?->addHours(RaffleTicket::RESERVATION_HOURS),
            'whatsappSupport' => $tenant->whatsapp_support ?? $tenant->support_phone ?? '',
        ]);
    }

    // uploadReceipt removido em 2026-08-31 — dead code (nenhuma view/email
    // chamava a rota /rifa/ticket/{ticket}/comprovante). Fluxo real de
    // comprovante e via WhatsApp direto pro NGO (link na checkout blade).
    // Auditoria 2026-08-29 P3.b.2 flagou IDOR baixa (guard so por buyer_email
    // nao-secreto). Fix escolhido: deletar > blindar com HMAC, ja que ninguem
    // legitimo usava. Se algum dia o upload no site voltar a ser necessario,
    // implementar com token opaco HMAC bidx desde o inicio.

    /**
     * BR Code do Pix estático da rifa (regras em App\Support\PixPayload e PixKey:
     * a chave vai no formato do Banco Central, mesmo se foi gravada com pontuação).
     */
    private function generatePixPayload(Raffle $raffle, float $amount): string
    {
        return PixPayload::forTenant($raffle->tenant, $amount)
            ?? 'Chave PIX não configurada pela organização.';
    }
}
