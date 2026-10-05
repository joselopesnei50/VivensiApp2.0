<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Dashboard de consumo WhatsApp pro CLIENTE (tenant scope automatico).
 *
 * Analytics puro: mostra conversas billable registradas pelo webhook Meta.
 * Cliente paga Meta diretamente — Vivensi nao cobra por volume.
 */
class WhatsappConsumoController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-whatsapp');

        // Periodo: ?days=7|30|90 (default 30)
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $from = now()->subDays($days)->startOfDay();
        $to   = now()->endOfDay();

        $base = WhatsappConversation::billable()->between($from, $to);

        $summary = [
            'total_conversations' => (clone $base)->count(),
            'total_cost_micros'   => (int) (clone $base)->sum('cost_usd_micros'),
        ];
        $summary['total_cost_usd'] = $summary['total_cost_micros'] / 1_000_000;
        $summary['total_cost_brl'] = $summary['total_cost_usd']
            * (float) config('whatsapp_pricing.usd_brl_rate', 5.50);

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

        return view('whatsapp.consumo', [
            'summary'    => $summary,
            'byCategory' => $byCategory,
            'timeline'   => $timeline,
            'days'       => $days,
            'from'       => $from,
            'to'         => $to,
            'usdBrlRate' => (float) config('whatsapp_pricing.usd_brl_rate', 5.50),
        ]);
    }
}
