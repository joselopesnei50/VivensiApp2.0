<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Models\Transaction;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Transaction::where('tenant_id', $tenantId)
            ->when($request->type,   fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from,   fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to,     fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $perPage = min((int) ($request->per_page ?? 20), 100);

        // Cursor pagination for large datasets (?cursor=xxx)
        if ($request->boolean('cursor')) {
            return TransactionResource::collection($query->cursorPaginate($perPage));
        }

        $paginated = $query->paginate($perPage);

        return TransactionResource::collection($paginated);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'type'        => ['required', Rule::in(['income', 'expense'])],
            'status'      => ['nullable', Rule::in(['paid', 'pending', 'canceled'])],
            'category_id' => 'nullable|integer',
            'project_id'  => 'nullable|integer',
        ]);

        $validated['tenant_id']      = $request->user()->tenant_id;
        $validated['status']         = $validated['status'] ?? 'paid';
        $validated['approval_status'] = 'approved';

        $transaction = Transaction::create($validated);

        app(WebhookService::class)->fire($validated['tenant_id'], 'transaction.created', [
            'id'          => $transaction->id,
            'type'        => $transaction->type,
            'amount'      => (float) $transaction->amount,
            'description' => $transaction->description,
            'date'        => $transaction->date?->toDateString(),
        ]);

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        $transaction = Transaction::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return new TransactionResource($transaction);
    }

    public function update(Request $request, int $id)
    {
        $transaction = Transaction::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'description' => 'sometimes|string|max:255',
            'amount'      => 'sometimes|numeric|min:0.01',
            'date'        => 'sometimes|date',
            'status'      => ['sometimes', Rule::in(['paid', 'pending', 'canceled'])],
            'category_id' => 'nullable|integer',
        ]);

        $transaction->update($validated);

        return new TransactionResource($transaction->fresh());
    }

    public function destroy(Request $request, int $id)
    {
        Transaction::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
