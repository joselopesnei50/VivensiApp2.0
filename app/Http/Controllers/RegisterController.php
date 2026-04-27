<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $plan_id = $request->query('plan_id');
        $plan = null;
        if ($plan_id) {
            $plan = SubscriptionPlan::find($plan_id);
        }
        
        return view('auth.register', compact('plan'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'organization_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'account_type' => 'required|in:project_manager,ngo_admin,client',
            'terms' => 'required|accepted',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Tenant
            // Determinar tenant type baseado no account_type escolhido
            $tenantType = match($request->account_type) {
                'ngo_admin' => 'ngo',
                'project_manager' => 'business',
                'client' => 'common',
                default => 'common'
            };

            // Calculate trial and set price based on billing_cycle
            $trialDays = 7;
            $billingCycle = $request->query('billing_cycle', 'monthly');
            $subscriptionPrice = 0;
            $pagseguroPlanId = null;

            if ($request->plan_id) {
                $plan = SubscriptionPlan::find($request->plan_id);
                if ($plan) {
                    if ($billingCycle === 'yearly') {
                        $subscriptionPrice = $plan->price_yearly ?? ($plan->price * 12 * 0.9);
                        $pagseguroPlanId = $plan->pagseguro_plan_id_yearly;
                    } else {
                        $subscriptionPrice = $plan->price;
                        // $pagseguroPlanId = $plan->pagseguro_plan_id; // Assume existing column if implemented
                    }
                }
            }

            $tenant = Tenant::create([
                'name' => $request->organization_name,
                'type' => $tenantType,
                'plan_id' => $request->plan_id,
                'subscription_status' => 'pending', // No more trial
                'trial_ends_at' => null,
                'billing_cycle' => $billingCycle,
            ]);

            // 2. Create User with selected role (Normalized)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => match($request->account_type) {
                    'ngo_admin' => 'ngo',
                    'project_manager' => 'manager',
                    default => $request->account_type
                },
                'status' => 'active',
                'terms_accepted_at' => now(),
                'terms_ip' => $request->ip(),
            ]);

            DB::commit();

            // Track conversion if user came from a specific landing page
            if (session()->has('lp_source')) {
                \App\Models\LandingPageMetric::track(session('lp_source'), 'registration');
            }

            Auth::login($user);

            // 📧 Send Welcome Email (Premium Mailable)
            try {
                $plan = SubscriptionPlan::find($request->plan_id);
                $planName = $plan ? $plan->name : 'Plano Básico';
                Mail::to($user->email)->send(new WelcomeMail($user, $planName));
            } catch (\Exception $e) {
                \Log::error('Erro ao enviar e-mail de boas-vindas: ' . $e->getMessage());
            }

            // Se vendas fechadas, redireciona para página de interesse
            $salesOpen = \App\Models\SystemSetting::getValue('sales_open', '0');
            if (!$salesOpen || $salesOpen === '0') {
                return redirect()->route('register.interest');
            }

            if ($request->plan_id) {
                return redirect('/checkout/' . $request->plan_id)->with('success', 'Conta criada! Conclua o pagamento para acessar o sistema.');
            }

            return redirect('/dashboard');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erro ao criar conta', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Não foi possível criar a conta. Por favor, tente novamente ou entre em contato com o suporte.')->withInput();
        }
    }

    public function interestPage()
    {
        $whatsapp = \App\Models\SystemSetting::getValue('support_whatsapp', '16997618695');
        return view('auth.interest', compact('whatsapp'));
    }

    protected function getTenantTypeByPlan($plan_id)
    {
        if (!$plan_id) return 'common';
        
        $plan = SubscriptionPlan::find($plan_id);
        if (!$plan) return 'common';

        return match($plan->target_audience) {
            'ngo' => 'ngo',
            'manager' => 'business',
            default => 'common'
        };
    }
}
