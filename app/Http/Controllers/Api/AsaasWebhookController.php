<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    /**
     * Handle Webhook from Asaas
     */
    public function handle(Request $request)
    {
        // Security Check: Validate Asaas Token
        $localToken = \App\Models\SystemSetting::getValue('asaas_webhook_token');
        $requestToken = $request->header('asaas-access-token');

        Log::info("DEBUG SECURITY: local='{$localToken}', request='{$requestToken}'");

        // STRICT SECURITY: We must have a token configured and it must match.
        if (empty($localToken)) {
            Log::error('Asaas Webhook: Security Token not configured in SystemSettings.');
            return response()->json(['status' => 'error', 'reason' => 'server_misconfiguration'], 500); 
        }

        if ($localToken !== $requestToken) {
            Log::warning('Asaas Webhook: Invalid Token', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'reason' => 'invalid_token'], 401);
        }

        $event = $request->input('event');
        $payment = $request->input('payment');
        $subscriptionId = $request->input('subscription') ?? ($payment['subscription'] ?? null);

        Log::info('Asaas Webhook Received:', ['event' => $event, 'subscription' => $subscriptionId]);

        if (!$subscriptionId) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_subscription']);
        }

        // Find tenant by customer Asaas ID or External Reference (we saved it as customer)
        $tenant = Tenant::where('asaas_customer_id', $payment['customer'] ?? null)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'reason' => 'tenant_not_found'], 404);
        }

        switch ($event) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $tenant->subscription_status = 'active';
                $tenant->save();
                
                // Enviar e-mail de confirmação para o gestor
                $manager = $tenant->users()->where('role', 'manager')->first();
                if ($manager) {
                    $brevo = app(\App\Services\BrevoService::class);
                    $brevo->sendPaymentConfirmedEmail($manager);
                }

                Log::info("Tenant {$tenant->id} activated and email sent.");
                break;


            case 'PAYMENT_OVERDUE':
                $tenant->subscription_status = 'past_due';
                $tenant->save();
                break;

            case 'SUBSCRIPTION_DELETED':
                $tenant->subscription_status = 'canceled';
                $tenant->save();
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
