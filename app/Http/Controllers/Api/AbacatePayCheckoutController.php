<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\AbacatePayService;
use Illuminate\Http\Request;

class AbacatePayCheckoutController extends Controller
{
    public function __construct(protected AbacatePayService $abacate) {}

    /**
     * Inicia um checkout para pagamento de plano.
     * POST /api/abacatepay/checkout
     * Body: { plan_id, plan_name, amount }
     */
    public function checkout(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount'    => 'required|numeric|min:1',
            'plan_name' => 'required|string',
            'plan_id'   => 'nullable|integer',
        ]);

        $tenant     = Tenant::findOrFail($request->user()->tenant_id);
        $externalId = 'VIVENSI_' . $tenant->id . '_' . time();
        $amountCents = (int) round($request->input('amount') * 100);

        // Registrar transação pendente
        $transaction = Transaction::create([
            'tenant_id'   => $tenant->id,
            'amount'      => $request->input('amount'),
            'description' => 'Plano: ' . $request->input('plan_name'),
            'type'        => 'income',
            'status'      => 'pending',
            'date'        => now()->toDateString(),
            'external_id' => $externalId,
        ]);

        // Criar checkout na AbacatePay
        // O item precisa existir no painel da AbacatePay (produto)
        // Usamos externalId como referência e passamos os dados no metadata
        $checkout = $this->abacate->createCheckout(
            items: [['externalId' => 'vivensi-plan-' . ($request->input('plan_id') ?? 'custom'), 'quantity' => 1]],
            externalId: $externalId,
            returnUrl: route('dashboard'),
            completionUrl: route('checkout.success'),
            methods: ['PIX', 'CARD'],
            metadata: [
                'tenant_id'    => $tenant->id,
                'tenant_name'  => $tenant->name,
                'plan_name'    => $request->input('plan_name'),
                'amount_brl'   => 'R$ ' . number_format($request->input('amount'), 2, ',', '.'),
            ]
        );

        if (!$checkout) {
            $transaction->update(['status' => 'canceled']);
            return response()->json([
                'status'  => 'error',
                'message' => 'Não foi possível iniciar o pagamento. Tente novamente.',
            ], 500);
        }

        // Salvar ID da AbacatePay na transação
        $transaction->update(['external_id' => $externalId, 'gateway_id' => $checkout['id'] ?? null]);

        return response()->json([
            'status'      => 'success',
            'payment_url' => $checkout['url'],
            'checkout_id' => $checkout['id'],
            'external_id' => $externalId,
        ]);
    }

    /**
     * Página de sucesso após pagamento.
     */
    public function success()
    {
        return view('checkout.success');
    }
}
