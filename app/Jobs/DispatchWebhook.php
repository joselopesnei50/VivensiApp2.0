<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 15;

    public function __construct(
        private int    $webhookId,
        private string $event,
        private array  $payload
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);
        if (!$webhook || !$webhook->active) return;

        $body      = json_encode($this->payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type'         => 'application/json',
                    'X-Vivensi-Event'      => $this->event,
                    'X-Vivensi-Signature'  => $signature,
                    'X-Vivensi-Delivery'   => (string) $this->job?->getJobId(),
                    'User-Agent'           => 'Vivensi-Webhook/2.0',
                ])
                ->post($webhook->url, $this->payload);

            $success = $response->successful();
            $status  = $response->status();
            $body    = substr($response->body(), 0, 1000);

        } catch (\Throwable $e) {
            $success = false;
            $status  = null;
            $body    = $e->getMessage();
            Log::warning("Webhook {$this->webhookId} failed: " . $e->getMessage());
        }

        WebhookLog::create([
            'webhook_id'    => $this->webhookId,
            'event'         => $this->event,
            'payload'       => $this->payload,
            'http_status'   => $status,
            'response_body' => $body,
            'attempts'      => $this->attempts(),
            'success'       => $success,
            'fired_at'      => now(),
        ]);

        $webhook->update(['last_triggered_at' => now()]);

        if (!$success && $this->attempts() < $this->tries) {
            $this->release(60 * $this->attempts());
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
