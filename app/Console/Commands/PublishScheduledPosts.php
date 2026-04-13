<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledPostJob;
use App\Models\ScheduledPost;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature   = 'posts:publish';
    protected $description = 'Dispara jobs para publicar todos os posts agendados vencidos.';

    public function handle(): void
    {
        $posts = ScheduledPost::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Nenhum post para publicar agora.');
            return;
        }

        foreach ($posts as $post) {
            PublishScheduledPostJob::dispatch($post->id);
        }

        $this->info("Disparados {$posts->count()} jobs de publicação.");
    }
}
