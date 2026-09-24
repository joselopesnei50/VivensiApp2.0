<?php

namespace App\Http\Controllers;

use App\Models\CatalogProduct;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-personal');
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Quote::where('tenant_id', $tenantId)->with('client:id,name');

        if ($status = $request->input('status')) {
            if (array_key_exists($status, Quote::STATUSES)) {
                $query->where('status', $status);
            }
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('quote_number', 'like', "%{$q}%");
            });
        }

        $quotes = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'draft'     => Quote::where('tenant_id', $tenantId)->where('status', 'draft')->count(),
            'sent'      => Quote::where('tenant_id', $tenantId)->where('status', 'sent')->count(),
            'accepted'  => Quote::where('tenant_id', $tenantId)->where('status', 'accepted')->count(),
            'converted' => Quote::where('tenant_id', $tenantId)->where('status', 'converted')->count(),
            'pipeline_value' => (float) Quote::where('tenant_id', $tenantId)
                ->whereIn('status', ['sent', 'accepted'])
                ->sum('total'),
        ];

        return view('personal.quotes.index', compact('quotes', 'stats'));
    }

    public function create(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $clients  = Client::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $catalog  = CatalogProduct::where('tenant_id', $tenantId)->active()->orderBy('name')->get(['id', 'name', 'unit_price', 'unit', 'type']);

        $preselectedClient = null;
        if ($cid = $request->query('client_id')) {
            $preselectedClient = Client::where('tenant_id', $tenantId)->where('id', $cid)->first();
        }

        return view('personal.quotes.create', compact('clients', 'catalog', 'preselectedClient'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateQuote($request);
        $tenantId  = auth()->user()->tenant_id;

        return DB::transaction(function () use ($validated, $request, $tenantId) {
            $quote = new Quote($this->quoteAttributes($validated));
            $quote->tenant_id    = $tenantId;
            $quote->quote_number = Quote::nextQuoteNumber($tenantId);
            $quote->status       = 'draft';
            $quote->save();

            $this->syncItems($quote, $request->input('items', []));
            $quote->recalculateTotal();
            $quote->save();

            return redirect()->route('quotes.show', $quote)
                ->with('success', 'Orçamento ' . $quote->quote_number . ' criado.');
        });
    }

    public function show(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $quote->load(['items.catalogProduct:id,name', 'client', 'convertedTransaction:id,description,date,status']);
        return view('personal.quotes.show', ['quote' => $quote]);
    }

    public function edit(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_if(in_array($quote->status, ['converted']), 400, 'Orçamento convertido não pode ser editado.');

        $tenantId = $quote->tenant_id;
        $quote->load('items');
        $clients  = Client::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $catalog  = CatalogProduct::where('tenant_id', $tenantId)->active()->orderBy('name')->get(['id', 'name', 'unit_price', 'unit', 'type']);

        return view('personal.quotes.edit', compact('quote', 'clients', 'catalog'));
    }

    public function update(Request $request, Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_if(in_array($quote->status, ['converted']), 400, 'Orçamento convertido não pode ser editado.');

        $validated = $this->validateQuote($request);

        return DB::transaction(function () use ($validated, $request, $quote) {
            $quote->fill($this->quoteAttributes($validated));
            $quote->save();

            $this->syncItems($quote, $request->input('items', []));
            $quote->recalculateTotal();
            $quote->save();

            return redirect()->route('quotes.show', $quote)
                ->with('success', 'Orçamento ' . $quote->quote_number . ' atualizado.');
        });
    }

    public function destroy(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_if($quote->status === 'converted', 400, 'Orçamento convertido não pode ser removido — cancele a receita primeiro.');
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Orçamento removido.');
    }

    public function markSent(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
        }
        return back()->with('success', 'Orçamento marcado como enviado.');
    }

    public function markAccepted(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless(in_array($quote->status, ['draft', 'sent']), 400);
        $quote->update(['status' => 'accepted', 'accepted_at' => now()]);
        return back()->with('success', 'Orçamento aceito. Você pode converter em receita agora.');
    }

    public function markRejected(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless(in_array($quote->status, ['draft', 'sent']), 400);
        $quote->update(['status' => 'rejected', 'rejected_at' => now()]);
        return back()->with('success', 'Orçamento marcado como rejeitado.');
    }

    public function duplicate(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);

        return DB::transaction(function () use ($quote) {
            $copy = $quote->replicate([
                'quote_number', 'status', 'sent_at', 'accepted_at',
                'rejected_at', 'converted_at', 'converted_transaction_id',
            ]);
            $copy->quote_number = Quote::nextQuoteNumber($quote->tenant_id);
            $copy->status       = 'draft';
            $copy->issue_date   = now()->toDateString();
            $copy->save();

            foreach ($quote->items as $item) {
                $newItem = $item->replicate();
                $newItem->quote_id = $copy->id;
                $newItem->save();
            }

            $copy->recalculateTotal();
            $copy->save();

            return redirect()->route('quotes.show', $copy)
                ->with('success', 'Cópia criada como rascunho: ' . $copy->quote_number);
        });
    }

    /**
     * Converte orçamento aceito em Transaction (receita pendente).
     * Deixa a data de vencimento igual a valid_until (ou 15 dias a partir de hoje).
     */
    public function convert(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($quote->status === 'accepted', 400, 'Só orçamentos aceitos podem virar receita.');

        return DB::transaction(function () use ($quote) {
            $due = $quote->valid_until ?: now()->addDays(15);

            $transaction = Transaction::create([
                'tenant_id'   => $quote->tenant_id,
                'client_id'   => $quote->client_id,
                'description' => 'Orçamento ' . $quote->quote_number . ' — ' . $quote->title,
                'amount'      => $quote->total,
                'type'        => 'income',
                'date'        => $due,
                'status'      => 'pending',
                'approval_status' => 'approved',
            ]);

            $quote->update([
                'status'                    => 'converted',
                'converted_at'              => now(),
                'converted_transaction_id'  => $transaction->id,
            ]);

            return redirect()->route('quotes.show', $quote)
                ->with('success', 'Convertido em receita pendente #' . $transaction->id . ' com vencimento ' . $due->format('d/m/Y'));
        });
    }

    public function pdf(Quote $quote)
    {
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $quote->load(['items', 'client', 'tenant']);

        $pdf = Pdf::loadView('personal.quotes.pdf', ['quote' => $quote])
            ->setPaper('a4');

        return $pdf->download('orcamento-' . $quote->quote_number . '.pdf');
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'client_id'   => 'nullable|integer|exists:clients,id',
            'title'       => 'required|string|max:200',
            'issue_date'  => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'discount'    => 'nullable|numeric|min:0|max:99999999.99',
            'notes'       => 'nullable|string|max:2000',
            'terms'       => 'nullable|string|max:4000',

            'items'                    => 'required|array|min:1',
            'items.*.name'             => 'required|string|max:200',
            'items.*.description'      => 'nullable|string|max:1000',
            'items.*.quantity'         => 'required|numeric|min:0.001|max:999999.999',
            'items.*.unit'             => 'nullable|string|max:20',
            'items.*.unit_price'       => 'required|numeric|min:0|max:99999999.99',
            'items.*.catalog_product_id' => 'nullable|integer|exists:catalog_products,id',
        ], [
            'items.required' => 'Inclua pelo menos um item no orçamento.',
            'items.min'      => 'Inclua pelo menos um item no orçamento.',
        ]);
    }

    private function quoteAttributes(array $validated): array
    {
        return [
            'client_id'   => $validated['client_id'] ?? null,
            'title'       => $validated['title'],
            'issue_date'  => $validated['issue_date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'discount'    => $validated['discount'] ?? 0,
            'notes'       => $validated['notes'] ?? null,
            'terms'       => $validated['terms'] ?? null,
        ];
    }

    private function syncItems(Quote $quote, array $itemsInput): void
    {
        // Estrategia simples: apaga e recria — orcamento tem poucos itens tipicamente.
        $quote->items()->delete();

        foreach (array_values($itemsInput) as $position => $it) {
            $qty   = (float) ($it['quantity'] ?? 1);
            $price = (float) ($it['unit_price'] ?? 0);

            QuoteItem::create([
                'quote_id'           => $quote->id,
                'catalog_product_id' => $it['catalog_product_id'] ?? null,
                'name'               => $it['name'],
                'description'        => $it['description'] ?? null,
                'quantity'           => $qty,
                'unit'               => $it['unit'] ?? 'un',
                'unit_price'         => $price,
                'subtotal'           => round($qty * $price, 2),
                'position'           => $position,
            ]);
        }

        $quote->load('items');
    }
}
