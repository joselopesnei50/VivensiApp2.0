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

            // Gera os números em lotes (10.000 inserts um a um estouravam o tempo da página).
            $now = now();
            foreach (array_chunk(range(1, (int) $request->total_tickets), 1000) as $chunk) {
                RaffleTicket::insert(array_map(fn ($n) => [
                    'raffle_id'  => $raffle->id,
                    'number'     => $n,
                    'status'     => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
        });

        return redirect()->route('raffles.index')->with('success', 'Rifa criada com sucesso!');
    }

    public function show(Raffle $raffle)
    {
        $this->authorizeTenant($raffle);

        $paidCount    = $raffle->tickets()->where('status', 'paid')->count();
        $pendingCount = $raffle->tickets()->where('status', 'pending')->count();
        $tickets      = $raffle->tickets()
                            ->with('transaction')
                            ->whereIn('status', ['pending', 'paid'])
                            ->orderByDesc('updated_at')
                            ->get();

        // Quantos números pendentes cada compra tem (botão "confirmar a compra toda").
        $pendingPerOrder = $tickets->where('status', 'pending')->whereNotNull('reservation_token')
            ->countBy('reservation_token');

        return view('raffles.show', compact('raffle', 'tickets', 'paidCount', 'pendingCount', 'pendingPerOrder'));
    }

    /**
     * Confirma o pagamento de um número — ou de todos os números pendentes da mesma compra
     * (scope=order). Gera UM lançamento no financeiro e UM e-mail para o comprador.
     */
    public function confirmPayment(Request $request, RaffleTicket $ticket)
    {
        $raffle = $ticket->raffle;
        $this->authorizeTenant($raffle);
        $wholeOrder = $request->input('scope') === 'order';

        $confirmed = DB::transaction(function () use ($ticket, $raffle, $wholeOrder) {
            // Trava as linhas: dois cliques (ou duas abas) não geram dois lançamentos.
            $query = $wholeOrder ? $ticket->siblingsQuery() : RaffleTicket::whereKey($ticket->id);
            $tickets = $query->where('status', 'pending')->orderBy('number')->lockForUpdate()->get();

            if ($tickets->isEmpty()) {
                return $tickets;
            }

            $category = FinancialCategory::firstOrCreate(
                ['tenant_id' => $raffle->tenant_id, 'name' => 'Rifas'],
                ['type' => 'income']
            );

            $numbers = $tickets->map(fn ($t) => '#' . $t->number)->implode(', ');
            $transaction = Transaction::create([
                'tenant_id'   => $raffle->tenant_id,
                'type'        => 'income',
                'category_id' => $category->id,
                'amount'      => (float) $raffle->ticket_price * $tickets->count(),
                'description' => ($tickets->count() > 1 ? "Venda dos bilhetes {$numbers}" : "Venda do bilhete {$numbers}")
                                 . " ({$raffle->title}) para {$tickets->first()->buyer_name}",
                'date'        => now(),
                'status'      => 'paid',
            ]);

            RaffleTicket::whereIn('id', $tickets->pluck('id'))->update([
                'status'         => 'paid',
                'transaction_id' => $transaction->id,
            ]);

            return $tickets;
        });

        if ($confirmed->isEmpty()) {
            return back()->with('error', 'Este pagamento já foi confirmado.');
        }

        if ($email = $confirmed->first()->buyer_email) {
            try {
                Mail::to($email)->send(new RaffleTicketPaid($raffle, $confirmed->each(fn ($t) => $t->status = 'paid')));
            } catch (\Throwable $e) {
                \Log::error('Failed to send raffle email: ' . $e->getMessage());
            }
        }

        $label = $confirmed->count() > 1
            ? 'Pagamento dos bilhetes ' . $confirmed->map(fn ($t) => '#' . $t->number)->implode(', ') . ' confirmado!'
            : "Pagamento do bilhete #{$confirmed->first()->number} confirmado!";

        return back()->with('success', $label);
    }

    public function releaseTicket(RaffleTicket $ticket)
    {
        $raffle = $ticket->raffle;
        $this->authorizeTenant($raffle);

        $released = DB::transaction(function () use ($ticket) {
            $fresh = RaffleTicket::whereKey($ticket->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status !== 'pending') {
                return null;
            }
            $copy = clone $fresh; // guarda nome/e-mail para o aviso
            $fresh->update(RaffleTicket::RELEASED_ATTRIBUTES);
            return $copy;
        });

        if (!$released) {
            return back()->with('error', 'Apenas bilhetes pendentes podem ser liberados.');
        }

        if ($released->buyer_email) {
            try {
                Mail::to($released->buyer_email)->send(new RaffleTicketReleased($raffle, $released));
            } catch (\Throwable $e) {
                \Log::error('Failed to send raffle release email: ' . $e->getMessage());
            }
        }

        return back()->with('success', "O bilhete #{$released->number} foi liberado e está disponível novamente.");
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

    public function destroy(Raffle $raffle)
    {
        $this->authorizeTenant($raffle);

        // Safety check: Prevent deletion if there are paid tickets
        $paidTicketsCount = $raffle->tickets()->where('status', 'paid')->count();
        if ($paidTicketsCount > 0) {
            return back()->with('error', "Esta rifa possui {$paidTicketsCount} bilhete(s) pago(s) e não pode ser excluída por segurança financeira.");
        }

        DB::transaction(function () use ($raffle) {
            // 1. Delete all associated tickets
            $raffle->tickets()->delete();

            // 2. Delete the image file if it exists
            if ($raffle->image_path && Storage::disk('public')->exists($raffle->image_path)) {
                Storage::disk('public')->delete($raffle->image_path);
            }

            // 3. Delete the raffle itself
            $raffle->delete();
        });

        return redirect()->route('raffles.index')->with('success', 'Rifa excluída permanentemente com sucesso!');
    }

    private function authorizeTenant(Raffle $raffle)
    {
        if ($raffle->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
