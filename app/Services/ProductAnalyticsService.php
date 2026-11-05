<?php

namespace App\Services;

use App\Models\Tenant;
use App\Support\TrafficClassifier;
use App\Support\VisitClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Analytics do super admin: uso do produto pelos clientes, saúde das
 * organizações, adoção por módulo, site público e WhatsApp.
 * Fonte: page_visits (páginas realmente vistas, sem robôs nem polling).
 * Visitas do próprio admin (area = admin) ficam fora das contas.
 */
class ProductAnalyticsService
{
    public const PERIODS = [7 => '7 dias', 30 => '30 dias', 90 => '90 dias'];

    /** Organização pagante sem uso há este tanto de dias = em risco. */
    public const RISK_DAYS = 14;

    private CarbonImmutable $start;
    private CarbonImmutable $end;
    private CarbonImmutable $prevStart;
    private CarbonImmutable $prevEnd;
    private int $days;

    public function build(int $days): array
    {
        $this->days      = array_key_exists($days, self::PERIODS) ? $days : 30;
        $this->end       = CarbonImmutable::now();
        $this->start     = $this->end->startOfDay()->subDays($this->days - 1);
        $this->prevEnd   = $this->start->subSecond();
        $this->prevStart = $this->start->subDays($this->days);

        return [
            'days'     => $this->days,
            'start'    => $this->start,
            'end'      => $this->end,
            'app'      => $this->app(),
            'health'   => $this->health(),
            'modules'  => $this->modules(),
            'pages'    => $this->topPages('app', 12),
            'public'   => $this->publicSite(),
            'whatsapp' => $this->whatsapp(),
        ];
    }

    // ── Uso do produto ────────────────────────────────────────────────────

    private function app(): array
    {
        $now = CarbonImmutable::now();
        $distinctUsers = fn ($from, $to = null) => $this->visits('app', $from, $to ?? $now)->distinct()->count('user_id');

        $dau = $distinctUsers($now->startOfDay());
        $wau = $distinctUsers($now->startOfDay()->subDays(6));
        $mau = $distinctUsers($now->startOfDay()->subDays(29));

        // Retorno (stickiness): média de usuários por dia ÷ usuários do mês
        $daily30 = $this->dailySeries('app', $now->startOfDay()->subDays(29), $now, 'COUNT(DISTINCT user_id)');
        $avgDaily = count($daily30['values']) ? array_sum($daily30['values']) / count($daily30['values']) : 0;

        $orgs     = $this->visits('app', $this->start, $this->end)->whereNotNull('tenant_id')->distinct()->count('tenant_id');
        $prevOrgs = $this->visits('app', $this->prevStart, $this->prevEnd)->whereNotNull('tenant_id')->distinct()->count('tenant_id');
        $users     = $this->visits('app', $this->start, $this->end)->distinct()->count('user_id');
        $prevUsers = $this->visits('app', $this->prevStart, $this->prevEnd)->distinct()->count('user_id');
        $views     = $this->visits('app', $this->start, $this->end)->count();
        $prevViews = $this->visits('app', $this->prevStart, $this->prevEnd)->count();

        $statusCounts = Tenant::select('subscription_status', DB::raw('COUNT(*) as c'))
            ->groupBy('subscription_status')->pluck('c', 'subscription_status');

        return [
            'dau' => $dau, 'wau' => $wau, 'mau' => $mau,
            'stickiness'   => $mau > 0 ? round($avgDaily / $mau * 100, 1) : null,
            'orgs'         => $orgs,  'orgs_change'  => $this->change($orgs, $prevOrgs),
            'users'        => $users, 'users_change' => $this->change($users, $prevUsers),
            'views'        => $views, 'views_change' => $this->change($views, $prevViews),
            'paying'       => (int) (($statusCounts['active'] ?? 0) + ($statusCounts['past_due'] ?? 0)),
            'trialing'     => (int) ($statusCounts['trialing'] ?? 0),
            'total_orgs'   => (int) $statusCounts->sum(),
            'daily_users'  => $this->dailySeries('app', $this->start, $this->end, 'COUNT(DISTINCT user_id)'),
            'daily_orgs'   => $this->dailySeries('app', $this->start, $this->end, 'COUNT(DISTINCT tenant_id)'),
        ];
    }

    // ── Saúde das organizações ────────────────────────────────────────────

    private function health(): array
    {
        $tenants = Tenant::query()
            ->leftJoin('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
            ->whereIn('tenants.subscription_status', ['active', 'trialing', 'past_due'])
            ->get(['tenants.id', 'tenants.name', 'tenants.brand_name', 'tenants.subscription_status',
                   'tenants.created_at', 'tenants.trial_ends_at', 'subscription_plans.name as plan_name']);

        if ($tenants->isEmpty()) {
            return ['at_risk' => [], 'not_activated' => [], 'top' => [], 'counts' => ['at_risk' => 0, 'not_activated' => 0, 'healthy' => 0]];
        }
        $ids = $tenants->pluck('id');

        $lastSeen = $this->visits('app', null, null)->whereIn('tenant_id', $ids)
            ->groupBy('tenant_id')->select('tenant_id', DB::raw('MAX(created_at) as last_at'))
            ->pluck('last_at', 'tenant_id');

        $period = $this->visits('app', $this->start, $this->end)->whereIn('tenant_id', $ids)
            ->groupBy('tenant_id')
            ->select('tenant_id', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT user_id) as users'))
            ->get()->keyBy('tenant_id');
        $prev = $this->visits('app', $this->prevStart, $this->prevEnd)->whereIn('tenant_id', $ids)
            ->groupBy('tenant_id')->select('tenant_id', DB::raw('COUNT(*) as views'))
            ->pluck('views', 'tenant_id');
        $activeDays30 = $this->visits('app', CarbonImmutable::now()->startOfDay()->subDays(29), null)->whereIn('tenant_id', $ids)
            ->groupBy('tenant_id')->select('tenant_id', DB::raw('COUNT(DISTINCT DATE(created_at)) as d'))
            ->pluck('d', 'tenant_id');

        $riskLimit = CarbonImmutable::now()->subDays(self::RISK_DAYS);
        $rows = $tenants->map(function ($t) use ($lastSeen, $period, $prev, $activeDays30, $riskLimit) {
            $last  = isset($lastSeen[$t->id]) ? CarbonImmutable::parse($lastSeen[$t->id]) : null;
            $views = (int) ($period[$t->id]->views ?? 0);
            $isNew = $t->created_at && $t->created_at->gte(CarbonImmutable::now()->subDays(30));

            return [
                'id'          => $t->id,
                'name'        => $t->brand_name ?: ($t->name ?: "Organização #{$t->id}"),
                'status'      => $t->subscription_status,
                'plan'        => $t->plan_name,
                'created_at'  => $t->created_at,
                'last_seen'   => $last,
                'views'       => $views,
                'users'       => (int) ($period[$t->id]->users ?? 0),
                'change'      => $this->change($views, (int) ($prev[$t->id] ?? 0)),
                'active_days' => (int) ($activeDays30[$t->id] ?? 0),
                'at_risk'     => in_array($t->subscription_status, ['active', 'past_due'], true) && (! $last || $last->lt($riskLimit)),
                'not_activated' => $isNew && (int) ($activeDays30[$t->id] ?? 0) <= 1,
            ];
        });

        $atRisk = $rows->where('at_risk', true)->sortBy(fn ($r) => $r['last_seen']?->timestamp ?? 0)->values();
        $notActivated = $rows->where('not_activated', true)->sortByDesc(fn ($r) => $r['created_at']?->timestamp ?? 0)->values();

        return [
            'at_risk'       => $atRisk->take(20)->all(),
            'not_activated' => $notActivated->take(20)->all(),
            'top'           => $rows->where('views', '>', 0)->sortByDesc('views')->take(15)->values()->all(),
            'counts'        => [
                'at_risk'       => $atRisk->count(),
                'not_activated' => $notActivated->count(),
                'healthy'       => $rows->reject(fn ($r) => $r['at_risk'] || $r['not_activated'])->count(),
            ],
        ];
    }

    // ── Adoção por módulo ─────────────────────────────────────────────────

    private function modules(): array
    {
        $orgsTotal = max(1, $this->visits('app', $this->start, $this->end)->whereNotNull('tenant_id')->distinct()->count('tenant_id'));

        // Painel, configurações e "outros" não são funcionalidades: ficam fora da adoção
        $skip = ['dashboard', 'settings', 'other'];
        $now = $this->visits('app', $this->start, $this->end)->whereNotNull('module')->whereNotIn('module', $skip)
            ->groupBy('module')
            ->select('module', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT tenant_id) as orgs'))
            ->get();
        $prev = $this->visits('app', $this->prevStart, $this->prevEnd)->whereNotNull('module')
            ->groupBy('module')->select('module', DB::raw('COUNT(DISTINCT tenant_id) as orgs'))
            ->pluck('orgs', 'module');

        return $now->map(fn ($r) => [
            'key'      => $r->module,
            'label'    => VisitClassifier::moduleLabel($r->module),
            'views'    => (int) $r->views,
            'orgs'     => (int) $r->orgs,
            'adoption' => round($r->orgs / $orgsTotal * 100, 1),
            'change'   => $this->change((int) $r->orgs, (int) ($prev[$r->module] ?? 0)),
        ])->sortByDesc('orgs')->values()->all();
    }

    private function topPages(string $area, int $limit): array
    {
        return $this->visits($area, $this->start, $this->end)
            ->groupBy('path')
            ->select('path', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT tenant_id) as orgs'), DB::raw('COUNT(DISTINCT visitor) as visitors'))
            ->orderByDesc('views')->limit($limit)->get()
            ->map(fn ($r) => ['path' => '/' . ltrim($r->path, '/'), 'views' => (int) $r->views, 'orgs' => (int) $r->orgs, 'visitors' => (int) $r->visitors])
            ->all();
    }

    // ── Site público ──────────────────────────────────────────────────────

    private function publicSite(): array
    {
        $visits     = $this->visits('public', $this->start, $this->end)->count();
        $prevVisits = $this->visits('public', $this->prevStart, $this->prevEnd)->count();
        $visitors     = $this->visits('public', $this->start, $this->end)->whereNotNull('visitor')->distinct()->count('visitor');
        $prevVisitors = $this->visits('public', $this->prevStart, $this->prevEnd)->whereNotNull('visitor')->distinct()->count('visitor');

        $signups     = Tenant::whereBetween('created_at', [$this->start, $this->end])->count();
        $prevSignups = Tenant::whereBetween('created_at', [$this->prevStart, $this->prevEnd])->count();

        $since = DB::table('page_visits')->where('area', 'public')->whereNotNull('visitor')->min('created_at');

        return [
            'visits'   => $visits,   'visits_change'   => $this->change($visits, $prevVisits),
            'visitors' => $visitors, 'visitors_change' => $this->change($visitors, $prevVisitors),
            'signups'  => $signups,  'signups_change'  => $this->change($signups, $prevSignups),
            'conversion' => $visitors > 0 ? round($signups / $visitors * 100, 2) : null,
            'tracking_since' => $since ? CarbonImmutable::parse($since) : null,
            'daily'    => $this->dailySeries('public', $this->start, $this->end, 'COUNT(*)'),
            'sources'  => $this->breakdown('source', fn ($k) => TrafficClassifier::sourceLabel($k)),
            'devices'  => $this->breakdown('device', fn ($k) => TrafficClassifier::deviceLabel($k)),
            'pages'    => $this->topPages('public', 10),
        ];
    }

    private function breakdown(string $column, callable $label): array
    {
        $rows = $this->visits('public', $this->start, $this->end)->whereNotNull($column)
            ->groupBy($column)->select("{$column} as k", DB::raw('COUNT(*) as c'))
            ->orderByDesc('c')->orderBy('k')->get();
        $total = max(1, (int) $rows->sum('c'));

        return $rows->map(fn ($r) => ['key' => $r->k, 'label' => $label($r->k), 'count' => (int) $r->c, 'pct' => round($r->c / $total * 100, 1)])->all();
    }

    // ── WhatsApp ──────────────────────────────────────────────────────────

    private function whatsapp(): array
    {
        $count = fn ($from, $to, $dir) => DB::table('whatsapp_messages')->whereBetween('created_at', [$from, $to])->where('direction', $dir)->count();

        $in  = $count($this->start, $this->end, 'inbound');
        $out = $count($this->start, $this->end, 'outbound');

        $series = function (string $dir) {
            $rows = DB::table('whatsapp_messages')->where('direction', $dir)
                ->whereBetween('created_at', [$this->start, $this->end])
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
            return $this->fill($rows);
        };

        return [
            'inbound'  => $in,  'inbound_change'  => $this->change($in, $count($this->prevStart, $this->prevEnd, 'inbound')),
            'outbound' => $out, 'outbound_change' => $this->change($out, $count($this->prevStart, $this->prevEnd, 'outbound')),
            'daily_in'  => $series('inbound'),
            'daily_out' => $series('outbound'),
        ];
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function visits(string $area, $from, $to)
    {
        return DB::table('page_visits')->where('area', $area)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }

    /** @return array{labels: string[], values: int[]} */
    private function dailySeries(string $area, CarbonImmutable $from, CarbonImmutable $to, string $aggregate): array
    {
        $rows = $this->visits($area, $from, $to)
            ->selectRaw("DATE(created_at) as d, {$aggregate} as c")
            ->groupBy('d')->pluck('c', 'd');

        return $this->fill($rows, $from, $to);
    }

    private function fill($rows, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $from = ($from ?? $this->start)->startOfDay();
        $to   = ($to ?? $this->end)->startOfDay();
        $labels = $values = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $labels[] = $d->format('d/m');
            $values[] = (int) ($rows[$d->toDateString()] ?? 0);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function change(int $now, int $before): ?float
    {
        if ($before === 0) {
            return $now > 0 ? null : 0.0;
        }
        return round(($now - $before) / $before * 100, 1);
    }
}
