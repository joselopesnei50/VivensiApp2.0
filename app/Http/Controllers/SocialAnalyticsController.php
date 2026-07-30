<?php

namespace App\Http\Controllers;

use App\Models\PostMetric;
use App\Models\ScheduledPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard agregado de métricas dos posts sociais pro cliente.
 * Isolamento tenant garantido pela trait BelongsToTenant em ambos os
 * models (ScheduledPost e PostMetric).
 */
class SocialAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $from = now()->subDays($days)->startOfDay();
        $to   = now()->endOfDay();

        // Posts publicados no período (com métricas eager loaded)
        $posts = ScheduledPost::with(['account', 'metrics'])
            ->where('status', 'published')
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderByDesc('scheduled_at')
            ->get();

        // Totais agregados
        $totals = [
            'posts'       => $posts->count(),
            'impressions' => 0, 'reach'      => 0,
            'likes'       => 0, 'comments'   => 0,
            'shares'      => 0, 'saved'      => 0,
            'clicks'      => 0, 'engagement' => 0,
        ];
        foreach ($posts as $p) {
            $mt = $p->metricsTotal();
            $totals['impressions'] += $mt['impressions'];
            $totals['reach']       += $mt['reach'];
            $totals['likes']       += $mt['likes'];
            $totals['comments']    += $mt['comments'];
            $totals['shares']      += $mt['shares'];
            $totals['saved']       += $mt['saved'];
            $totals['clicks']      += $mt['clicks'];
            $totals['engagement']  += $mt['engagement'];
        }

        // Top 5 posts por engagement
        $topPosts = $posts
            ->map(function ($p) {
                $mt = $p->metricsTotal();
                return [
                    'post'       => $p,
                    'engagement' => $mt['engagement'],
                    'reach'      => $mt['reach'],
                    'impressions'=> $mt['impressions'],
                    'likes'      => $mt['likes'],
                    'comments'   => $mt['comments'],
                ];
            })
            ->filter(fn ($r) => $r['engagement'] > 0)
            ->sortByDesc('engagement')
            ->take(5)
            ->values();

        // Breakdown por rede (soma métricas de todas linhas do período)
        $byNetwork = PostMetric::whereBetween('created_at', [$from, $to])
            ->selectRaw('source, SUM(impressions) as impressions, SUM(reach) as reach,
                         SUM(likes) as likes, SUM(comments) as comments, SUM(shares) as shares,
                         SUM(saved) as saved, SUM(engagement) as engagement, COUNT(*) as posts')
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        return view('social.analytics', [
            'days'      => $days,
            'from'      => $from,
            'to'        => $to,
            'totals'    => $totals,
            'topPosts'  => $topPosts,
            'byNetwork' => $byNetwork,
        ]);
    }

    /** Passo-a-passo Bruce IA de como criar e agendar posts nas redes. */
    public function guide(): View
    {
        return view('social.como-postar');
    }
}
