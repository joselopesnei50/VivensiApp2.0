<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleAsaasWebhook;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AsaasWebhookController
 *
 * Rota: POST /api/webhooks/asaas (publica, sem Auth).
 * Auth: token compartilhado em header asaas-access-token (hash_equals).
 *
 * ISOLAMENTO MULTI-TENANT:
 * - Rota publica nao aciona o filtro global de BelongsToTenant — queries
 *   Model::where(...) aqui veem todos os tenants.
 * - O tenant e resolvido no HandleAsaasWebhook via
 *   Tenant::where('asaas_customer_id', $customerId), unico por conta
 *   ASAAS. tenant_id do payload nao deve ser usado como autoridade.
 */
class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $expectedToken = SystemSetting::getValue('asaas_webhook_token') ?? config('services.asaas.webhook_token');
        $providedToken = $request->header('asaas-access-token');

        if (!$expectedToken || !$providedToken || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        try {
            HandleAsaasWebhook::dispatch($payload);
        } catch (\Throwable $e) {
            Log::critical('Failed to dispatch Asaas webhook job', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }
}
