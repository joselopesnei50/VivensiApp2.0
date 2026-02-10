<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleAsaasWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Security Check
        $webhookToken = config('services.asaas.webhook_token');
        $incomingToken = $request->header('asaas-access-token');

        if (!$webhookToken || $incomingToken !== $webhookToken) {
            Log::warning('Asaas Webhook: Invalid Token Attempt', [
                'ip' => $request->ip(),
                'incoming_token' => $incomingToken ? '***' : 'null'
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // 2. Log Event (keep logs small / safe)
        Log::info('Asaas Webhook Received', [
            'event' => $payload['event'] ?? null,
            'id' => $payload['id'] ?? null,
            'payment_id' => $payload['payment']['id'] ?? null,
            'ip' => $request->ip(),
        ]);

        // 3. Dispatch Job (but never break webhook with 500 if queue is misconfigured)
        try {
            HandleAsaasWebhook::dispatch($payload);
        } catch (\Throwable $e) {
            // Common production cause: QUEUE_CONNECTION=database without `jobs` table migrated.
            Log::error('Asaas Webhook: Failed to enqueue job; processing inline as fallback.', [
                'event' => data_get($payload, 'event'),
                'id' => data_get($payload, 'id'),
                'payment_id' => data_get($payload, 'payment.id'),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            try {
                (new HandleAsaasWebhook($payload))->handle();
            } catch (\Throwable $e2) {
                Log::critical('Asaas Webhook: Inline processing failed.', [
                    'event' => data_get($payload, 'event'),
                    'id' => data_get($payload, 'id'),
                    'payment_id' => data_get($payload, 'payment.id'),
                    'exception' => get_class($e2),
                    'message' => $e2->getMessage(),
                ]);
            }
        }

        // Always acknowledge Asaas quickly to avoid retries/penalties.
        return response()->json(['status' => 'ok']);
    }
}
