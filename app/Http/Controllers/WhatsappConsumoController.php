<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConversation;
use App\Models\WhatsappQuotaEvent;
use App\Services\WhatsAppService\WhatsappQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dashboard de consumo WhatsApp pro CLIENTE (tenant scope automático).
 *
 * Modelo comercial C (2026-07-30): cliente paga Meta direto pelo cartão
 * da WABA dele + paga assinatura mensal ao Vivensi (plano inclui X
 * conversas). Se usar mais, compra pack extra do Vivensi via /consumo.
 * Envio bloqueado quando estoura cota + packs.
 */
class WhatsappConsumoController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-whatsapp');

        $tenantId = (int) auth()->user()->tenant_id;

        // Período: mês corrente por default; ?days=7|30|90 aceito
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $from = now()->subDays($days)->startOfDay();
        $to   = now()->endOfDay();

        // Query base — tenant scope já filtra automático pelo BelongsToTenant.
        // Só conversas billable (Meta marca is_billable=false pra service window).
        $base = WhatsappConversation::billable()->between($from, $to);

        // Totais gerais
        $summary = [
            'total_conversations' => (clone $base)->count(),
            'total_cost_micros'   => (int) (clone $base)->sum('cost_usd_micros'),
        ];
        $summary['total_cost_usd'] = $summary['total_cost_micros'] / 1_000_000;
        $summary['total_cost_brl'] = $summary['total_cost_usd']
            * (float) config('whatsapp_pricing.usd_brl_rate', 5.50);

        // Breakdown por categoria (marketing/utility/authentication/service)
        $byCategory = (clone $base)
            ->selectRaw('category, COUNT(*) as conversations, SUM(cost_usd_micros) as cost_micros')
            ->groupBy('category')
            ->orderByDesc('cost_micros')
            ->get()
            ->map(function ($row) {
                $row->cost_usd = $row->cost_micros / 1_000_000;
                $row->cost_brl = $row->cost_usd * (float) config('whatsapp_pricing.usd_brl_rate', 5.50);
                return $row;
            });

        // Timeline diária (últimos $days dias)
        $timeline = (clone $base)
            ->selectRaw('DATE(started_at) as day, COUNT(*) as conversations, SUM(cost_usd_micros) as cost_micros')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(function ($row) {
                $row->cost_brl = ($row->cost_micros / 1_000_000)
                    * (float) config('whatsapp_pricing.usd_brl_rate', 5.50);
                return $row;
            });

        // Cota mensal (Modelo comercial C). getUsage cria on-demand com
        // snapshot do plano atual. Se plano tem 0 conversas inclusas, view
        // mostra card cinza "módulo indisponível no seu plano".
        $quotaService = app(WhatsappQuotaService::class);
        $usage        = $quotaService->getUsage($tenantId);
        $quotaEvents  = WhatsappQuotaEvent::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(25)
            ->get();

        // Info do plano pra card de pack extra
        $tenant = auth()->user()->tenant;
        $plan   = $tenant?->plan;

        return view('whatsapp.consumo', [
            'summary'     => $summary,
            'byCategory'  => $byCategory,
            'timeline'    => $timeline,
            'days'        => $days,
            'from'        => $from,
            'to'          => $to,
            'usdBrlRate'  => (float) config('whatsapp_pricing.usd_brl_rate', 5.50),
            'usage'       => $usage,
            'quotaEvents' => $quotaEvents,
            'plan'        => $plan,
        ]);
    }
}
