<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // 1. Bypass if not logged in (other middleware handles this)
        if (!$user) {
            return $next($request);
        }

        // 2. Bypass Super Admin
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // 3. Check Tenant Subscription (cached 5 min)
        $tenant = Cache::remember("tenant.{$user->tenant_id}", 300, fn () => Tenant::find($user->tenant_id));
        
        if (!$tenant) {
            return $next($request); // Should not happen based on app logic
        }

        // 4. Exception routes (to avoid infinite redirect loops)
        if ($request->routeIs('checkout.*') || $request->routeIs('logout') || $request->is('support*')) {
            return $next($request);
        }

        // 4b. Planos de cortesia — não exigem pagamento, liberação total.
        if ($tenant->plan && $tenant->plan->is_courtesy) {
            return $next($request);
        }

        // 5. Check if suspended
        if ($tenant->subscription_status === 'suspended') {
            Cache::forget("tenant.{$user->tenant_id}");
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Sua organização foi suspensa temporariamente. Entre em contato com o suporte para regularizar o acesso.');
        }

        // 6. Active or Trialing logic
        if ($tenant->subscription_status === 'active') {
            return $next($request);
        }

        if ($tenant->subscription_status === 'trialing' && optional($tenant->trial_ends_at)->isFuture()) {
            return $next($request);
        }

        // 6. If they have a plan but it's pending, redirect to checkout/success to remind them
        if ($tenant->plan_id && $tenant->subscription_status === 'pending') {
             // We can use a session flash to remind them, or just let them into some restricted view
             // For now, let's redirect to checkout if they try to access main features
             return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
                ->with('error', 'Sua assinatura está pendente de pagamento.');
        }

        // 7. Sem plano vinculado — mostra tela de escolha de plano.
        // Nada de forçar plan_id=1 (pode nem existir/estar inativo).
        if (!$tenant->plan_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->with('error', 'Sua conta ainda não tem plano vinculado. Escolha um plano para acessar.');
        }

        if ($tenant->subscription_status === 'trialing' && optional($tenant->trial_ends_at)->isPast()) {
            return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
                ->with('error', 'Seu período de teste chegou ao fim. Realize o pagamento para desbloquear o acesso.');
        }

        return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
            ->with('error', 'Pagamento necessário para acessar o sistema.');
    }
}
