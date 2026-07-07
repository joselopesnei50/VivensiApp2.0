<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCloudApiWebhook;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Meta WhatsApp Business Cloud API — endpoint instance-level.
 *
 * URLs:
 *   GET  /api/whatsapp/cloud-webhook  — handshake (hub.mode=subscribe + hub.verify_token)
 *   POST /api/whatsapp/cloud-webhook  — eventos (messages, statuses)
 *
 * Config (via SystemSetting, editável no painel admin):
 *   - meta_cloud_verify_token  (segredo compartilhado com a Meta pro GET)
 *   - meta_cloud_app_secret    (App Secret do app NC5HUBDIGITAL-EMP, valida HMAC do POST)
 *
 * Nota: `WhatsappController@webhook` existente permanece funcional para clientes
 * legados que ainda usam `WhatsappConfig` (tenant-level). Este novo endpoint é a
 * arquitetura instance-level onde cada WhatsappInstance tem seu próprio phone_number_id.
 */
class CloudApiWebhookController extends Controller
{
    /**
     * GET — handshake de verificação da Meta.
     * Echoes hub.challenge quando hub.verify_token corresponde ao segredo cadastrado.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge', '');

        $expected = SystemSetting::getValue('meta_cloud_verify_token', '');

        if ($mode !== 'subscribe' || $token === null || empty($expected) || !hash_equals($expected, (string) $token)) {
            Log::warning('CloudApi Webhook: handshake rejeitado', [
                'mode'   => $mode,
                'ip'     => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid verify token'], 403);
        }

        return response((string) $challenge, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * POST — recebe eventos (mensagens, statuses). Valida HMAC-SHA256 no raw body
     * ANTES de decodificar JSON. Se ok, despacha ProcessCloudApiWebhook e responde 200.
     */
    public function handle(Request $request)
    {
        $appSecret = SystemSetting::getValue('meta_cloud_app_secret', '');
        if (empty($appSecret)) {
            Log::error('CloudApi Webhook: meta_cloud_app_secret não configurado', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        if (empty($signature)) {
            Log::warning('CloudApi Webhook: X-Hub-Signature-256 ausente', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $rawBody  = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('CloudApi Webhook: assinatura HMAC inválida', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        // Meta espera 200 dentro de 20s; delegamos processamento pra fila.
        ProcessCloudApiWebhook::dispatch($payload)->onQueue('whatsapp');

        return response()->json(['status' => 'queued'], 200);
    }
}
