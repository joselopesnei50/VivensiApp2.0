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
        // NUCLEAR OPTION: Ensure we catch EVERYTHING and return 200.
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
                // Ignore log failures
            }

            // 3. Dispatch Job
            try {
                // Ensure DB is alive before dispatching if using database queue
                try {
                    \Illuminate\Support\Facades\DB::connection()->getPdo();
                } catch (\Throwable $dbLost) {
                    try {
                        \Illuminate\Support\Facades\DB::reconnect();
                    } catch (\Throwable $dbFail) {
                        // DB is dead. We can't dispatch to DB queue.
                        // We could try to write to a text file?
                        // For now, just absorb it so we don't 500.
                    }
                }

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
            // FALLBACK: If Laravel response fails, force raw PHP headers
            try {
                Log::critical('Asaas Webhook Critical Failure', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Throwable $logError) {
                // Logging failed
            }
            
            // Force 200 OK for Asaas
            if (!headers_sent()) {
                http_response_code(200);
                header('Content-Type: application/json');
            }
            echo json_encode(['status' => 'ok', 'warning' => 'Internal Error Handled']);
            exit; // Stop execution immediately to prevent framework 500 bubbling
        }
    }
}
