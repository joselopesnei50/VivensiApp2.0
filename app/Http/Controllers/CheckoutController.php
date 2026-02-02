<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\AsaasService;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Exception;

class CheckoutController extends Controller
{
    protected $asaas;
    protected $brevo;

    public function __construct(AsaasService $asaas, BrevoService $brevo)
    {
        $this->asaas = $asaas;
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
     * Process checkout - Create customer and subscription in Asaas
     */
    public function process(Request $request)
    {
        try {
            $user = auth()->user();
            $tenant = Tenant::findOrFail($user->tenant_id);
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // 1. Create/Update Customer in Asaas if not exists
            if (!$tenant->asaas_customer_id) {
                // Preencher documento se estiver vazio no tenant mas veio no request
                if ($request->filled('document')) {
                    $tenant->document = $request->document;
                    $tenant->save();
                }

                if (!$tenant->document) {
                    return back()->with('error', 'CPF ou CNPJ é obrigatório para o faturamento.');
                }

                $customer = $this->asaas->createCustomer($tenant);
                $tenant->asaas_customer_id = $customer['id'];
                $tenant->save();
            }

            // 2. Create Subscription
            $subscription = $this->asaas->createSubscription(
                $tenant->asaas_customer_id, 
                $plan, 
                $request->payment_method ?? 'UNDEFINED'
            );

            // 3. Update Tenant local info
            $tenant->plan_id = $plan->id;
            $tenant->subscription_status = 'pending'; // confirmed via webhook
            $tenant->save();

            // 4. Send Welcome Email via Brevo
            $this->brevo->sendWelcomeEmail($user, $plan->name);

            // Redirect to success page or the billing invoice URL
            return redirect()->route('checkout.success', ['id' => $subscription['id']]);

        } catch (Exception $e) {
            return back()->with('error', 'Erro ao processar checkout: ' . $e->getMessage());
        }
    }

    /**
     * Success page - Show payment info (Pix/Boleto)
     */
    public function success(Request $request)
    {
        // Here we could fetch the specific payment info from Asaas to show Pix QR Code
        return view('checkout.success');
    }
}
