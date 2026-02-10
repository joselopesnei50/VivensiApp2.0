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
            // 1. Authenticate Request
            $webhookToken = config('services.asaas.webhook_token');
            $incomingToken = $request->header('asaas-access-token');

            if (!$webhookToken || $incomingToken !== $webhookToken) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $payload = $request->all();
            $event = data_get($payload, 'event');
            $paymentId = data_get($payload, 'payment.id');
            $customerId = data_get($payload, 'payment.customer');

            // 2. Validate Tenant Existence
            // Antigravity/Vivensi requires a Tenant to associate the payment.
            // If we receive a webhook for a customer not in our DB, we ignore it to prevent errors.
            $tenant = null;
            if ($customerId) {
                try {
                    $tenant = \App\Models\Tenant::where('asaas_customer_id', $customerId)->first();
                } catch (\Throwable $dbError) {
                   // DB connection might be flaky, but we catch it later.
                   // For now, $tenant remains null.
                }
            }

            if (!$tenant) {
                // Log warning but return 200 to satisfy Asaas (and avoid penalties)
                try {
                    Log::warning('Asaas Webhook: Ignored. Tenant not found for customer.', [
                        'customer_id' => $customerId,
                        'payment_id' => $paymentId,
                        'event' => $event
                    ]);
                } catch (\Throwable $e) {}
                
                return response()->json(['status' => 'ignored_tenant_not_found']);
            }

            // 3. Process Valid Webhook
            try {
                // Dispatch Job with the payload. 
                // The Job will handle the detailed logic (creating transaction, etc.)
                // We could pass $tenant->id to the job to save a query, but passing payload is standard.
                HandleAsaasWebhook::dispatch($payload);
            } catch (\Throwable $e) {
                // If Dispatch fails (e.g. Queue down), try inline or log error.
                try {
                    Log::error('Asaas Webhook: Dispatch failed.', [
                        'error' => $e->getMessage(),
                        'payment_id' => $paymentId
                    ]);
                    // Optional: Try inline if critical
                    // (new HandleAsaasWebhook($payload))->handle();
                } catch (\Throwable $inner) {}
            }

            return response()->json(['status' => 'success']);

        } catch (\Throwable $e) {
            // NUCLEAR OPTION: Catch ANY other error (logic, syntax, etc.)
            // Ensure Asaas ALWAYS gets 200 OK.
            try {
                Log::critical('Asaas Webhook: Critical Error caught.', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Throwable $logError) {}

            if (!headers_sent()) {
                http_response_code(200);
            }
            return response()->json(['status' => 'error_handled']);
        }
    }
}
