<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\AbacatePayService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de faturas mensais recorrentes.
 *
 * Regras:
 *   - Tenants com subscription_status != active NÃO geram invoice
 *   - Planos com is_courtesy=true NÃO geram invoice
 *   - Idempotente por (tenant_id, period_start) — unique no DB
 *   - Vencimento default: 5 dias após criação
 *   - Overdue: status muda pra overdue quando due_date < now - 3d
 *   - Ao criar, tenta gerar checkout AbacatePay pra guardar links de pagamento
 */
class InvoiceService
{
    /** A fatura do próximo ciclo é gerada N dias antes do aniversário. */
    public const ISSUE_DAYS_BEFORE      = 5;
    /** Dias de atraso até o painel ficar restrito à tela de faturas. */
    public const BLOCK_DAYS_AFTER_DUE   = 5;
    /**
     * Bloqueio e e-mails de cobrança valem só para faturas com vencimento a
     * partir desta data — faturas antigas em aberto são revisadas à mão
     * (evita bloquear organizações no dia do deploy).
     */
    public const ENFORCE_FROM = '2026-11-10';
    /** Prazo mínimo para pagar uma fatura que nasce com o ciclo já iniciado. */
    private const MIN_DAYS_TO_PAY       = 5;

    /**
     * Gera a fatura do mês corrente pra um tenant (se ainda não existe).
     * Retorna null se skipou (cortesia, sem plano, inativo, etc).
     */
    public function generateForTenant(Tenant $tenant): ?Invoice
    {
        if ($tenant->subscription_status !== 'active') {
            return null;
        }

        $plan = $tenant->plan;
        if (!$plan) {
            return null;
        }
        if ($plan->is_courtesy) {
            return null;
        }
        if ((float) $plan->price <= 0) {
            return null;
        }

        // Fatura em aberto sem link (API fora do ar quando nasceu): tenta de novo.
        $semLink = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
            ->whereNull('abacatepay_billing_url')->orderBy('due_date')->first();
        if ($semLink) {
            $this->tryGenerateAbacateCheckout($semLink, $tenant, $plan);
        }

        // Ciclo pela data da assinatura: o próximo período começa no dia
        // seguinte ao fim do último; gera só a partir de N dias antes.
        $start = $this->nextPeriodStart($tenant);
        if ($start->gt(now()->startOfDay()->addDays(self::ISSUE_DAYS_BEFORE))) {
            return $semLink;
        }

        $periodStart = $start->toDateString();
        $periodEnd   = $start->copy()->addMonthNoOverflow()->subDay()->toDateString();
        $minDue      = now()->startOfDay()->addDays(self::MIN_DAYS_TO_PAY);
        $dueDate     = ($start->gt($minDue) ? $start : $minDue)->toDateString();
        $amountCents = (int) round(((float) $plan->price) * 100);

        return DB::transaction(function () use ($tenant, $plan, $periodStart, $periodEnd, $dueDate, $amountCents, $start) {
            $existing = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereDate('period_start', $periodStart)
                ->first();

            if ($existing) {
                // Invoice ja existe. Se nao tem link AbacatePay (nasceu quando a
                // API estava fora do ar, ou foi criada por versao antiga do code),
                // tenta recuperar agora — senao cliente fica com fatura sem botao.
                if ($existing->isPayable() && empty($existing->abacatepay_billing_url)) {
                    $this->tryGenerateAbacateCheckout($existing, $tenant, $plan);
                }
                return $existing;
            }

            $invoice = Invoice::create([
                'tenant_id'    => $tenant->id,
                'plan_id'      => $plan->id,
                'amount_cents' => $amountCents,
                'description' => "Assinatura {$plan->name} — " . $start->format('d/m/Y') . ' a ' . Carbon::parse($periodEnd)->format('d/m/Y'),
                'status'       => Invoice::STATUS_OPEN,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'due_date'     => $dueDate,
            ]);

            // Tenta pré-gerar checkout AbacatePay pra guardar links de pagamento.
            // Se falhar, invoice fica sem link — cliente vê só "Copiar PIX".
            $this->tryGenerateAbacateCheckout($invoice, $tenant, $plan);

            return $invoice;
        });
    }

    /**
     * Roda o gerador pra todos tenants active. Chamado pelo command
     * invoices:generate-recurring no scheduler dia 1 às 00:10.
     * Retorna [created, skipped].
     */
    public function generateForAllActive(): array
    {
        $created = 0;
        $skipped = 0;
        Tenant::where('subscription_status', 'active')
            ->with('plan')
            ->orderBy('id')
            ->chunk(100, function ($tenants) use (&$created, &$skipped) {
                foreach ($tenants as $tenant) {
                    try {
                        $invoice = $this->generateForTenant($tenant);
                        if ($invoice) { $created++; } else { $skipped++; }
                    } catch (\Throwable $e) {
                        Log::warning('InvoiceService: falha ao gerar invoice', [
                            'tenant_id' => $tenant->id,
                            'error'     => $e->getMessage(),
                        ]);
                        $skipped++;
                    }
                }
            });
        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Marca invoice como paga. Idempotente — chamar 2x não muda nada.
     * Fonte: 'abacatepay' (webhook), 'pix_manual' (admin), 'manual_admin'
     */
    public function markAsPaid(Invoice $invoice, string $paidVia, ?int $actorUserId = null, ?string $abacatepayChargeId = null): Invoice
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return $invoice;
        }
        $result = DB::transaction(function () use ($invoice, $paidVia, $actorUserId, $abacatepayChargeId) {
            $invoice->status         = Invoice::STATUS_PAID;
            $invoice->paid_at        = now();
            $invoice->paid_via       = $paidVia;
            if ($actorUserId)         $invoice->actor_user_id = $actorUserId;
            if ($abacatepayChargeId)  $invoice->abacatepay_charge_id = $abacatepayChargeId;
            $invoice->save();
            \Illuminate\Support\Facades\Cache::forget(self::blockCacheKey($invoice->tenant_id));
            return $invoice;
        });

        if ($paidVia !== Invoice::PAID_VIA_FREE_COURTESY) {
            try {
                app(InvoiceNotifier::class)->send($result, 'paga');
            } catch (\Throwable $e) {
                Log::error('Fatura: falha ao avisar pagamento', ['invoice_id' => $result->id, 'error' => $e->getMessage()]);
            }
        }
        return $result;
    }

    /**
     * Marca invoices vencidas há mais de N dias como overdue.
     * Chamado pelo command invoices:mark-overdue no scheduler diário.
     * Retorna quantidade marcada.
     */
    public function markOverdue(): int
    {
        // Vencida a partir do dia seguinte ao vencimento.
        return Invoice::withoutGlobalScopes()
            ->where('status', Invoice::STATUS_OPEN)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update([
                'status'     => Invoice::STATUS_OVERDUE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Início do próximo período a faturar:
     *  1. dia seguinte ao fim da última fatura (não cancelada);
     *  2. sem fatura: 1 mês após o 1º pagamento pelo checkout (que cobriu o 1º mês);
     *  3. conta antiga sem nenhum dos dois: início do mês atual (regra anterior).
     */
    public function nextPeriodStart(Tenant $tenant): Carbon
    {
        $last = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', '!=', Invoice::STATUS_CANCELED)
            ->orderByDesc('period_end')->first();
        if ($last && $last->period_end) {
            return Carbon::parse($last->period_end)->startOfDay()->addDay();
        }

        $paidAt = \App\Models\Transaction::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', 'paid')->where('external_id', 'like', 'VIVENSI_%')
            ->orderByDesc('paid_at')->value('paid_at');
        if ($paidAt) {
            return Carbon::parse($paidAt)->startOfDay()->addMonthNoOverflow();
        }

        return now()->startOfMonth();
    }

    /**
     * 1º pagamento (checkout de assinatura) vira a fatura paga do 1º ciclo:
     * de hoje até o dia anterior do mesmo dia no mês seguinte. Idempotente.
     */
    public function recordInitialPayment(Tenant $tenant, ?\App\Models\SubscriptionPlan $plan, ?float $amount, ?string $chargeId): ?Invoice
    {
        $today = now()->startOfDay();
        $covering = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', '!=', Invoice::STATUS_CANCELED)
            ->whereDate('period_start', '<=', $today->toDateString())
            ->whereDate('period_end', '>=', $today->toDateString())
            ->first();
        if ($covering) {
            return $covering;
        }

        $end = $today->copy()->addMonthNoOverflow()->subDay();
        return Invoice::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'period_start' => $today->toDateString()],
            [
                'plan_id'              => $plan?->id ?? $tenant->plan_id,
                'amount_cents'         => (int) round(((float) ($amount ?? $plan?->price ?? 0)) * 100),
                'description'          => 'Assinatura ' . ($plan?->name ?? '') . ' — ' . $today->format('d/m/Y') . ' a ' . $end->format('d/m/Y'),
                'status'               => Invoice::STATUS_PAID,
                'period_end'           => $end->toDateString(),
                'due_date'             => $today->toDateString(),
                'paid_at'              => now(),
                'paid_via'             => Invoice::PAID_VIA_ABACATEPAY,
                'abacatepay_charge_id' => $chargeId,
            ]
        );
    }

    public static function blockCacheKey(int $tenantId): string
    {
        return "tenant.{$tenantId}.billing_block";
    }

    /** Fatura vencida há BLOCK_DAYS_AFTER_DUE dias ou mais → painel restrito às faturas. */
    public function isBlocked(Tenant $tenant): bool
    {
        return \Illuminate\Support\Facades\Cache::remember(self::blockCacheKey($tenant->id), 300, function () use ($tenant) {
            return Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
                ->whereDate('due_date', '<=', now()->subDays(self::BLOCK_DAYS_AFTER_DUE)->toDateString())
                ->whereDate('due_date', '>=', self::ENFORCE_FROM)
                ->exists();
        });
    }

    /** Link de pagamento novo para a fatura (o anterior pode ter expirado). */
    public function freshPaymentUrl(Invoice $invoice): ?string
    {
        $tenant = Tenant::find($invoice->tenant_id);
        $plan = \App\Models\SubscriptionPlan::find($invoice->plan_id) ?? $tenant?->plan;
        if (!$tenant || !$plan || !$invoice->isPayable()) {
            return null;
        }
        $invoice->abacatepay_billing_url = null;
        $this->tryGenerateAbacateCheckout($invoice, $tenant, $plan);
        return $invoice->abacatepay_billing_url;
    }

    /** Confere na AbacatePay se a fatura já foi paga (tela de faturas). */
    public function syncFromGateway(Invoice $invoice): bool
    {
        if (!$invoice->isPayable() || !$invoice->abacatepay_charge_id) {
            return false;
        }
        $checkout = app(AbacatePayService::class)->getCheckout((string) $invoice->abacatepay_charge_id);
        $st = strtoupper((string) ($checkout['status'] ?? ''));
        if (in_array($st, ['PAID', 'COMPLETED', 'APPROVED'], true)) {
            $this->markAsPaid($invoice, Invoice::PAID_VIA_ABACATEPAY, null, $invoice->abacatepay_charge_id);
            return true;
        }
        return false;
    }

    /**
     * Tenta gerar checkout hospedado AbacatePay e salva o link na invoice.
     * Falha silenciosa — cliente sempre pode pagar via PIX manual (chave
     * Vivensi) como fallback.
     *
     * Usa /checkouts/create (único endpoint de cobrança que funciona nesta
     * conta AbacatePay — /transparents/create retorna 422, ver
     * docs/ABACATEPAY_INTEGRATION_STUCK.md) com o produto do plano e
     * externalId 'invoice_<id>_<ts>' — o webhook checkout.completed reconhece
     * o prefixo e marca a invoice como paga.
     */
    private function tryGenerateAbacateCheckout(Invoice $invoice, Tenant $tenant, \App\Models\SubscriptionPlan $plan): void
    {
        if (empty($plan->abacatepay_product_id)) {
            Log::warning('InvoiceService: plano sem abacatepay_product_id — invoice sem link de pagamento', [
                'invoice_id' => $invoice->id,
                'plan_id'    => $plan->id,
            ]);
            return;
        }

        try {
            $service = app(AbacatePayService::class);

            $checkout = $service->createCheckout(
                items: [['id' => $plan->abacatepay_product_id, 'quantity' => 1]],
                externalId: 'invoice_' . $invoice->id . '_' . time(),
                returnUrl: config('app.url') . '/minha-conta/faturas',
                completionUrl: config('app.url') . '/minha-conta/faturas',
                methods: ['PIX'],
                metadata: [
                    'invoice_id' => $invoice->id,
                    'tenant_id'  => $tenant->id,
                    'plan_id'    => $invoice->plan_id,
                    'period'     => (string) $invoice->period_start,
                ]
            );

            if (!$checkout || empty($checkout['url'])) {
                return; // createCheckout já logou o erro
            }

            $invoice->abacatepay_charge_id   = $checkout['id'] ?? null;
            $invoice->abacatepay_billing_url = $checkout['url'];
            $invoice->save();
        } catch (\Throwable $e) {
            Log::warning('InvoiceService: pré-gerar checkout AbacatePay falhou (não bloqueante)', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
