<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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

        // 3. Check Tenant Subscription
        $tenant = $user->tenant;
        
        if (!$tenant) {
            return $next($request); // Should not happen based on app logic
        }

        // 4. Exception routes (to avoid infinite redirect loops)
        if ($request->routeIs('checkout.*') || $request->routeIs('logout') || $request->is('support*')) {
            return $next($request);
        }


        // 5. Active or Trialing logic
        if ($tenant->subscription_status === 'active') {
            return $next($request);
        }

        if ($tenant->subscription_status === 'trialing' && $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
            return $next($request);
        }

        // 6. If they have a plan but it's pending, redirect to checkout/success to remind them
        if ($tenant->plan_id && $tenant->subscription_status === 'pending') {
             // We can use a session flash to remind them, or just let them into some restricted view
             // For now, let's redirect to checkout if they try to access main features
             return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
                ->with('error', 'Sua assinatura está pendente de pagamento.');
        }

        // 7. If no plan selected, redirect to checkout to choose one (Defaulting to Plan 1 - Basic)
        if (!$tenant->plan_id) {
            // Ideally we should have a 'plans' page, but checkout/1 serves as a prompt
            // We set plan_id to 1 provisionally
            $tenant->update(['plan_id' => 1]); 
            return redirect()->route('checkout.index', ['plan_id' => 1])
                ->with('warning', 'Sua conta não possui um plano ativo. Por favor, confirme sua assinatura.');
        }

        if ($tenant->subscription_status === 'trialing' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
            return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
                ->with('error', 'Seu período de teste de 7 dias chegou ao fim. Realize o pagamento para desbloquear seu acesso completo.');
        }

        return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id ?? 1])
            ->with('error', 'Assinatura necessária para acessar este recurso.');
    }
}
