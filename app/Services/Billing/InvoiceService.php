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
                ->where('period_start', $periodStart)
                ->first();

            if ($existing) {
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
            $this->tryGenerateAbacateCheckout($invoice, $tenant);

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
     * Tenta gerar cobrança PIX Transparent na AbacatePay e salva brCode +
     * brCodeBase64 na invoice. Falha silenciosa — cliente sempre pode pagar
     * via PIX manual (chave Vivensi) como fallback.
     *
     * Usa /transparents/create em vez de /checkouts/create porque:
     *  - AbacatePay exige Produto pré-cadastrado pra checkouts (não temos)
     *  - Transparent aceita valor arbitrário direto (ideal pra invoice)
     *  - Retorna QR code embutido (melhor UX que redirect)
     *  - Só PIX (que é o modelo comercial do Vivensi hoje)
     */
    private function tryGenerateAbacateCheckout(Invoice $invoice, Tenant $tenant): void
    {
        try {
            $service = app(AbacatePayService::class);

            $customer = array_filter([
                'name'  => $tenant->name,
                'email' => $tenant->email ?? null,
                'taxId' => $tenant->tax_id ?? $tenant->document ?? null,
            ]);

            $metadata = [
                'invoice_id'  => $invoice->id,
                'tenant_id'   => $tenant->id,
                'plan_id'     => $invoice->plan_id,
                'period'      => (string) $invoice->period_start,
                'external_id' => 'invoice_' . $invoice->id,
            ];

            $data = $service->createPixCharge(
                $invoice->amount_cents,
                $invoice->description,
                $customer,
                $metadata,
                86400 * 5 // QR válido por 5 dias (bate com due_date)
            );

            if (!$data) return;

            $invoice->abacatepay_charge_id      = $data['id']           ?? null;
            $invoice->abacatepay_pix_url        = $data['brCode']       ?? null; // copia-e-cola
            $invoice->abacatepay_pix_qr_base64  = $data['brCodeBase64'] ?? null; // PNG base64
            $invoice->abacatepay_billing_url    = $data['url']          ?? null; // pode não vir em transparent
            $invoice->save();
        } catch (\Throwable $e) {
            Log::warning('InvoiceService: pré-gerar cobrança PIX AbacatePay falhou (não bloqueante)', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
