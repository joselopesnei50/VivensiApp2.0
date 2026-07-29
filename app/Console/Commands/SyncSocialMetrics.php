<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Services\MetaSocialInsightsService;
use Illuminate\Console\Command;

class SyncSocialMetrics extends Command
{
    protected $signature   = 'posts:sync-metrics {--days=30 : Só posts publicados nos últimos N dias}';
    protected $description = 'Sincroniza métricas (impressões, alcance, likes, etc) dos posts publicados na Meta.';

    public function handle(MetaSocialInsightsService $insights): int
    {
        $days = max(1, (int) $this->option('days'));

        // Bypass do tenant scope — cron opera cross-tenant. Cada post carrega
        // seu próprio tenant_id que é respeitado na hora de gravar a métrica.
        $posts = ScheduledPost::withoutGlobalScopes()
            ->with('account')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNotNull('facebook_post_id')
                  ->orWhereNotNull('instagram_post_id');
            })
            ->where('scheduled_at', '>=', now()->subDays($days))
            ->orderByDesc('scheduled_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info("Nenhum post publicado nos últimos {$days} dias.");
            return self::SUCCESS;
        }

        $ok = 0; $fail = 0;

        foreach ($posts as $post) {
            $r = $insights->syncPost($post);
            // r = ['facebook' => bool|null, 'instagram' => bool|null]
            foreach ($r as $source => $status) {
                if ($status === true)  $ok++;
                if ($status === false) $fail++;
            }
        }

        $this->info("Metrics sync: {$ok} OK, {$fail} falhas, {$posts->count()} posts processados.");
        return self::SUCCESS;
    }
}
