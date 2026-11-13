<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Cancelamento pelo cliente (Termos de Uso, item 5):
 *  - sem multa; o acesso continua até o fim do período já pago e não saem novas faturas;
 *  - em até 7 dias da primeira contratação: devolução integral (art. 49 do CDC) —
 *    o acesso termina na hora e o suporte faz o estorno;
 *  - depois do fim do acesso, os dados ficam guardados por 90 dias.
 */
class SubscriptionCancellation
{
    public const REFUND_DAYS = 7;
    public const RETENTION_DAYS = 90;

    public function __construct(private InvoiceService $invoices)
    {
    }

    /** Primeiro pagamento da assinatura, se houver. */
    public function firstPaymentAt(Tenant $tenant): ?Carbon
    {
        $tx = Transaction::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', 'paid')->where('external_id', 'like', 'VIVENSI_%')
            ->min(DB::raw('COALESCE(paid_at, updated_at)'));
        $inv = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', Invoice::STATUS_PAID)->min('paid_at');

        $dates = array_filter([$tx ? Carbon::parse($tx) : null, $inv ? Carbon::parse($inv) : null]);
        return $dates ? collect($dates)->min() : null;
    }

    /** Pode pedir devolução integral: primeiro pagamento há no máximo 7 dias. */
    public function refundEligible(Tenant $tenant): bool
    {
        $first = $this->firstPaymentAt($tenant);
        return $first !== null && $first->gte(now()->subDays(self::REFUND_DAYS));
    }

    /** Último dia coberto pelo que já foi pago (fatura em aberto não conta). */
    public function paidUntil(Tenant $tenant): Carbon
    {
        $lastPaidEnd = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', Invoice::STATUS_PAID)->max('period_end');
        if ($lastPaidEnd) {
            $until = Carbon::parse($lastPaidEnd);
        } elseif ($first = $this->firstPaymentAt($tenant)) {
            $until = $first->copy()->startOfDay()->addMonthNoOverflow()->subDay();
        } else {
            $until = now();
        }
        return $until->lt(now()->startOfDay()) ? now()->startOfDay() : $until->startOfDay();
    }

    /**
     * @return array{refund: bool, access_until: Carbon}
     */
    public function cancel(Tenant $tenant, User $by, ?string $reason): array
    {
        $refund = $this->refundEligible($tenant);
        $until  = $refund ? now()->startOfDay() : $this->paidUntil($tenant);

        DB::transaction(function () use ($tenant, $reason, $until, $refund) {
            $tenant->forceFill([
                'cancel_requested_at' => now(),
                'cancel_reason'       => $reason ? mb_substr($reason, 0, 500) : null,
                'access_until'        => $until->toDateString(),
            ]);
            if ($refund) {
                // Arrependimento: o acesso acaba agora e o valor volta inteiro.
                $tenant->subscription_status = 'canceled';
                $tenant->canceled_at = now();
            }
            $tenant->save();

            // Faturas que ainda não foram pagas e cobririam depois do fim do acesso.
            Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
                ->whereDate('period_start', '>', $until->toDateString())
                ->update(['status' => Invoice::STATUS_CANCELED]);
        });

        \Illuminate\Support\Facades\Cache::forget("tenant.{$tenant->id}");
        \Illuminate\Support\Facades\Cache::forget(InvoiceService::blockCacheKey($tenant->id));

        $this->notify($tenant, $by, $reason, $until, $refund);

        return ['refund' => $refund, 'access_until' => $until];
    }

    /** Desistiu do cancelamento antes do fim do acesso. */
    public function keep(Tenant $tenant): bool
    {
        if (!$tenant->hasScheduledCancellation()) {
            return false;
        }
        $tenant->forceFill(['cancel_requested_at' => null, 'cancel_reason' => null, 'access_until' => null])->save();
        \Illuminate\Support\Facades\Cache::forget("tenant.{$tenant->id}");
        return true;
    }

    /** Rotina diária: encerra o acesso de quem chegou ao fim do período pago. */
    public function finishExpired(): int
    {
        $count = 0;
        Tenant::where('subscription_status', 'active')
            ->whereNotNull('cancel_requested_at')
            ->whereDate('access_until', '<', now()->toDateString())
            ->each(function (Tenant $t) use (&$count) {
                $t->forceFill(['subscription_status' => 'canceled', 'canceled_at' => now()])->save();
                \Illuminate\Support\Facades\Cache::forget("tenant.{$t->id}");
                $count++;
            });
        return $count;
    }

    /** Organizações canceladas há mais de 90 dias (dados a excluir). */
    public function dueForDeletion()
    {
        return Tenant::where('subscription_status', 'canceled')
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->orderBy('canceled_at')
            ->get(['id', 'name', 'canceled_at']);
    }

    private function notify(Tenant $tenant, User $by, ?string $reason, Carbon $until, bool $refund): void
    {
        $support = config('legal.email_support');
        try {
            Mail::to($by->email)->send(new \App\Mail\SubscriptionCanceledMail($tenant, $until, $refund));
        } catch (\Throwable $e) {
            Log::error('Cancelamento: falha ao enviar e-mail ao cliente', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
        try {
            $lines = [
                ($refund ? '[REEMBOLSO INTEGRAL PENDENTE] ' : '') . "Cancelamento de assinatura: {$tenant->name} (organização #{$tenant->id})",
                "Pedido por: {$by->name} <{$by->email}>",
                'Acesso até: ' . $until->format('d/m/Y'),
                'Motivo: ' . ($reason ?: '(não informado)'),
            ];
            if ($refund) {
                $lines[] = 'Primeiro pagamento há menos de 7 dias: fazer o estorno integral na AbacatePay.';
            }
            Mail::raw(implode("\n", $lines), function ($m) use ($support, $tenant, $refund) {
                $m->to($support)->subject(($refund ? 'Reembolso + cancelamento: ' : 'Cancelamento: ') . $tenant->name);
            });
        } catch (\Throwable $e) {
            Log::error('Cancelamento: falha ao avisar o suporte', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }
}
