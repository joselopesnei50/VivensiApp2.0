<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandlePagSeguroWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagSeguroWebhookController extends Controller
{
    /**
     * Handle the incoming webhook from PagSeguro.
     * Use POST. PagSeguro sends 'notificationCode' and 'notificationType'.
     */
    public function handle(Request $request)
    {
        // 1. Basic Validation
        // PagSeguro sends form-data, not JSON usually.
        $notificationCode = $request->input('notificationCode');
        $notificationType = $request->input('notificationType');

        if (!$notificationCode || !$notificationType) {
            // It might be a verify call from PagSeguro or invalid request
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // 2. Log Receive (Lightweight)
        Log::info('PagSeguro Notification Received', ['code' => $notificationCode, 'type' => $notificationType]);

        // 3. Dispatch Job
        // We do NOT check the status here to keep response fast (per PagSeguro recommendation).
        // We dispatch a job to query the API and update DB.
        try {
            HandlePagSeguroWebhook::dispatch($notificationCode, $notificationType);
        } catch (\Throwable $e) {
            Log::critical('Failed to dispatch PagSeguro job', ['error' => $e->getMessage()]);
            // Still return 200 to PagSeguro, otherwise they keep retrying
        }

        return response()->json(['status' => 'ok']);
    }
}
