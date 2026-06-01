<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Models\Tenant;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today  = now()->startOfDay();
        $week   = now()->startOfWeek();
        $month  = now()->startOfMonth();
        $last30 = now()->subDays(29)->startOfDay();

        // ── Acessos ──────────────────────────────────────────────────────────
        $visitsToday = PageVisit::where('created_at', '>=', $today)->count();
        $visitsWeek  = PageVisit::where('created_at', '>=', $week)->count();
        $visitsMonth = PageVisit::where('created_at', '>=', $month)->count();

        $usersToday = PageVisit::where('created_at', '>=', $today)->whereNotNull('user_id')->distinct('user_id')->count();
        $usersWeek  = PageVisit::where('created_at', '>=', $week)->whereNotNull('user_id')->distinct('user_id')->count();
        $usersMonth = PageVisit::where('created_at', '>=', $month)->whereNotNull('user_id')->distinct('user_id')->count();

        // Visitas por dia — últimos 30 dias
        $dailyRaw = PageVisit::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day');

        $dailyLabels = [];
        $dailyData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day           = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('d/m');
            $dailyData[]   = (int) ($dailyRaw[$day] ?? 0);
        }

        // ── WhatsApp ─────────────────────────────────────────────────────────
        $waMsgToday = WhatsappMessage::withoutGlobalScopes()->where('created_at', '>=', $today)->count();
        $waMsgWeek  = WhatsappMessage::withoutGlobalScopes()->where('created_at', '>=', $week)->count();
        $waMsgMonth = WhatsappMessage::withoutGlobalScopes()->where('created_at', '>=', $month)->count();

        $waInboundToday  = WhatsappMessage::withoutGlobalScopes()->where('created_at', '>=', $today)->where('direction', 'inbound')->count();
        $waOutboundToday = WhatsappMessage::withoutGlobalScopes()->where('created_at', '>=', $today)->where('direction', 'outbound')->count();

        // Mensagens por dia últimos 30 dias (inbound + outbound separados)
        $waInboundRaw = WhatsappMessage::withoutGlobalScopes()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)->where('direction', 'inbound')
            ->groupBy('day')->pluck('total', 'day');

        $waOutboundRaw = WhatsappMessage::withoutGlobalScopes()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)->where('direction', 'outbound')
            ->groupBy('day')->pluck('total', 'day');

        $waInboundData  = [];
        $waOutboundData = [];
        for ($i = 29; $i >= 0; $i--) {
            $day              = now()->subDays($i)->format('Y-m-d');
            $waInboundData[]  = (int) ($waInboundRaw[$day]  ?? 0);
            $waOutboundData[] = (int) ($waOutboundRaw[$day] ?? 0);
        }

        // ── Tenants ativos ────────────────────────────────────────────────────
        $activeTenantsCount = PageVisit::where('created_at', '>=', $last30)
            ->whereNotNull('tenant_id')
            ->distinct('tenant_id')
            ->count();

        $totalTenants = Tenant::count();

        // Top tenants por acessos (últimos 30 dias)
        $topTenants = PageVisit::select('tenant_id', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)
            ->whereNotNull('tenant_id')
            ->groupBy('tenant_id')->orderByDesc('total')->limit(10)
            ->get()->map(function ($row) {
                $tenant    = Tenant::find($row->tenant_id);
                $row->name = $tenant?->brand_name ?: $tenant?->name ?: "Tenant #{$row->tenant_id}";
                return $row;
            });

        // Top páginas (últimos 30 dias)
        $topPages = PageVisit::select('path', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)
            ->groupBy('path')->orderByDesc('total')->limit(15)
            ->get();

        $totalRecords = PageVisit::count();

        return view('admin.analytics.index', compact(
            'visitsToday', 'visitsWeek', 'visitsMonth',
            'usersToday', 'usersWeek', 'usersMonth',
            'dailyLabels', 'dailyData',
            'waMsgToday', 'waMsgWeek', 'waMsgMonth',
            'waInboundToday', 'waOutboundToday',
            'waInboundData', 'waOutboundData',
            'activeTenantsCount', 'totalTenants',
            'topPages', 'topTenants', 'totalRecords'
        ));
    }
}
