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
    private const DUE_DAYS_AFTER_CREATION   = 5;
    private const OVERDUE_DAYS_AFTER_DUE    = 3;

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

        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd   = now()->endOfMonth()->toDateString();
        $dueDate     = now()->addDays(self::DUE_DAYS_AFTER_CREATION)->toDateString();
        $amountCents = (int) round(((float) $plan->price) * 100);

        return DB::transaction(function () use ($tenant, $plan, $periodStart, $periodEnd, $dueDate, $amountCents) {
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
                'description' => "Assinatura {$plan->name} — " . Carbon::parse($periodStart)->translatedFormat('F/Y'),
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
        return DB::transaction(function () use ($invoice, $paidVia, $actorUserId, $abacatepayChargeId) {
            $invoice->status         = Invoice::STATUS_PAID;
            $invoice->paid_at        = now();
            $invoice->paid_via       = $paidVia;
            if ($actorUserId)         $invoice->actor_user_id = $actorUserId;
            if ($abacatepayChargeId)  $invoice->abacatepay_charge_id = $abacatepayChargeId;
            $invoice->save();
            return $invoice;
        });
    }

    /**
     * Marca invoices vencidas há mais de N dias como overdue.
     * Chamado pelo command invoices:mark-overdue no scheduler diário.
     * Retorna quantidade marcada.
     */
    public function markOverdue(): int
    {
        $cutoff = now()->subDays(self::OVERDUE_DAYS_AFTER_DUE)->toDateString();
        return Invoice::withoutGlobalScopes()
            ->where('status', Invoice::STATUS_OPEN)
            ->whereDate('due_date', '<', $cutoff)
            ->update([
                'status'     => Invoice::STATUS_OVERDUE,
                'updated_at' => now(),
            ]);
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
