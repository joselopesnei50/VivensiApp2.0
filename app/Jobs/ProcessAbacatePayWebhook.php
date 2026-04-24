<?php

namespace App\Jobs;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        if (!$checkout || !$externalId) {
            Log::warning('AbacatePay: checkout.completed sem externalId');
            return;
        }

        $tenant = $this->findTenantByExternalId($externalId);

        if (!$tenant) {
            Log::error('AbacatePay: tenant não encontrado', ['externalId' => $externalId]);
            return;
        }

        // Extrair plan_id do metadata
        $planId = $checkout['metadata']['plan_id'] ?? null;

        // Atualizar transação como paga
        try {
            Transaction::withoutGlobalScopes()
                ->where('external_id', $externalId)
                ->update([
                    'status'          => 'paid',
                    'approval_status' => 'approved',
                    'paid_at'         => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('AbacatePay: falha ao atualizar transação', ['error' => $e->getMessage(), 'externalId' => $externalId]);
        }

        // Ativar assinatura e vincular plano
        $tenant->subscription_status = 'active';
        if ($planId && SubscriptionPlan::find($planId)) {
            $tenant->plan_id = $planId;
        }
        $tenant->save();

        Log::info('AbacatePay: checkout.completed processado', [
            'tenant'     => $tenant->id,
            'plan_id'    => $planId,
            'externalId' => $externalId,
            'amount'     => ($checkout['paidAmount'] ?? 0) / 100,
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
            $tenant->subscription_status = 'canceled';
            $tenant->save();
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
            $tenant->subscription_status = 'canceled';
            $tenant->save();
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
