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
            $nextDueDate = now()->addDays(3);
            
            // If still in trial, set due date to trial end
            if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
                $nextDueDate = $tenant->trial_ends_at->copy()->addDay(); // Charge the day after trial ends
            }

            $subscription = $this->asaas->createSubscription(
                $tenant->asaas_customer_id, 
                $plan, 
                $request->payment_method ?? 'UNDEFINED',
                $nextDueDate
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
        $subscriptionId = $request->get('id');
        $paymentData = null;

        if ($subscriptionId) {
            $payments = $this->asaas->getSubscriptionPayments($subscriptionId);
            
            // Get the first pending payment
            if ($payments && isset($payments['data']) && count($payments['data']) > 0) {
                // Usually the first one is the current one pending
                foreach($payments['data'] as $payment) {
                    if ($payment['status'] === 'PENDING') {
                        $paymentData = $payment;
                        break;
                    }
                }
                
                // If we found a pending payment and it is PIX, let's try to get the QR Code payload/image if not present
                // Note: The /payments endpoint usually returns basic info. 
                // Sometimes we need a specific endpoint for Pix QR Code / identificationField (boleto)
                
                // If it is BOLETO or PIX, let's ensure we have the necessary data
                if ($paymentData) {
                    // Fetch specific payment details which usually contains the pixQRCode or numCode (boleto)
                    // Extending service on the fly here or assuming data is there?
                    // Let's assume standard list returns basic info. 
                    // To be safe, let's try to fetch payment specific identification if needed.
                    
                    // Actually, for Asaas v3, the payment object usually has 'invoiceUrl', 'bankSlipUrl' (boleto), etc.
                    // For Pix, sometimes we need to call /payments/{id}/pixQrCode
                    
                    if ($paymentData['billingType'] === 'PIX') {
                        $pixInfo = $this->asaas->getPixQrCode($paymentData['id']);
                        if ($pixInfo) {
                            $paymentData['pix'] = $pixInfo;
                        }
                    }
                    
                    if ($paymentData['billingType'] === 'BOLETO') {
                        $boletoInfo = $this->asaas->getBoletoCode($paymentData['id']);
                         if ($boletoInfo) {
                            $paymentData['boleto'] = $boletoInfo;
                        }
                    }
                }
            }
        }

        return view('checkout.success', compact('paymentData'));
    }
}
