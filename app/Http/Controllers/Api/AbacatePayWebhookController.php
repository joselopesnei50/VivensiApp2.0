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
 * Segurança dupla (as duas obrigatórias):
 *   1. webhookSecret no header X-Webhook-Secret (preferido) ou query string (legado)
 *   2. Assinatura HMAC-SHA256 (base64) do corpo bruto no header X-Webhook-Signature,
 *      com a chave pública da AbacatePay (config services.abacatepay.hmac_key)
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

        // ── 2. Assinatura HMAC obrigatória ────────────────────────────────────
        // O secret mostra que veio da AbacatePay; a assinatura mostra que o corpo
        // não foi alterado. Sem as duas, recusa com 401 (a AbacatePay não reenvia
        // 401). Pagamento perdido aqui ainda é confirmado pelo abacatepay:reconcile
        // (a cada 5 min) e pela consulta da tela de checkout/faturas.
        $rawBody   = $request->getContent();
        $signature = (string) $request->header('X-Webhook-Signature', '');

        // O secret já bateu: se a assinatura falhar, é sinal de configuração errada
        // (ou corpo adulterado) — vai para o log e para o Sentry, que avisa por e-mail.
        if ($signature === '') {
            Log::error('AbacatePay Webhook: recusado SEM assinatura X-Webhook-Signature', ['ip' => $request->ip()]);
            report(new \RuntimeException('AbacatePay Webhook recusado: sem assinatura X-Webhook-Signature'));
            return response()->json(['message' => 'Missing signature'], 401);
        }
        if (!$abacate->verifyWebhookSignature($rawBody, $signature)) {
            Log::error('AbacatePay Webhook: recusado com assinatura HMAC inválida', ['ip' => $request->ip()]);
            report(new \RuntimeException('AbacatePay Webhook recusado: assinatura HMAC inválida'));
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
