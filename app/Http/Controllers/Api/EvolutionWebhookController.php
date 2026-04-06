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
        // 1. Identificar a instância pelo token secreto da URL
        $instance = WhatsappInstance::where('instance_token', $token)->first();

        if (!$instance) {
            // Loga mas retorna 200 para não causar retry loops na Evolution API
            Log::warning("Evolution Webhook: token inválido recebido", [
                'token_prefix' => substr($token, 0, 8) . '...',
                'ip'           => $request->ip(),
            ]);
            return response()->json(['status' => 'ignored'], 200);
        }

        $payload = $request->all();
        $event   = $payload['event'] ?? ($payload['type'] ?? 'unknown');

        // 2. Despacha para a fila — obrigatório retornar 200 rápido
        ProcessEvolutionWebhook::dispatch($instance->id, $event, $payload)
            ->onQueue('whatsapp');

        return response()->json(['status' => 'queued'], 200);
    }
}
