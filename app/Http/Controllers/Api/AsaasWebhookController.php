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
        try {
            // 1. Security Check
            $webhookToken = config('services.asaas.webhook_token');
            $incomingToken = $request->header('asaas-access-token');

            if (!$webhookToken || $incomingToken !== $webhookToken) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $payload = $request->all();

            // 2. Log Event (safe wrap)
            try {
                Log::info('Asaas Webhook Received', [
                    'event' => data_get($payload, 'event'),
                    'id' => data_get($payload, 'id'),
                    'ip' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                // Logging failed
            }

            // 3. Dispatch Job
            try {
                HandleAsaasWebhook::dispatch($payload);
            } catch (\Throwable $e) {
                 try {
                    (new HandleAsaasWebhook($payload))->handle();
                } catch (\Throwable $e2) {
                    // Inline failed
                }
            }

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            try {
                Log::critical('Asaas Webhook Critical Failure', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Throwable $logError) {
                // Give up
            }
            
            return response()->json(['status' => 'ok']);
        }
    }
}
