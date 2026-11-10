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
 *   1. webhookSecret no header X-Webhook-Secret (preferido) ou query string (legado)
 *   2. Assinatura HMAC no header X-Webhook-Signature
 *
 * Webhook URL: POST /api/abacatepay/webhook
 * Header:      X-Webhook-Secret: SEU_SECRET
 *
 * ISOLAMENTO MULTI-TENANT:
 * - Rota publica nao aciona o filtro global de BelongsToTenant — queries
 *   Model::where(...) aqui veem todos os tenants.
 * - O tenant e resolvido no ProcessAbacatePayWebhook via
 *   findTenantByExternalId() parseando o padrao "tenant_X" do externalId
 *   enviado por nos na criacao da cobranca. tenant_id no payload bruto
 *   nao deve ser tratado como autoridade.
 */
class AbacatePayWebhookController extends Controller
{
    public function handle(Request $request, AbacatePayService $abacate)
    {
        // ── 1. Verificar secret — header tem prioridade sobre query string ────
        $secret = $request->header('X-Webhook-Secret') ?? $request->query('webhookSecret', '');
        if (!$abacate->verifyWebhookSecret($secret)) {
            Log::warning('AbacatePay Webhook: secret inválido', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ── 2. Verificar assinatura HMAC ──────────────────────────────────────
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Webhook-Signature', '');

        if ($signature && !$abacate->hasHmacKey()) {
            // Antes: RuntimeException → 500 → pagamento não liberava. O secret já
            // foi validado acima; registra para configurar a chave HMAC.
            Log::error('AbacatePay Webhook: assinatura recebida mas ABACATEPAY_HMAC_KEY não configurada — aceito pelo secret');
        } elseif ($signature && !$abacate->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('AbacatePay Webhook: assinatura HMAC inválida', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }
        if (!$signature) {
            // Fase de observação: ainda aceita (o secret já foi validado), mas registra.
            // Se este aviso nunca aparecer nos logs, a assinatura pode passar a ser obrigatória.
            Log::warning('AbacatePay Webhook: chegou SEM assinatura X-Webhook-Signature', [
                'ip'       => $request->ip(),
                'via_query' => $request->query('webhookSecret') !== null,
            ]);
        }

        // ── 3. Despachar para fila (responde 200 imediatamente) ───────────────
        $payload = $request->json()->all();
        $event   = $payload['event'] ?? 'unknown';

        Log::info('AbacatePay Webhook recebido', [
            'event'   => $event,
            'devMode' => $payload['devMode'] ?? false,
            'id'      => $payload['id'] ?? null,
        ]);

        // Fila fora do ar não pode perder o pagamento: processa na hora.
        try {
            // Dispatcher direto (não ::dispatch()): o PendingDispatch só envia no
            // __destruct, fora deste try — a falha da fila escaparia do catch.
            app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch(new ProcessAbacatePayWebhook($event, $payload));
            return response()->json(['status' => 'queued'], 200);
        } catch (\Throwable $e) {
            Log::error('AbacatePay Webhook: falha ao enfileirar, processando na hora', ['event' => $event, 'error' => $e->getMessage()]);
        }

        try {
            (new ProcessAbacatePayWebhook($event, $payload))->handle(); // direto, sem fila
            return response()->json(['status' => 'processed'], 200);
        } catch (\Throwable $e) {
            Log::error('AbacatePay Webhook: falha ao processar', ['event' => $event, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao processar'], 500); // AbacatePay tenta de novo
        }
    }
}
