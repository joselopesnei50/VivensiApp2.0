<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\AsaasService;
use App\Services\PagSeguroService;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Exception;

class CheckoutController extends Controller
{
    protected $pagSeguro;
    protected $brevo;

    public function __construct(PagSeguroService $pagSeguro, BrevoService $brevo)
    {
        $this->pagSeguro = $pagSeguro;
        $this->brevo = $brevo;
    }

    /**
     * Show checkout page
     */
    public function index($plan_id)
    {
        $plan = SubscriptionPlan::findOrFail($plan_id);
        $user = auth()->user();
        
        // Ensure user has a tenant
        if (!$user->tenant_id) {
            return redirect('/dashboard')->with('error', 'Organização não encontrada para o usuário.');
        }

        $tenant = Tenant::find($user->tenant_id);

        return view('checkout.index', compact('plan', 'tenant'));
    }

    /**
     * Process checkout - Create payment in PagSeguro
     */
    public function process(Request $request)
    {
        try {
            $user = auth()->user();
            $tenant = Tenant::findOrFail($user->tenant_id);
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // 1. Update Tenant Document if provided
            if ($request->filled('document')) {
                $tenant->document = $request->document;
                $tenant->save();
            }

            if (!$tenant->document) {
                return back()->with('error', 'CPF ou CNPJ é obrigatório para o faturamento.');
            }

            // 2. Prepare Data for PagSeguro
            // Generate a unique reference: VIVENSI_TENANT_PLAN_TIMESTAMP
            $reference = sprintf("VIVENSI_%s_%s_%s", $tenant->id, $plan->id, time());

            $paymentData = [
                'reference' => $reference,
                'amount' => $plan->price,
                'description' => 'Assinatura ' . $plan->name,
                'sender' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'cpf' => $this->cleanCpf($tenant->document), // Ensure only numbers
                ]
            ];

            // 3. Call PagSeguro
            $result = $this->pagSeguro->createPayment($paymentData);

            if ($result && isset($result['paymentLink'])) {
                return redirect()->away($result['paymentLink']);
            }

            return back()->with('error', 'Não foi possível gerar o link de pagamento. Tente novamente.');

        } catch (Exception $e) {
            return back()->with('error', 'Erro ao processar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Helper to clean CPF/CNPJ
     */
    private function cleanCpf($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }
}
