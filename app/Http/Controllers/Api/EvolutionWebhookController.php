<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEvolutionWebhook;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EvolutionWebhookController
 * 
 * Recebe eventos da Evolution API (nossa infra) via URL segura por token.
 * URL: POST /api/evo/webhook/{instance_token}
 * 
 * Cada instância tem seu próprio token gerado no provisioning,
 * garantindo isolamento multi-tenant sem expor tenant_id na URL.
 */
class EvolutionWebhookController extends Controller
{
    public function handle(Request $request, string $token)
    {
        // 1. Identificar a instância pelo blind index (token cifrado no DB, bidx indexado)
        $instance = WhatsappInstance::where('instance_token_bidx', hash_hmac('sha256', $token, config('app.key')))->first();

        if (!$instance) {
            Log::warning("Evolution Webhook: token inválido recebido", [
                'token_prefix' => substr($token, 0, 8) . '...',
                'ip'           => $request->ip(),
            ]);
            // Retorna 200 para não causar retry loops na Evolution API
            return response()->json(['status' => 'ignored'], 200);
        }

        // 2. Validação HMAC opcional — ativa quando EVOLUTION_WEBHOOK_SECRET está configurado
        $webhookSecret = config('whatsapp.evolution_webhook_secret');
        if ($webhookSecret) {
            $signature = $request->header('x-webhook-hmac')
                ?: $request->header('x-hub-signature-256')
                ?: $request->header('x-signature');

            if (!$signature) {
                Log::warning("Evolution Webhook: assinatura ausente com secret configurado", [
                    'instance_id' => $instance->id,
                    'ip'          => $request->ip(),
                ]);
                return response()->json(['status' => 'unauthorized'], 401);
            }

            $rawBody  = $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $webhookSecret);

            if (!hash_equals($expected, $signature)) {
                Log::warning("Evolution Webhook: assinatura HMAC inválida", [
                    'instance_id' => $instance->id,
                    'ip'          => $request->ip(),
                ]);
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        $payload = $request->all();
        $event   = $payload['event'] ?? ($payload['type'] ?? 'unknown');

        // 3. Despacha para a fila — obrigatório retornar 200 rápido
        ProcessEvolutionWebhook::dispatch($instance->id, $event, $payload)
            ->onQueue('whatsapp');

        return response()->json(['status' => 'queued'], 200);
    }
}
