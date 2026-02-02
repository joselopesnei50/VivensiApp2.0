<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->organization_name,
                'type' => $this->getTenantTypeByPlan($request->plan_id),
                'plan_id' => $request->plan_id,
                'subscription_status' => 'trialing',
                'trial_ends_at' => now()->addDays(7),
            ]);

            // 2. Create User (Manager)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'role' => 'manager',
                'status' => 'active',
            ]);

            DB::commit();

            // Track conversion if user came from a specific landing page
            if (session()->has('lp_source')) {
                \App\Models\LandingPageMetric::track(session('lp_source'), 'registration');
            }

            Auth::login($user);

            // 📧 Send Welcome Email via Brevo
            try {
                $plan = SubscriptionPlan::find($request->plan_id);
                $planName = $plan ? $plan->name : 'Plano Básico';
                app(\App\Services\BrevoService::class)->sendWelcomeEmail($user, $planName);
            } catch (\Exception $e) {
                \Log::error('Erro ao enviar e-mail de boas-vindas: ' . $e->getMessage());
            }

            if ($request->plan_id) {
                return redirect('/dashboard')->with('success', 'Sua conta foi criada! Você tem 7 dias de teste grátis.');
            }

            return redirect('/dashboard');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Erro ao criar conta: ' . $e->getMessage())->withInput();
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
