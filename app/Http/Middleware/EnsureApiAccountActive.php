<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Billing\InvoiceService;
use App\Support\TeamAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * API v1 (token): as mesmas regras do painel, que a API não tinha
 * (auditoria 26/11/2026, item 6).
 *  - pessoa suspensa ou inativa não usa o token;
 *  - conta da organização com pendência (suspensa, cancelada, teste vencido,
 *    contrato pendente, fatura bloqueada) não usa a API;
 *  - Coordenação/Colaborador de ONG só acessam pela API o que veem no painel.
 */
class EnsureApiAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || $user->role === 'super_admin') {
            return $next($request);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Acesso suspenso.'], 403);
        }

        $tenant = Tenant::find($user->tenant_id);
        if (!$tenant || !$this->accountUsable($tenant)) {
            return response()->json(['message' => 'A conta da organização está com pendência. Regularize no painel para usar a API.'], 402);
        }

        // api/v1/ngo/donors → ngo/donors ; api/v1/transactions → transactions
        $panelPath = preg_replace('#^api/v1/#', '', trim($request->path(), '/'));
        if (!TeamAccess::allowsPath($user, $panelPath)) {
            return response()->json(['message' => 'Seu perfil não tem acesso a esta área.'], 403);
        }

        return $next($request);
    }

    /** Mesma regra que o painel usa para a equipe (CheckSubscription). */
    private function accountUsable(Tenant $tenant): bool
    {
        if ($tenant->subscription_status === 'awaiting_contract' || $tenant->subscription_status === 'suspended') {
            return false;
        }
        if ($tenant->plan && $tenant->plan->is_courtesy) {
            return true;
        }
        if ($tenant->subscription_status === 'active') {
            return !app(InvoiceService::class)->isBlocked($tenant);
        }
        return $tenant->subscription_status === 'trialing' && optional($tenant->trial_ends_at)->isFuture();
    }
}
