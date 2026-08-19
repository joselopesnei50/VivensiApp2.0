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
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
            'plan_id' => 'required|exists:subscription_plans,id', // OBRIGATÓRIO — sem plano ninguém entra
            'account_type' => 'required|in:project_manager,ngo_admin,client',
            'terms' => 'required|accepted',
        ]);

        // Verifica se o plano está ativo antes de criar conta
        $selectedPlan = SubscriptionPlan::find($request->plan_id);
        if (!$selectedPlan || !$selectedPlan->is_active) {
            return back()->with('error', 'O plano selecionado não está disponível. Escolha outro plano.')->withInput();
        }

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

            $billingCycle = $request->query('billing_cycle', 'monthly');

            $tenant = Tenant::create([
                'name' => $request->organization_name,
                'type' => $tenantType,
                'plan_id' => $request->plan_id,
                // Fluxo: sempre passa pelo contrato de adesao antes de pagar/acessar.
                // Cortesia e paga tem o mesmo entrypoint — CheckSubscription/AdesaoController
                // decide o proximo destino apos assinatura.
                'subscription_status' => 'awaiting_contract',
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

            // Welcome Email
            try {
                Mail::to($user->email)->send(new WelcomeMail($user, $selectedPlan->name));
            } catch (\Exception $e) {
                \Log::error('Erro ao enviar e-mail de boas-vindas: ' . $e->getMessage());
            }

            // Meta Pixel: sinal de cadastro concluido — vai pra sessao flash e o
            // proximo layout (checkout ou app) dispara CompleteRegistration.
            session()->flash('meta_pixel_register', [
                'plan_id'   => $selectedPlan->id,
                'plan_name' => $selectedPlan->name,
                'value'     => (float) ($selectedPlan->price ?? 0),
            ]);

            // Cortesia OU pago: ambos passam pelo contrato de adesao.
            // Apos assinatura, AdesaoController redireciona:
            //   is_courtesy -> subscription_status='active' -> /dashboard
            //   pago        -> subscription_status='pending' -> /checkout/{plan}
            Auth::login($user);
            return redirect()->route('adesao.show')
                ->with('success', 'Cadastro criado! Complete os dados da entidade e assine o contrato de adesao.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erro ao criar conta', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Não foi possível criar a conta. Por favor, tente novamente ou entre em contato com o suporte.')->withInput();
        }
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
