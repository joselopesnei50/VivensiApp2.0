<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\AbacatePayService;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(
        protected AbacatePayService $abacate,
        protected BrevoService      $brevo,
    ) {}

    /**
     * Exibe a página de checkout para o plano selecionado.
     */
    public function index($plan_id)
    {
        $plan = SubscriptionPlan::findOrFail($plan_id);
        $user = auth()->user();

        if (!$user->tenant_id) {
            return redirect('/dashboard')->with('error', 'Organização não encontrada para o usuário.');
        }

        $tenant = Tenant::find($user->tenant_id);

        return view('checkout.index', compact('plan', 'tenant'));
    }

    /**
     * Processa o checkout — AbacatePay (padrão) ou PagSeguro (legado).
     */
    public function process(Request $request)
    {
        $request->validate([
            'plan_id'        => 'required|integer|exists:subscription_plans,id',
            'payment_method' => 'nullable|in:PIX,CARD',
        ]);

        try {
            $user    = auth()->user();
            $tenant  = Tenant::findOrFail($user->tenant_id);
            $plan    = SubscriptionPlan::findOrFail($request->plan_id);
            $gateway = $request->input('gateway', 'abacatepay');

            // Salvar/atualizar documento do tenant
            if ($request->filled('document')) {
                $tenant->document = preg_replace('/\D/', '', $request->document);
                $tenant->save();
            }

            if (!$tenant->document) {
                return back()->with('error', 'CPF ou CNPJ é obrigatório para o faturamento.');
            }

            // ── AbacatePay (padrão) ────────────────────────────────────────
            if ($gateway === 'abacatepay') {
                return $this->processAbacatePay($request, $tenant, $plan, $user);
            }

            // ── PagSeguro foi descontinuado — redireciona para AbacatePay ──
            Log::info('CheckoutController: tentativa de uso do PagSeguro descontinuado', [
                'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            ]);
            return $this->processAbacatePay($request, $tenant, $plan, $user);

        } catch (Exception $e) {
            Log::error('CheckoutController::process exception', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar pagamento: ' . $e->getMessage());
        }
    }

    // ─── AbacatePay ────────────────────────────────────────────────────────

    private function processAbacatePay(Request $request, Tenant $tenant, SubscriptionPlan $plan, $user)
    {
        if (empty($plan->abacatepay_product_id)) {
            return back()->with('error',
                'Este plano ainda não está configurado para pagamento via AbacatePay. Entre em contato com o suporte.'
            );
        }

        $externalId    = 'VIVENSI_' . $tenant->id . '_' . time();
        $paymentMethod = $request->input('payment_method', 'PIX');

        // Registrar transação pendente
        $transaction = Transaction::create([
            'tenant_id'   => $tenant->id,
            'plan_id'     => $plan->id,
            'amount'      => $plan->price,
            'description' => 'Assinatura: ' . $plan->name,
            'type'        => 'income',
            'status'      => 'pending',
            'date'        => now()->toDateString(),
            'external_id' => $externalId,
        ]);

        // Criar checkout na AbacatePay
        // items[].id = ID do produto cadastrado no painel AbacatePay (campo obrigatório)
        $checkout = $this->abacate->createCheckout(
            items: [['id' => $plan->abacatepay_product_id, 'quantity' => 1]],
            externalId: $externalId,
            returnUrl: route('dashboard'),
            completionUrl: route('checkout.success'),
            methods: [$paymentMethod],
            metadata: [
                'tenant_id'  => $tenant->id,
                'plan_id'    => $plan->id,
                'plan_name'  => $plan->name,
            ]
        );

        if (!$checkout || empty($checkout['url'])) {
            $transaction->update(['status' => 'canceled']);
            Log::error('AbacatePay checkout falhou', ['plan' => $plan->id, 'tenant' => $tenant->id]);
            return back()->with('error', 'Não foi possível iniciar o pagamento. Tente novamente.');
        }

        $transaction->update(['gateway_id' => $checkout['id'] ?? null]);

        // Redirecionar para checkout hospedado pela AbacatePay
        return redirect($checkout['url']);
    }

    /**
     * Página de sucesso após pagamento.
     */
    public function success()
    {
        return view('checkout.success');
    }
}
