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
    /**
     * Cadastro público: só Terceiro Setor (ONG, associação, instituto, fundação).
     * Plano vindo do link (?plan_id=) vale se for de ONG e ativo — inclusive
     * cortesia enviada pela equipe. Sem plano no link, a pessoa escolhe entre
     * os planos pagos de ONG ativos.
     */
    public function showRegistrationForm(Request $request)
    {
        $plans = $this->publicPlans();

        $plan = null;
        if ($request->query('plan_id')) {
            $plan = SubscriptionPlan::where('id', $request->query('plan_id'))
                ->where('is_active', true)->where('target_audience', 'ngo')->first();
        }
        if (!$plan && $plans->count() === 1) {
            $plan = $plans->first();
        }

        return view('auth.register', compact('plan', 'plans'));
    }

    /** Planos de ONG pagos e ativos, do mais barato ao mais caro. */
    private function publicPlans()
    {
        return SubscriptionPlan::where('is_active', true)
            ->where('target_audience', 'ngo')
            ->where(fn ($q) => $q->where('is_courtesy', false)->orWhereNull('is_courtesy'))
            ->orderBy('price')
            ->get();
    }

    public function register(Request $request)
    {
        $request->validate([
            'organization_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
            // OBRIGATÓRIO — sem plano ninguém entra. Só planos de ONG ativos
            // (cadastro público é exclusivo do Terceiro Setor).
            'plan_id' => ['required', \Illuminate\Validation\Rule::exists('subscription_plans', 'id')
                ->where('is_active', true)->where('target_audience', 'ngo')],
            'terms' => 'required|accepted',
        ], [
            'plan_id.required' => 'Escolha um plano para continuar.',
            'plan_id.exists'   => 'Escolha um dos planos disponíveis para organizações do Terceiro Setor.',
            'terms.required'   => 'É preciso aceitar os Termos de Uso e a Política de Privacidade.',
            'terms.accepted'   => 'É preciso aceitar os Termos de Uso e a Política de Privacidade.',
        ], [
            'organization_name' => 'nome da organização',
            'name'              => 'seu nome',
        ]);

        // Verifica se o plano está ativo antes de criar conta
        $selectedPlan = SubscriptionPlan::find($request->plan_id);
        if (!$selectedPlan || !$selectedPlan->is_active) {
            return back()->with('error', 'O plano selecionado não está disponível. Escolha outro plano.')->withInput();
        }

        try {
            DB::beginTransaction();

            // 1. Organização — cadastro público é sempre Terceiro Setor.
            $tenantType = 'ngo';
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
                'role' => 'ngo',
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
