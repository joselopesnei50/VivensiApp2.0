<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAbacatePayWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 5;
    public int   $timeout = 30;
    public array $backoff  = [30, 60, 120, 300];

    public function __construct(
        protected string $event,
        protected array  $payload
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        match ($this->event) {
            'checkout.completed'     => $this->handleCheckoutCompleted($this->payload),
            'checkout.refunded'      => $this->handleCheckoutRefunded($this->payload),
            'subscription.completed' => $this->handleSubscriptionCompleted($this->payload),
            'subscription.renewed'   => $this->handleSubscriptionCompleted($this->payload),
            'subscription.cancelled' => $this->handleSubscriptionCancelled($this->payload),
            default                  => Log::info("AbacatePay: evento '{$this->event}' ignorado."),
        };
    }

    private function handleCheckoutCompleted(array $payload): void
    {
        $checkout   = $payload['data']['checkout'] ?? null;
        $externalId = $checkout['externalId'] ?? null;
        $webhookId  = $payload['id'] ?? null;

        if (!$checkout || !$externalId) {
            Log::warning('AbacatePay: checkout.completed sem externalId');
            return;
        }

        // Idempotência: registrar o webhook_id; se já processado, sair silenciosamente
        if ($webhookId) {
            try {
                $inserted = DB::table('processed_webhooks')->insertOrIgnore([
                    'gateway'      => 'abacatepay',
                    'webhook_id'   => $webhookId,
                    'event'        => 'checkout.completed',
                    'processed_at' => now(),
                ]);
                if (!$inserted) {
                    Log::info('AbacatePay: webhook duplicado ignorado', ['webhookId' => $webhookId]);
                    return;
                }
            } catch (\Throwable $e) {
                // Violação de unicidade = outro worker já processou
                Log::info('AbacatePay: webhook já processado por outro worker', ['webhookId' => $webhookId]);
                return;
            }
        }

        $tenant = $this->findTenantByExternalId($externalId);
        if (!$tenant) {
            Log::error('AbacatePay: tenant não encontrado', ['externalId' => $externalId]);
            return;
        }

        $planId = $checkout['metadata']['plan_id'] ?? null;

        DB::transaction(function () use ($externalId, $tenant, $planId, $checkout) {
            // ── Bypass intencional do BelongsToTenant global scope ─────────────
            // Webhook do AbacatePay chega sem auth Laravel; $tenant ja foi resolvido
            // acima via findTenantByExternalId(). external_id eh unico globalmente
            // (gerado pelo gateway), entao lookup por ele eh seguro mesmo cross-tenant.
            // lockForUpdate previne race com workers simultaneos.
            $transaction = Transaction::withoutGlobalScopes()
                ->where('external_id', $externalId)
                ->lockForUpdate()
                ->first();

            if ($transaction && $transaction->status !== 'paid') {
                $oldStatus = $transaction->status;
                $transaction->update([
                    'status'          => 'paid',
                    'approval_status' => 'approved',
                    'paid_at'         => now(),
                ]);
                AuditLog::create([
                    'tenant_id'      => $tenant->id,
                    'user_id'        => null,
                    'event'          => 'payment.status_changed',
                    'auditable_type' => Transaction::class,
                    'auditable_id'   => $transaction->id,
                    'old_values'     => ['status' => $oldStatus],
                    'new_values'     => ['status' => 'paid', 'gateway' => 'abacatepay', 'external_id' => $externalId],
                ]);
            }

            $oldSubStatus = $tenant->subscription_status;
            $tenant->subscription_status = 'active';
            if ($planId && SubscriptionPlan::find($planId)) {
                $tenant->plan_id = $planId;
            }
            $tenant->save();
            Cache::forget("tenant.{$tenant->id}");

            AuditLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => null,
                'event'          => 'subscription.activated',
                'auditable_type' => Tenant::class,
                'auditable_id'   => $tenant->id,
                'old_values'     => ['subscription_status' => $oldSubStatus],
                'new_values'     => ['subscription_status' => 'active', 'gateway' => 'abacatepay', 'plan_id' => $planId],
            ]);
        });

        Log::info('AbacatePay: checkout.completed processado', [
            'tenant'     => $tenant->id,
            'plan_id'    => $planId,
            'externalId' => $externalId,
        ]);
    }

    private function handleCheckoutRefunded(array $payload): void
    {
        $externalId = $payload['data']['checkout']['externalId'] ?? null;
        if (!$externalId) return;

        try {
            // Bypass intencional: external_id eh unico globalmente (gerado pelo
            // gateway). Webhook nao tem auth Laravel mas o ID nao colide entre tenants.
            Transaction::withoutGlobalScopes()
                ->where('external_id', $externalId)
                ->update(['status' => 'refunded']);
        } catch (\Throwable $e) {
            Log::warning('AbacatePay: falha ao marcar refund', ['error' => $e->getMessage()]);
        }

        $tenant = $this->findTenantByExternalId($externalId);
        if ($tenant) {
            $oldSubStatus = $tenant->subscription_status;
            $tenant->subscription_status = 'canceled';
            $tenant->save();
            AuditLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => null,
                'event'          => 'subscription.cancelled',
                'auditable_type' => Tenant::class,
                'auditable_id'   => $tenant->id,
                'old_values'     => ['subscription_status' => $oldSubStatus],
                'new_values'     => ['subscription_status' => 'canceled', 'gateway' => 'abacatepay', 'reason' => 'refund'],
            ]);
        }

        Log::info('AbacatePay: checkout.refunded', ['externalId' => $externalId]);
    }

    private function handleSubscriptionCompleted(array $payload): void
    {
        $webhookId = $payload['id'] ?? null;
        if (!$this->markProcessed($webhookId, 'subscription.completed')) {
            return;
        }

        $resolved = $this->resolveTenantFromSubscriptionPayload($payload);
        if (!$resolved) {
            Log::error('AbacatePay: subscription.completed sem tenant resolvido', [
                'payload_keys' => array_keys($payload['data'] ?? []),
            ]);
            return;
        }

        /** @var Tenant $tenant */
        $tenant   = $resolved['tenant'];
        $source   = $resolved['source'];
        $sub      = $payload['data']['subscription'] ?? [];
        $planId   = $sub['metadata']['plan_id'] ?? ($payload['data']['customer']['metadata']['plan_id'] ?? null);
        $extId    = $sub['externalId'] ?? null;
        $subId    = $sub['id'] ?? null;

        DB::transaction(function () use ($tenant, $sub, $planId, $extId, $subId, $source) {
            // Marca Transaction pendente como paid se conseguirmos linkar pelo external_id
            if ($extId) {
                $tx = Transaction::withoutGlobalScopes()
                    ->where('external_id', $extId)
                    ->lockForUpdate()
                    ->first();
                if ($tx && $tx->status !== 'paid') {
                    $tx->update([
                        'status'          => 'paid',
                        'approval_status' => 'approved',
                        'paid_at'         => now(),
                    ]);
                }
            }

            $oldSubStatus = $tenant->subscription_status;
            $tenant->subscription_status = 'active';
            if ($planId && SubscriptionPlan::find($planId)) {
                $tenant->plan_id = $planId;
            }
            $tenant->save();
            Cache::forget("tenant.{$tenant->id}");

            AuditLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => null,
                'event'          => 'subscription.activated',
                'auditable_type' => Tenant::class,
                'auditable_id'   => $tenant->id,
                'old_values'     => ['subscription_status' => $oldSubStatus],
                'new_values'     => [
                    'subscription_status' => 'active',
                    'gateway'             => 'abacatepay',
                    'plan_id'             => $planId,
                    'subscription_id'     => $subId,
                    'lookup_source'       => $source,
                ],
            ]);
        });

        Log::info('AbacatePay: subscription ativada', [
            'tenant'        => $tenant->id,
            'subId'         => $subId,
            'lookup_source' => $source,
        ]);
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $webhookId = $payload['id'] ?? null;
        if (!$this->markProcessed($webhookId, 'subscription.cancelled')) {
            return;
        }

        $resolved = $this->resolveTenantFromSubscriptionPayload($payload);
        if (!$resolved) {
            Log::error('AbacatePay: subscription.cancelled sem tenant resolvido', [
                'payload_keys' => array_keys($payload['data'] ?? []),
            ]);
            return;
        }

        /** @var Tenant $tenant */
        $tenant = $resolved['tenant'];
        $source = $resolved['source'];
        $subId  = $payload['data']['subscription']['id'] ?? null;

        DB::transaction(function () use ($tenant, $subId, $source) {
            $oldSubStatus = $tenant->subscription_status;
            $tenant->subscription_status = 'canceled';
            $tenant->save();
            Cache::forget("tenant.{$tenant->id}");

            AuditLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => null,
                'event'          => 'subscription.cancelled',
                'auditable_type' => Tenant::class,
                'auditable_id'   => $tenant->id,
                'old_values'     => ['subscription_status' => $oldSubStatus],
                'new_values'     => [
                    'subscription_status' => 'canceled',
                    'gateway'             => 'abacatepay',
                    'reason'              => 'subscription.cancelled',
                    'subscription_id'     => $subId,
                    'lookup_source'       => $source,
                ],
            ]);
        });

        Log::info('AbacatePay: subscription cancelada', [
            'tenant'        => $tenant->id,
            'subId'         => $subId,
            'lookup_source' => $source,
        ]);
    }

    /**
     * Resolve o tenant a partir do payload de subscription, tentando em cascata
     * (do mais robusto pro mais frágil):
     *  1. subscription.externalId (gerado pelo Vivensi no padrão VIVENSI_{id}_*)
     *  2. subscription.metadata.tenant_id (enviado pelo Vivensi na criação)
     *  3. customer.metadata.tenant_id
     *  4. customer.email → owner do tenant (fallback frágil, marcado no log)
     *
     * @return array{tenant:Tenant, source:string}|null
     */
    private function resolveTenantFromSubscriptionPayload(array $payload): ?array
    {
        $sub      = $payload['data']['subscription'] ?? [];
        $customer = $payload['data']['customer']     ?? [];

        // 1) external_id no formato VIVENSI_{id}_*
        $extId = $sub['externalId'] ?? null;
        if ($extId) {
            $tenant = $this->findTenantByExternalId($extId);
            if ($tenant) {
                return ['tenant' => $tenant, 'source' => 'subscription.externalId'];
            }
        }

        // 2) subscription.metadata.tenant_id
        $tenantId = $sub['metadata']['tenant_id'] ?? null;
        if ($tenantId && ($tenant = Tenant::find((int) $tenantId))) {
            return ['tenant' => $tenant, 'source' => 'subscription.metadata.tenant_id'];
        }

        // 3) customer.metadata.tenant_id
        $tenantId = $customer['metadata']['tenant_id'] ?? null;
        if ($tenantId && ($tenant = Tenant::find((int) $tenantId))) {
            return ['tenant' => $tenant, 'source' => 'customer.metadata.tenant_id'];
        }

        // 4) Email fallback — pode falhar se o email do owner mudou
        $email = $customer['email'] ?? null;
        if ($email) {
            $tenant = Tenant::whereHas('owner', fn ($q) => $q->where('email', $email))->first();
            if ($tenant) {
                Log::warning('AbacatePay: tenant resolvido por fallback de email — considere garantir externalId ou metadata nas subscriptions futuras', [
                    'tenant_id' => $tenant->id,
                    'email'     => $email,
                ]);
                return ['tenant' => $tenant, 'source' => 'email_fallback'];
            }
        }

        return null;
    }

    /**
     * Registra processamento idempotente do webhook. Retorna true se este worker
     * deve seguir processando, false se outro já processou (duplicata).
     */
    private function markProcessed(?string $webhookId, string $event): bool
    {
        if (!$webhookId) {
            return true; // sem id => não dá pra deduplicar, segue
        }
        try {
            $inserted = DB::table('processed_webhooks')->insertOrIgnore([
                'gateway'      => 'abacatepay',
                'webhook_id'   => $webhookId,
                'event'        => $event,
                'processed_at' => now(),
            ]);
            if (!$inserted) {
                Log::info('AbacatePay: webhook duplicado ignorado', [
                    'webhookId' => $webhookId,
                    'event'     => $event,
                ]);
                return false;
            }
        } catch (\Throwable $e) {
            // Race: outro worker venceu
            Log::info('AbacatePay: webhook já processado por outro worker', [
                'webhookId' => $webhookId,
                'event'     => $event,
            ]);
            return false;
        }
        return true;
    }

    private function findTenantByExternalId(string $externalId): ?Tenant
    {
        if (preg_match('/^VIVENSI_(\d+)_/', $externalId, $matches)) {
            return Tenant::find((int) $matches[1]);
        }
        return null;
    }
}
