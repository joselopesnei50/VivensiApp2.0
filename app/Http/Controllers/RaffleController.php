<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use App\Mail\RaffleTicketPaid;
use App\Mail\RaffleTicketReleased;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RaffleController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $raffles = Raffle::where('tenant_id', $tenant->id)->withCount('tickets')->get();
        
        return view('raffles.index', compact('raffles'));
    }

    public function create()
    {
        return view('raffles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'rules' => 'nullable|string',
            'ticket_price' => 'required|numeric|min:1',
            'total_tickets' => 'required|integer|min:1|max:10000',
            'draw_date' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
        ]);

        $tenant = auth()->user()->tenant;

        DB::transaction(function () use ($request, $tenant) {
            $imagePath = $request->file('image') ? $request->file('image')->store('raffles', 'public') : null;

            $raffle = Raffle::create([
                'tenant_id' => $tenant->id,
                'title' => $request->title,
                'slug' => \Illuminate\Support\Str::slug($request->title) . '-' . uniqid(),
                'description' => $request->description,
                'rules' => $request->rules,
                'image_path' => $imagePath,
                'ticket_price' => $request->ticket_price,
                'total_tickets' => $request->total_tickets,
                'draw_date' => $request->draw_date,
                'status' => 'active',
            ]);

            // Pre-generate tickets
            for ($i = 1; $i <= $request->total_tickets; $i++) {
                RaffleTicket::create([
                    'raffle_id' => $raffle->id,
                    'number' => $i,
                    'status' => 'available',
                ]);
            }
        });

        return redirect()->route('raffles.index')->with('success', 'Rifa criada com sucesso!');
    }

    public function show(Raffle $raffle)
    {
        $this->authorizeTenant($raffle);
        $tickets = $raffle->tickets()->with('transaction')->get();
        
        return view('raffles.show', compact('raffle', 'tickets'));
    }

    public function confirmPayment(RaffleTicket $ticket)
    {
        $this->authorizeTenant($ticket->raffle);

        if ($ticket->status === 'paid') {
            return back()->with('error', 'Este bilhete já está pago.');
        }

        DB::transaction(function () use ($ticket) {
            // Create transaction in financial module
            $category = FinancialCategory::firstOrCreate(
                ['tenant_id' => $ticket->raffle->tenant_id, 'name' => 'Rifas'],
                ['type' => 'income']
            );

            $transaction = Transaction::create([
                'tenant_id' => $ticket->raffle->tenant_id,
                'type' => 'income',
                'category_id' => $category->id,
                'amount' => $ticket->raffle->ticket_price,
                'description' => "Venda de bilhete #{$ticket->number} para {$ticket->buyer_name}",
                'date' => now(),
                'status' => 'paid',
            ]);

            $ticket->update([
                'status' => 'paid',
                'transaction_id' => $transaction->id,
            ]);

            // Send confirmation email
            try {
                Mail::to($ticket->buyer_email)->send(new RaffleTicketPaid($ticket->raffle, $ticket));
            } catch (\Exception $e) {
                // Log error but don't stop the process
                \Log::error("Failed to send raffle email: " . $e->getMessage());
            }
        });

        return back()->with('success', "Pagamento do bilhete #{$ticket->number} confirmado!");
    }

    public function releaseTicket(RaffleTicket $ticket)
    {
        $this->authorizeTenant($ticket->raffle);

        if ($ticket->status !== 'pending') {
            return back()->with('error', 'Apenas bilhetes pendentes podem ser liberados.');
        }

        // Store info for email before clearing
        $buyerEmail = $ticket->buyer_email;
        $raffle = $ticket->raffle;

        DB::transaction(function () use ($ticket, $buyerEmail, $raffle) {
            
            // Send email notification BEFORE clearing ticket data
            if ($buyerEmail) {
                try {
                    Mail::to($buyerEmail)->send(new RaffleTicketReleased($raffle, $ticket));
                } catch (\Exception $e) {
                    \Log::error("Failed to send raffle release email: " . $e->getMessage());
                }
            }

            // Clear ticket data
            $ticket->update([
                'status' => 'available',
                'buyer_name' => null,
                'buyer_email' => null,
                'buyer_phone' => null,
                'reserved_at' => null,
                'payment_receipt_path' => null,
            ]);
        });

        return back()->with('success', "O bilhete #{$ticket->number} foi liberado e está disponível novamente.");
    }

    public function draw(Raffle $raffle)
    {
        $this->authorizeTenant($raffle);

        if ($raffle->status === 'finished') {
            return back()->with('error', 'Esta rifa já foi sorteada.');
        }

        $winnerTicket = RaffleTicket::where('raffle_id', $raffle->id)
            ->where('status', 'paid')
            ->inRandomOrder()
            ->first();

        if (!$winnerTicket) {
            return back()->with('error', 'Nenhum bilhete pago encontrado para realizar o sorteio.');
        }

        $raffle->update([
            'winner_ticket_id' => $winnerTicket->id,
            'status' => 'finished'
        ]);

        return back()->with('success', "Sorteio realizado! O vencedor é o bilhete #{$winnerTicket->number} ({$winnerTicket->buyer_name}).");
    }

    private function authorizeTenant(Raffle $raffle)
    {
        if ($raffle->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
