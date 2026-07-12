<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WhatsappConversation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard admin de consumo WhatsApp Cloud API (Fase 5.1).
 *
 * Mostra agregados de conversas faturaveis por categoria/tenant/periodo,
 * custo total em USD/BRL. Sem fatura/repasse ainda — so tracking.
 */
class WhatsappBillingController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $from = now()->subDays($days)->startOfDay();
        $to   = now()->endOfDay();

        $tenantId   = $request->query('tenant_id');
        $category   = $request->query('category');

        $base = WhatsappConversation::withoutGlobalScope('tenant')
            ->billable()
            ->between($from, $to)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($category, fn ($q) => $q->where('category', $category));

        // Totais gerais (clone da base pra nao vazar wheres nas queries seguintes)
        $summary = [
            'total_conversations' => (clone $base)->count(),
            'total_cost_micros'   => (int) (clone $base)->sum('cost_usd_micros'),
        ];
        $summary['total_cost_usd'] = $summary['total_cost_micros'] / 1_000_000;
        $summary['total_cost_brl'] = $summary['total_cost_usd']
            * (float) config('whatsapp_pricing.usd_brl_rate', 5.50);

        // Breakdown por categoria
        $byCategory = (clone $base)
            ->selectRaw('category, COUNT(*) as conversations, SUM(cost_usd_micros) as cost_micros')
            ->groupBy('category')
            ->orderByDesc('cost_micros')
            ->get()
            ->map(function ($row) {
                $row->cost_usd = $row->cost_micros / 1_000_000;
                return $row;
            });

        // Top 10 tenants por custo
        $topTenants = (clone $base)
            ->selectRaw('tenant_id, COUNT(*) as conversations, SUM(cost_usd_micros) as cost_micros')
            ->groupBy('tenant_id')
            ->orderByDesc('cost_micros')
            ->limit(10)
            ->get();

        // Hidrata nomes dos tenants em 1 query
        $tenantNames = Tenant::whereIn('id', $topTenants->pluck('tenant_id'))
            ->pluck('name', 'id');

        $topTenants = $topTenants->map(function ($row) use ($tenantNames) {
            $row->tenant_name = $tenantNames[$row->tenant_id] ?? "Tenant #{$row->tenant_id}";
            $row->cost_usd    = $row->cost_micros / 1_000_000;
            return $row;
        });

        // Serie temporal diaria (grafico)
        $timeline = (clone $base)
            ->selectRaw('DATE(started_at) as day, COUNT(*) as conversations, SUM(cost_usd_micros) as cost_micros')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Lista de tenants pra filtro dropdown
        $tenants = Tenant::orderBy('name')->pluck('name', 'id');

        $categories = [
            WhatsappConversation::CATEGORY_MARKETING      => 'Marketing',
            WhatsappConversation::CATEGORY_UTILITY        => 'Utility',
            WhatsappConversation::CATEGORY_AUTHENTICATION => 'Authentication',
            WhatsappConversation::CATEGORY_SERVICE        => 'Service',
            WhatsappConversation::CATEGORY_REFERRAL       => 'Referral',
        ];

        return view('admin.whatsapp.billing.index', compact(
            'summary',
            'byCategory',
            'topTenants',
            'timeline',
            'tenants',
            'categories',
            'days',
            'tenantId',
            'category'
        ));
    }
}
