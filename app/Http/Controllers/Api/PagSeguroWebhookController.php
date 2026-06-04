<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandlePagSeguroWebhook;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PagSeguroWebhookController
 *
 * Rota: POST /api/webhooks/pagseguro (publica, sem Auth).
 * Auth: token compartilhado em header x-pagseguro-token (hash_equals).
 *
 * ISOLAMENTO MULTI-TENANT:
 * - Esta rota nao tem usuario autenticado, entao a trait BelongsToTenant
 *   NAO aplica filtro global (vide BelongsToTenant.php:30-37). Qualquer
 *   query Model::where(...) aqui retornaria linhas de TODOS os tenants.
 * - O tenant e descoberto via $transaction->tenant_id no HandlePagSeguroWebhook
 *   apos buscar a Transaction pelo external_id retornado pela gateway
 *   (unico por transacao). Nunca confiar em tenant_id vindo do payload.
 */
class PagSeguroWebhookController extends Controller
{
    /**
     * Handle the incoming webhook from PagSeguro.
     * Use POST. PagSeguro sends 'notificationCode' and 'notificationType'.
     */
    public function handle(Request $request)
    {
        // 0. Token authentication
        $expectedToken = SystemSetting::getValue('pagseguro_webhook_token') ?? config('services.pagseguro.webhook_token');
        $providedToken = $request->header('x-pagseguro-token') ?? $request->query('token');

        if (!$expectedToken || !$providedToken || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            Log::warning('PagSeguro Webhook: token inválido', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

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
