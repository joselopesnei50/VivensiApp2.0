<?php

namespace App\Services;

use App\Models\Post;
use App\Support\TrafficClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Métricas do blog para o painel do super admin. Só conta pessoas
 * (is_bot = false); robôs aparecem à parte, como informação.
 *
 * Uma "visita" = 1 IP num post por dia (a deduplicação é feita no registro).
 */
class BlogMetricsService
{
    public const PERIODS = [7 => '7 dias', 30 => '30 dias', 90 => '90 dias', 365 => '12 meses'];

    /**
     * @return array<string,mixed>
     */
    public function summary(int $days, ?int $postId = null): array
    {
        $days  = array_key_exists($days, self::PERIODS) ? $days : 30;
        $end   = CarbonImmutable::now()->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $prevStart = $start->subDays($days);
        $prevEnd   = $start->subSecond();

        $views     = $this->countHumans($start, $end, $postId);
        $prevViews = $this->countHumans($prevStart, $prevEnd, $postId);

        return [
            'days'          => $days,
            'start'         => $start,
            'end'           => $end,
            'views'         => $views,
            'prev_views'    => $prevViews,
            'change'        => $this->change($views, $prevViews),
            'readers'       => $this->base($start, $end, $postId)->distinct()->count('ip_address'),
            'bots'          => DB::table('post_views')->where('is_bot', true)
                                    ->whereBetween('created_at', [$start, $end])
                                    ->when($postId, fn ($q) => $q->where('post_id', $postId))
                                    ->count(),
            'all_time'      => $this->base(null, null, $postId)->count(),
            'published'     => $postId ? null : Post::where('is_published', true)
                                    ->whereBetween('published_at', [$start, $end])->count(),
            'daily'         => $this->daily($start, $end, $postId),
            'sources'       => $this->breakdown('source', $start, $end, $postId, fn ($k) => TrafficClassifier::sourceLabel($k)),
            'devices'       => $this->breakdown('device', $start, $end, $postId, fn ($k) => TrafficClassifier::deviceLabel($k)),
            'top_posts'     => $postId ? [] : $this->topPosts($start, $end, $prevStart, $prevEnd),
            'top_referers'  => $this->topReferers($start, $end, $postId),
        ];
    }

    /** Visitas de pessoas por post (todo o período), para a lista do Blog CMS. */
    public function humanViewsByPost(): array
    {
        return DB::table('post_views')->where('is_bot', false)
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->groupBy('post_id')
            ->pluck('total', 'post_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function base($start, $end, ?int $postId)
    {
        return DB::table('post_views')
            ->where('is_bot', false)
            ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
            ->when($postId, fn ($q) => $q->where('post_id', $postId));
    }

    private function countHumans($start, $end, ?int $postId): int
    {
        return $this->base($start, $end, $postId)->count();
    }

    private function change(int $now, int $before): ?float
    {
        if ($before === 0) {
            return $now > 0 ? null : 0.0; // null = "novo" (sem base de comparação)
        }
        return round(($now - $before) / $before * 100, 1);
    }

    /** @return array{labels: string[], values: int[]} */
    private function daily(CarbonImmutable $start, CarbonImmutable $end, ?int $postId): array
    {
        $rows = $this->base($start, $end, $postId)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $values = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $key      = $day->toDateString();
            $labels[] = $day->format('d/m');
            $values[] = (int) ($rows[$key] ?? 0);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array<int, array{key: string, label: string, count: int, pct: float}> */
    private function breakdown(string $column, $start, $end, ?int $postId, callable $label): array
    {
        $rows  = $this->base($start, $end, $postId)
            ->selectRaw("COALESCE({$column}, 'unknown') as k, COUNT(*) as c")
            ->groupBy('k')
            ->orderByDesc('c')->orderBy('k')
            ->get();
        $total = max(1, (int) $rows->sum('c'));

        return $rows->map(fn ($r) => [
            'key'   => $r->k,
            'label' => $label($r->k === 'unknown' ? null : $r->k),
            'count' => (int) $r->c,
            'pct'   => round($r->c / $total * 100, 1),
        ])->all();
    }

    private function topPosts($start, $end, $prevStart, $prevEnd): array
    {
        $now = $this->base($start, $end, null)
            ->select('post_id', DB::raw('COUNT(*) as c'), DB::raw('COUNT(DISTINCT ip_address) as u'))
            ->groupBy('post_id')->orderByDesc('c')->limit(15)->get();

        if ($now->isEmpty()) {
            return [];
        }

        $prev = $this->base($prevStart, $prevEnd, null)
            ->whereIn('post_id', $now->pluck('post_id'))
            ->select('post_id', DB::raw('COUNT(*) as c'))
            ->groupBy('post_id')->pluck('c', 'post_id');

        $topSource = $this->base($start, $end, null)
            ->whereIn('post_id', $now->pluck('post_id'))
            ->select('post_id', 'source', DB::raw('COUNT(*) as c'))
            ->groupBy('post_id', 'source')->orderByDesc('c')->get()
            ->groupBy('post_id')->map(fn ($g) => $g->first()->source);

        $posts = Post::whereIn('id', $now->pluck('post_id'))->get(['id', 'title', 'slug', 'published_at', 'is_published'])->keyBy('id');
        $total = max(1, (int) $now->sum('c'));

        return $now->filter(fn ($r) => $posts->has($r->post_id))->map(fn ($r) => [
            'post'       => $posts[$r->post_id],
            'views'      => (int) $r->c,
            'readers'    => (int) $r->u,
            'share'      => round($r->c / $total * 100, 1),
            'change'     => $this->change((int) $r->c, (int) ($prev[$r->post_id] ?? 0)),
            'top_source' => TrafficClassifier::sourceLabel($topSource[$r->post_id] ?? null),
        ])->values()->all();
    }

    /** Sites que mais mandaram leitores (fora buscadores, redes e o próprio site). */
    private function topReferers($start, $end, ?int $postId): array
    {
        $rows = $this->base($start, $end, $postId)
            ->where('source', 'other')
            ->whereNotNull('referer')->where('referer', '!=', '')
            ->pluck('referer');

        return $rows->map(fn ($r) => preg_replace('~^www\.~', '', (string) parse_url($r, PHP_URL_HOST)))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->all();
    }
}
