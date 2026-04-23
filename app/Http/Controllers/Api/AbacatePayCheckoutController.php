<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\AbacatePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AbacatePayCheckoutController
 *
 * Inicia checkouts de assinatura na AbacatePay.
 * O campo items[].id DEVE ser o ID do produto cadastrado no painel AbacatePay.
 * Ref: https://docs.abacatepay.com/pages/payment/create
 */
class AbacatePayCheckoutController extends Controller
{
    public function __construct(protected AbacatePayService $abacate) {}

    /**
     * Inicia checkout de assinatura mensal com trial de 7 dias.
     * Chamado pelo formulário em /checkout (POST checkout.process).
     *
     * Body: { plan_id, document, payment_method, gateway }
     */
    public function checkout(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'plan_id'        => 'required|integer|exists:subscription_plans,id',
            'payment_method' => 'nullable|in:PIX,CARD',
        ]);

        $plan   = SubscriptionPlan::findOrFail($request->input('plan_id'));
        $tenant = Tenant::findOrFail($request->user()->tenant_id);

        // Verificar se o plano tem o ID do produto configurado na AbacatePay
        if (empty($plan->abacatepay_product_id)) {
            Log::error('AbacatePay checkout: plano sem abacatepay_product_id', ['plan_id' => $plan->id]);
            return redirect()->back()->with('error',
                'Este plano ainda não está configurado para pagamento via AbacatePay. Entre em contato com o suporte.'
            );
        }

        $externalId     = 'VIVENSI_' . $tenant->id . '_' . time();
        $paymentMethod  = $request->input('payment_method', 'PIX');

        // Registrar transação pendente
        $transaction = Transaction::create([
            'tenant_id'   => $tenant->id,
            'amount'      => $plan->price,
            'description' => 'Assinatura: ' . $plan->name,
            'type'        => 'income',
            'status'      => 'pending',
            'date'        => now()->toDateString(),
            'external_id' => $externalId,
        ]);

        // Criar checkout na AbacatePay
        // items[].id = ID do produto cadastrado no painel AbacatePay (obrigatório)
        $checkout = $this->abacate->createCheckout(
            items: [
                [
                    'id'       => $plan->abacatepay_product_id, // ✅ campo correto conforme docs
                    'quantity' => 1,
                ]
            ],
            externalId: $externalId,
            returnUrl: route('dashboard'),
            completionUrl: route('checkout.success'),
            methods: [$paymentMethod],   // PIX ou CARD — escolha do usuário
            metadata: [
                'tenant_id'   => $tenant->id,
                'tenant_name' => $tenant->name,
                'plan_id'     => $plan->id,
                'plan_name'   => $plan->name,
            ]
        );

        if (!$checkout || empty($checkout['url'])) {
            $transaction->update(['status' => 'canceled']);
            Log::error('AbacatePay: falha ao criar checkout', [
                'plan'   => $plan->id,
                'tenant' => $tenant->id,
            ]);
            return redirect()->back()->with('error',
                'Não foi possível iniciar o pagamento. Verifique a configuração da API e tente novamente.'
            );
        }

        // Salvar o ID gerado pela AbacatePay na transação
        $transaction->update([
            'external_id' => $externalId,
            'gateway_id'  => $checkout['id'] ?? null,
        ]);

        // Redirecionar para o checkout hospedado na AbacatePay
        return redirect($checkout['url']);
    }

    /**
     * Página de sucesso após pagamento completado.
     */
    public function success()
    {
        return view('checkout.success');
    }
}
