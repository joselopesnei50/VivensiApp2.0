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
        // Usa a queue 'default' que já tem worker no supervisor
        // (vivensi-worker-default). Se um dia o volume justificar worker
        // dedicado, cria-se supervisor 'vivensi-worker-social' e volta pra
        // ->onQueue('social').
        $this->onQueue('default');
    }

    public function handle(MetaSocialPublisherService $publisher): void
    {
        // ── Bypass intencional do BelongsToTenant global scope ─────────────
        // Job é despachado pelo command posts:publish (cron) que opera
        // cross-tenant. O lookup eh por PK + status, e o tenant_id implícito
        // em $post->tenant_id eh respeitado pelo MetaSocialPublisherService
        // (que usa as credenciais Meta do tenant dono do post).
        // Filtro tenant_id no construtor seria defesa em profundidade — listado
        // como follow-up em AUDIT_JOBS_TENANT_FILTER.md (item 1.3.A).
        $post = ScheduledPost::withoutGlobalScopes()
            ->with('account')
            ->where('status', 'scheduled')
            ->find($this->postId);

        if (!$post) return;

        $publisher->publish($post);
    }
}
