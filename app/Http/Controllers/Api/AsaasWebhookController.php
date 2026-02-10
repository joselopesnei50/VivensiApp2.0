<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Security Check
        $webhookToken = config('services.asaas.webhook_token'); // We need to add this to config/services.php
        $incomingToken = $request->header('asaas-access-token');

        if (!$webhookToken || $incomingToken !== $webhookToken) {
            Log::warning('Asaas Webhook: Invalid Token Attempt', [
                'ip' => $request->ip(),
                'incoming_token' => $incomingToken ? '***' : 'null'
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Log Event (for debugging)
        Log::info('Asaas Webhook Received', $request->all());

        // 3. Dispatch Job
        \App\Jobs\HandleAsaasWebhook::dispatch($request->all());

        return response()->json(['status' => 'success']);
    }
}
