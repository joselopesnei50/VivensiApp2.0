<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleAsaasWebhook;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
