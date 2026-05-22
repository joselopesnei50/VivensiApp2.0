<?php

namespace App\Services;

use App\Jobs\DispatchWebhook;
use App\Models\Webhook;

class WebhookService
{
    public const EVENTS = [
        'transaction.created',
        'transaction.approved',
        'transaction.rejected',
        'project.created',
        'project.status_changed',
        'task.created',
        'task.completed',
        'task.overdue',
    ];

    public function fire(int $tenantId, string $event, array $payload): void
    {
        Webhook::where('tenant_id', $tenantId)
            ->active()
            ->get()
            ->each(function (Webhook $webhook) use ($event, $payload) {
                if ($webhook->subscribesTo($event)) {
                    DispatchWebhook::dispatch(
                        $webhook->id,
                        $event,
                        array_merge($payload, [
                            'event'      => $event,
                            'fired_at'   => now()->toIso8601String(),
                            'tenant_id'  => $webhook->tenant_id,
                        ])
                    );
                }
            });
    }
}
