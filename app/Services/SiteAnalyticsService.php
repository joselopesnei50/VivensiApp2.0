<?php

namespace App\Services;

use App\Support\TrafficClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Métricas de acesso ao sistema (site público + área logada), no mesmo
 * formato das métricas do blog. Fonte: page_visits — só páginas realmente
 * vistas, sem robôs, polling ou erros. Acessos do super admin ficam de fora.
 */
class SiteAnalyticsService
{
    public const PERIODS = [7 => '7 dias', 30 => '30 dias', 90 => '90 dias', 365 => '12 meses'];

    public const AREAS = ['tudo' => 'Tudo', 'public' => 'Site Vivensi', 'app' => 'Área logada', 'clients' => 'Páginas dos clientes'];

    private const ALL = ['public', 'app', 'clients'];

    public function summary(int $days, string $area = 'tudo'): array
    {
        $days  = array_key_exists($days, self::PERIODS) ? $days : 30;
        $area  = array_key_exists($area, self::AREAS) ? $area : 'tudo';
        $end   = CarbonImmutable::now()->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $prevStart = $start->subDays($days);
        $prevEnd   = $start->subSecond();

        $views     = $this->base($area, $start, $end)->count();
        $prevViews = $this->base($area, $prevStart, $prevEnd)->count();
        $visitors     = $this->uniqueVisitors($area, $start, $end);
        $prevVisitors = $this->uniqueVisitors($area, $prevStart, $prevEnd);
        $logged     = $this->base($area, $start, $end)->whereNotNull('user_id')->distinct()->count('user_id');
        $prevLogged = $this->base($area, $prevStart, $prevEnd)->whereNotNull('user_id')->distinct()->count('user_id');

        $since = DB::table('page_visits')->whereNotNull('device')->min('created_at');

        return [
            'days'      => $days,
            'area'      => $area,
            'start'     => $start,
            'end'       => $end,
            'views'     => $views,    'views_change'    => $this->change($views, $prevViews),
            'prev_views'=> $prevViews,
            'visitors'  => $visitors, 'visitors_change' => $this->change($visitors, $prevVisitors),
            'logged'    => $logged,   'logged_change'   => $this->change($logged, $prevLogged),
            'per_visitor' => $visitors > 0 ? round($views / $visitors, 1) : null,
            'today'     => $this->base($area, CarbonImmutable::now()->startOfDay(), $end)->count(),
            'daily'     => $this->daily($area, $start, $end),
            'hours'     => $this->hours($area, $start, $end),
            'areas'     => $this->areaSplit($start, $end),
            'sources'   => $this->breakdown('source', $area, $start, $end, fn ($k) => TrafficClassifier::sourceLabel($k)),
            'devices'   => $this->breakdown('device', $area, $start, $end, fn ($k) => TrafficClassifier::deviceLabel($k)),
            'pages'     => $this->topPages($area, $start, $end, $prevStart, $prevEnd),
            'referrers' => $this->base($area, $start, $end)->whereNotNull('referrer_host')
                ->groupBy('referrer_host')->select('referrer_host', DB::raw('COUNT(*) as c'))
                ->orderByDesc('c')->limit(8)->pluck('c', 'referrer_host')->map(fn ($c) => (int) $c)->all(),
            'tracking_since' => $since ? CarbonImmutable::parse($since) : null,
            'orgs'      => $this->topOrgs($start, $end, $prevStart, $prevEnd),
            'whatsapp'  => $this->whatsapp($start, $end, $prevStart, $prevEnd),
        ];
    }

    /** Organizações com mais acessos na área logada. */
    private function topOrgs($start, $end, $prevStart, $prevEnd): array
    {
        $now = $this->base('app', $start, $end)->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->select('tenant_id', DB::raw('COUNT(*) as v'), DB::raw('COUNT(DISTINCT user_id) as u'))
            ->orderByDesc('v')->limit(10)->get();
        if ($now->isEmpty()) {
            return [];
        }
        $prev = $this->base('app', $prevStart, $prevEnd)->whereIn('tenant_id', $now->pluck('tenant_id'))
            ->groupBy('tenant_id')->select('tenant_id', DB::raw('COUNT(*) as v'))->pluck('v', 'tenant_id');
        $names = DB::table('tenants')->whereIn('id', $now->pluck('tenant_id'))->get(['id', 'name', 'brand_name'])->keyBy('id');

        return $now->map(fn ($r) => [
            'id'     => (int) $r->tenant_id,
            'name'   => ($names[$r->tenant_id]->brand_name ?? null) ?: (($names[$r->tenant_id]->name ?? null) ?: "Organização #{$r->tenant_id}"),
            'views'  => (int) $r->v,
            'users'  => (int) $r->u,
            'change' => $this->change((int) $r->v, (int) ($prev[$r->tenant_id] ?? 0)),
        ])->all();
    }

    private function whatsapp($start, $end, $prevStart, $prevEnd): array
    {
        $count = fn ($from, $to, $dir) => DB::table('whatsapp_messages')->whereBetween('created_at', [$from, $to])->where('direction', $dir)->count();
        $in = $count($start, $end, 'inbound');
        $out = $count($start, $end, 'outbound');

        return [
            'inbound'  => $in,  'inbound_change'  => $this->change($in, $count($prevStart, $prevEnd, 'inbound')),
            'outbound' => $out, 'outbound_change' => $this->change($out, $count($prevStart, $prevEnd, 'outbound')),
        ];
    }

    private function base(string $area, $start, $end)
    {
        return DB::table('page_visits')
            ->when($area === 'tudo', fn ($q) => $q->whereIn('area', self::ALL), fn ($q) => $q->where('area', $area))
            ->whereBetween('created_at', [$start, $end]);
    }

    /** Visitante único = usuário logado ("u{id}") ou sessão anônima. Visitas anônimas antigas não têm esse dado. */
    private function uniqueVisitors(string $area, $start, $end): int
    {
        return $this->base($area, $start, $end)->whereNotNull('visitor')->distinct()->count('visitor');
    }

    /** @return array{labels: string[], views: int[], visitors: int[]} */
    private function daily(string $area, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $this->base($area, $start, $end)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as v, COUNT(DISTINCT visitor) as u')
            ->groupBy('d')->get()->keyBy('d');

        $labels = $views = $visitors = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $key = $day->toDateString();
            $labels[]   = $day->format('d/m');
            $views[]    = (int) ($rows[$key]->v ?? 0);
            $visitors[] = (int) ($rows[$key]->u ?? 0);
        }
        return ['labels' => $labels, 'views' => $views, 'visitors' => $visitors];
    }

    /** Visitas por hora do dia (0–23), no fuso do sistema. */
    private function hours(string $area, $start, $end): array
    {
        $hourExpr = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H', created_at) AS INTEGER)" : 'HOUR(created_at)';
        $rows = $this->base($area, $start, $end)
            ->selectRaw("{$hourExpr} as h, COUNT(*) as c")->groupBy('h')->pluck('c', 'h');

        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = (int) ($rows[$h] ?? 0);
        }
        return $out;
    }

    private function areaSplit($start, $end): array
    {
        $rows = DB::table('page_visits')->whereIn('area', self::ALL)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('area')->select('area', DB::raw('COUNT(*) as c'))->pluck('c', 'area');
        $total = max(1, (int) $rows->sum());

        return collect(self::ALL)->map(fn ($k) => [
            'key' => $k, 'label' => self::AREAS[$k], 'count' => (int) ($rows[$k] ?? 0),
            'pct' => round(($rows[$k] ?? 0) / $total * 100, 1),
        ])->all();
    }

    private function breakdown(string $column, string $area, $start, $end, callable $label): array
    {
        $rows = $this->base($area, $start, $end)->whereNotNull($column)
            ->groupBy($column)->select("{$column} as k", DB::raw('COUNT(*) as c'))
            ->orderByDesc('c')->orderBy('k')->get();
        $total = max(1, (int) $rows->sum('c'));

        return $rows->map(fn ($r) => [
            'key' => $r->k, 'label' => $label($r->k), 'count' => (int) $r->c, 'pct' => round($r->c / $total * 100, 1),
        ])->all();
    }

    private function topPages(string $area, $start, $end, $prevStart, $prevEnd): array
    {
        $now = $this->base($area, $start, $end)
            ->groupBy('path', 'area')
            ->select('path', 'area', DB::raw('COUNT(*) as v'), DB::raw('COUNT(DISTINCT visitor) as u'))
            ->orderByDesc('v')->limit(20)->get();
        if ($now->isEmpty()) {
            return [];
        }

        $prev = $this->base($area, $prevStart, $prevEnd)->whereIn('path', $now->pluck('path'))
            ->groupBy('path', 'area')->select('path', 'area', DB::raw('COUNT(*) as v'))->get()
            ->mapWithKeys(fn ($r) => [$r->area . '|' . $r->path => (int) $r->v]);
        $total = max(1, $this->base($area, $start, $end)->count());

        return $now->map(fn ($r) => [
            'path'     => '/' . ltrim($r->path, '/'),
            'area'     => $r->area,
            'views'    => (int) $r->v,
            'visitors' => (int) $r->u,
            'share'    => round($r->v / $total * 100, 1),
            'change'   => $this->change((int) $r->v, (int) ($prev[$r->area . '|' . $r->path] ?? 0)),
        ])->all();
    }

    private function change(int $now, int $before): ?float
    {
        if ($before === 0) {
            return $now > 0 ? null : 0.0;
        }
        return round(($now - $before) / $before * 100, 1);
    }
}
