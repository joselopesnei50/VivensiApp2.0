<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\AbacatePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AbacatePayWebhookController
 *
 * Recebe eventos da AbacatePay via POST.
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

        // ── 3. Processar evento ───────────────────────────────────────────────
        $payload = $request->json()->all();
        $event   = $payload['event'] ?? 'unknown';

        Log::info('AbacatePay Webhook recebido', [
            'event'   => $event,
            'devMode' => $payload['devMode'] ?? false,
            'id'      => $payload['id'] ?? null,
        ]);

        match ($event) {
            'checkout.completed'      => $this->handleCheckoutCompleted($payload),
            'checkout.refunded'       => $this->handleCheckoutRefunded($payload),
            'subscription.completed'  => $this->handleSubscriptionCompleted($payload),
            'subscription.renewed'    => $this->handleSubscriptionRenewed($payload),
            'subscription.cancelled'  => $this->handleSubscriptionCancelled($payload),
            default                   => Log::info("AbacatePay: evento '{$event}' ignorado."),
        };

        return response()->json(['status' => 'ok'], 200);
    }

    // ─── Handlers de Evento ───────────────────────────────────────────────────

    private function handleCheckoutCompleted(array $payload): void
    {
        $checkout   = $payload['data']['checkout'] ?? null;
        $externalId = $checkout['externalId'] ?? null;

        if (!$checkout || !$externalId) {
            Log::warning('AbacatePay: checkout.completed sem externalId');
            return;
        }

        // Formato do externalId: VIVENSI_{tenant_id}_{timestamp}
        $tenant = $this->findTenantByExternalId($externalId);

        if (!$tenant) {
            Log::error('AbacatePay: tenant não encontrado', ['externalId' => $externalId]);
            return;
        }

        // Atualizar transação como paga
        Transaction::withoutGlobalScopes()
            ->where('external_id', $externalId)
            ->update([
                'status'          => 'paid',
                'approval_status' => 'approved',
                'paid_at'         => now(),
            ]);

        // Ativar/renovar assinatura do tenant
        $tenant->subscription_status = 'active';
        $tenant->save();

        Log::info('AbacatePay: checkout.completed processado', [
            'tenant'     => $tenant->id,
            'externalId' => $externalId,
            'amount'     => ($checkout['paidAmount'] ?? 0) / 100,
        ]);
    }

    private function handleCheckoutRefunded(array $payload): void
    {
        $externalId = $payload['data']['checkout']['externalId'] ?? null;
        if (!$externalId) return;

        Transaction::withoutGlobalScopes()
            ->where('external_id', $externalId)
            ->update(['status' => 'refunded']);

        Log::info('AbacatePay: checkout.refunded', ['externalId' => $externalId]);
    }

    private function handleSubscriptionCompleted(array $payload): void
    {
        // Assinatura nova ativa
        $customer = $payload['data']['customer'] ?? null;
        $sub      = $payload['data']['subscription'] ?? null;

        if (!$customer || !$sub) return;

        // Encontrar tenant pelo e-mail do customer
        $tenant = Tenant::whereHas('owner', fn($q) => $q->where('email', $customer['email']))->first();

        if ($tenant) {
            $tenant->subscription_status = 'active';
            $tenant->save();
            Log::info('AbacatePay: subscription.completed', ['tenant' => $tenant->id, 'subId' => $sub['id']]);
        }
    }

    private function handleSubscriptionRenewed(array $payload): void
    {
        // Renovação automática — mesma lógica do completed
        $this->handleSubscriptionCompleted($payload);
        Log::info('AbacatePay: subscription.renewed processado');
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $customer = $payload['data']['customer'] ?? null;
        if (!$customer) return;

        $tenant = Tenant::whereHas('owner', fn($q) => $q->where('email', $customer['email']))->first();

        if ($tenant) {
            $tenant->subscription_status = 'canceled';
            $tenant->save();
            Log::info('AbacatePay: subscription.cancelled', ['tenant' => $tenant->id]);
        }
    }

    // ─── Utilidades ───────────────────────────────────────────────────────────

    private function findTenantByExternalId(string $externalId): ?Tenant
    {
        // Formato: VIVENSI_{tenant_id}_{timestamp}
        if (preg_match('/^VIVENSI_(\d+)_/', $externalId, $matches)) {
            return Tenant::find((int) $matches[1]);
        }
        return null;
    }
}
