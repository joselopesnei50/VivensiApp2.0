<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConversation;
use App\Models\WhatsappCreditTransaction;
use App\Services\WhatsAppService\WhatsappCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dashboard de consumo WhatsApp pro CLIENTE (tenant scope aplicado
 * automaticamente pela trait BelongsToTenant no WhatsappConversation).
 *
 * Modelo comercial B (default do Vivensi): cliente paga a Meta direto
 * pelo cartão vinculado à WABA dele — o Vivensi só mostra o consumo
 * pra transparência. Sem markup, sem cobrança nossa em cima.
 *
 * Se um dia migrar pra Modelo A (BSP com linha de crédito Vivensi),
 * basta trocar o `showMarkup` pra true no controller (a view já suporta).
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

        // Saldo pré-pago + extrato (Fase 1 modelo comercial A).
        // Se tenant não é pré-pago, balance está zero e prepaid_enabled_at
        // null — a view mostra card cinza "modelo transparência".
        $credit      = app(WhatsappCreditService::class);
        $balance     = $credit->getBalance($tenantId);
        $transactions = WhatsappCreditTransaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit(25)
            ->get();

        return view('whatsapp.consumo', [
            'summary'      => $summary,
            'byCategory'   => $byCategory,
            'timeline'     => $timeline,
            'days'         => $days,
            'from'         => $from,
            'to'           => $to,
            'usdBrlRate'   => (float) config('whatsapp_pricing.usd_brl_rate', 5.50),
            'balance'      => $balance,
            'transactions' => $transactions,
            'suggestedTopups' => (array) config('whatsapp_pricing.prepaid.suggested_topups', [50, 100, 250, 500]),
        ]);
    }
}
