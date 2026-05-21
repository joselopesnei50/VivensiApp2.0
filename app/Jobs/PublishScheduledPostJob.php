<?php

namespace App\Jobs;

use App\Models\ScheduledPost;
use App\Services\MetaSocialPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(public readonly int $postId)
    {
        $this->onQueue('social');
    }

    public function handle(MetaSocialPublisherService $publisher): void
    {
        $post = ScheduledPost::withoutGlobalScopes()
            ->with('account')
            ->where('status', 'scheduled')
            ->find($this->postId);

        if (!$post) return;

        $publisher->publish($post);
    }
}
