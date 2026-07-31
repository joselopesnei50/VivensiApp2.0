<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WhatsappQuotaUsage;
use App\Services\WhatsAppService\WhatsappQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Painel super_admin — gestão de cotas WhatsApp Cloud (Modelo comercial C).
 *
 * Escopo Fase 1:
 *  - Listar tenants com uso do mês vs cota do plano vs packs extras
 *  - Adicionar pack extra manual (após confirmar pagamento fora)
 *  - Ajuste manual (crédito/débito de conversas)
 *
 * Fase 2 (backlog): compra self-service de pack extra via AbacatePay
 * (webhook cria evento extra_pack automático, este painel vira relatório).
 */
class WhatsappQuotasController extends Controller
{
    public function index(Request $request): View
    {
        // Todos tenants + usage (left join in-memory pra listar todos)
        $tenants = Tenant::with('plan')->orderBy('name')->get()->map(function ($tenant) {
            $usage = WhatsappQuotaUsage::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->first();
            $tenant->quota_usage = $usage;
            return $tenant;
        });

        // Stats
        $usages = WhatsappQuotaUsage::withoutGlobalScopes()->get();
        $stats = [
            'active_tenants'   => $tenants->filter(fn($t) => ($t->plan?->whatsapp_conversations_included ?? 0) > 0)->count(),
            'near_quota'       => $usages->filter(fn($u) => $u->isNearQuota())->count(),
            'over_quota'       => $usages->filter(fn($u) => $u->isOverQuota() && $u->totalAvailable() > 0)->count(),
            'total_used_month' => (int) $usages->sum('conversations_used_month'),
        ];

        return view('admin.whatsapp.cotas.index', [
            'tenants' => $tenants,
            'stats'   => $stats,
        ]);
    }

    /** Adiciona pack extra pra um tenant (admin manual). */
    public function addExtraPack(Tenant $tenant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'conversations' => 'required|integer|min:1|max:100000',
            'description'   => 'required|string|max:400',
        ]);

        $service = app(WhatsappQuotaService::class);
        $ev = $service->addExtraPack(
            $tenant->id,
            (int) $data['conversations'],
            $data['description'],
            auth()->id()
        );

        return back()->with('success', "Pack extra de +{$data['conversations']} conversas aplicado a {$tenant->name}. Total pack extra agora: {$ev->extra_pack_after}.");
    }

    /** Ajuste manual (positivo ou negativo). */
    public function adjustment(Tenant $tenant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delta'       => 'required|integer|not_in:0|min:-100000|max:100000',
            'description' => 'required|string|max:400',
        ]);

        $service = app(WhatsappQuotaService::class);
        $service->adjustment(
            $tenant->id,
            (int) $data['delta'],
            $data['description'],
            auth()->id()
        );

        return back()->with('success', "Ajuste de {$data['delta']} conversas aplicado a {$tenant->name}.");
    }
}
