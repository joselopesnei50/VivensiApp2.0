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
            // lockForUpdate garante que dois workers simultâneos não processem o mesmo registro
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
        $customer = $payload['data']['customer'] ?? null;
        $sub      = $payload['data']['subscription'] ?? null;

        if (!$customer || !$sub) return;

        $tenant = Tenant::whereHas('owner', fn($q) => $q->where('email', $customer['email']))->first();

        if ($tenant) {
            $tenant->subscription_status = 'active';
            $tenant->save();
            Log::info('AbacatePay: subscription ativada', ['tenant' => $tenant->id, 'subId' => $sub['id'] ?? null]);
        }
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $customer = $payload['data']['customer'] ?? null;
        if (!$customer) return;

        $tenant = Tenant::whereHas('owner', fn($q) => $q->where('email', $customer['email']))->first();

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
                'new_values'     => ['subscription_status' => 'canceled', 'gateway' => 'abacatepay', 'reason' => 'subscription.cancelled'],
            ]);
            Log::info('AbacatePay: subscription cancelada', ['tenant' => $tenant->id]);
        }
    }

    private function findTenantByExternalId(string $externalId): ?Tenant
    {
        if (preg_match('/^VIVENSI_(\d+)_/', $externalId, $matches)) {
            return Tenant::find((int) $matches[1]);
        }
        return null;
    }
}
