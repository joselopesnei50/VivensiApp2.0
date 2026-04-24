<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAbacatePayWebhook;
use App\Services\AbacatePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AbacatePayWebhookController
 *
 * Recebe eventos da AbacatePay via POST e despacha para fila com retry automático.
 * Segurança dupla:
 *   1. webhookSecret na query string
 *   2. Assinatura HMAC no header X-Webhook-Signature
 *
 * Webhook URL: POST /api/abacatepay/webhook?webhookSecret=SEU_SECRET
 */
class AbacatePayWebhookController extends Controller
{
    public function handle(Request $request, AbacatePayService $abacate)
    {
        // ── 1. Verificar secret na URL ────────────────────────────────────────
        $secret = $request->query('webhookSecret', '');
        if (!$abacate->verifyWebhookSecret($secret)) {
            Log::warning('AbacatePay Webhook: secret inválido', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ── 2. Verificar assinatura HMAC ──────────────────────────────────────
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Webhook-Signature', '');

        if ($signature && !$abacate->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('AbacatePay Webhook: assinatura HMAC inválida', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // ── 3. Despachar para fila (responde 200 imediatamente) ───────────────
        $payload = $request->json()->all();
        $event   = $payload['event'] ?? 'unknown';

        Log::info('AbacatePay Webhook recebido', [
            'event'   => $event,
            'devMode' => $payload['devMode'] ?? false,
            'id'      => $payload['id'] ?? null,
        ]);

        ProcessAbacatePayWebhook::dispatch($event, $payload);

        return response()->json(['status' => 'queued'], 200);
    }
}
