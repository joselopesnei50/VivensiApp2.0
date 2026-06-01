<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today     = now()->startOfDay();
        $week      = now()->startOfWeek();
        $month     = now()->startOfMonth();
        $last30    = now()->subDays(29)->startOfDay();

        // Totais de visitas
        $visitsToday  = PageVisit::where('created_at', '>=', $today)->count();
        $visitsWeek   = PageVisit::where('created_at', '>=', $week)->count();
        $visitsMonth  = PageVisit::where('created_at', '>=', $month)->count();

        // Usuários únicos
        $usersToday   = PageVisit::where('created_at', '>=', $today)->whereNotNull('user_id')->distinct('user_id')->count();
        $usersWeek    = PageVisit::where('created_at', '>=', $week)->whereNotNull('user_id')->distinct('user_id')->count();
        $usersMonth   = PageVisit::where('created_at', '>=', $month)->whereNotNull('user_id')->distinct('user_id')->count();

        // Visitas por dia (últimos 30 dias) — para o gráfico
        $dailyRaw = PageVisit::select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $last30)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Preencher dias sem visitas com zero
        $dailyLabels = [];
        $dailyData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('d/m');
            $dailyData[]   = (int) ($dailyRaw[$day] ?? 0);
        }

        // Top 15 páginas (últimos 30 dias)
        $topPages = PageVisit::select('path', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        // Tenants mais ativos (últimos 30 dias)
        $topTenants = PageVisit::select('tenant_id', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $last30)
            ->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $tenant = Tenant::find($row->tenant_id);
                $row->name = $tenant?->brand_name ?: $tenant?->name ?: "Tenant #{$row->tenant_id}";
                return $row;
            });

        // Total geral de registros (para info de armazenamento)
        $totalRecords = PageVisit::count();

        return view('admin.analytics.index', compact(
            'visitsToday', 'visitsWeek', 'visitsMonth',
            'usersToday', 'usersWeek', 'usersMonth',
            'dailyLabels', 'dailyData',
            'topPages', 'topTenants', 'totalRecords'
        ));
    }
}
