<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Revisão das faturas antigas (rodar uma vez, à mão).
 *
 *  1. Cancela faturas em aberto de organizações sem plano pago (cortesia/teste).
 *  2. Cancela faturas em aberto com vencimento antes da assinatura do contrato.
 *  3. Pagamento de assinatura sem fatura paga correspondente → cria a fatura paga.
 *
 * Sem --aplicar só mostra o que faria.
 */
class ReviewLegacyInvoices extends Command
{
    protected $signature = 'invoices:revisar {--aplicar : Executa as mudanças (sem isso, só mostra)}';
    protected $description = 'Revisa faturas antigas: cancela as que não valem e registra pagamentos sem fatura.';

    public function handle(InvoiceService $service): int
    {
        $apply = (bool) $this->option('aplicar');
        $this->info($apply ? 'Aplicando mudanças.' : 'Simulação (nada é alterado). Use --aplicar para executar.');

        $rows = [];
        Tenant::with('plan')->orderBy('id')->chunk(100, function ($tenants) use ($service, $apply, &$rows) {
            foreach ($tenants as $tenant) {
                foreach ($service->freePlanInvoices($tenant) as $inv) {
                    $rows[] = [$tenant->id, $tenant->name, "#{$inv->id}", $inv->formatted_amount, $inv->due_date?->format('d/m/Y'), 'Cancelar: plano sem cobrança'];
                }
                if ($apply) {
                    $service->cancelForFreePlan($tenant);
                }

                $listed = $service->freePlanInvoices($tenant)->pluck('id')->all();
                foreach ($service->preContractInvoices($tenant) as $inv) {
                    if (in_array($inv->id, $listed, true)) {
                        continue;
                    }
                    $rows[] = [$tenant->id, $tenant->name, "#{$inv->id}", $inv->formatted_amount, $inv->due_date?->format('d/m/Y'), 'Cancelar: antes do contrato'];
                }
                if ($apply) {
                    $service->cancelPreContract($tenant);
                }

                $pay = $this->unrecordedPayment($tenant);
                if ($pay) {
                    $rows[] = [$tenant->id, $tenant->name, 'nova', 'R$ ' . number_format((float) $pay->amount, 2, ',', '.'),
                        Carbon::parse($pay->paid_at)->format('d/m/Y'), 'Criar fatura paga (pagamento sem fatura)'];
                    if ($apply) {
                        $service->recordInitialPayment($tenant, $tenant->plan, (float) $pay->amount, null, Carbon::parse($pay->paid_at));
                    }
                }
            }
        });

        if (!$rows) {
            $this->info('Nada a revisar.');
            return self::SUCCESS;
        }
        $this->table(['Org', 'Nome', 'Fatura', 'Valor', 'Data', 'Ação'], $rows);
        $this->info($apply ? 'Concluído.' : 'Para executar: php artisan invoices:revisar --aplicar');
        return self::SUCCESS;
    }

    /** Último pagamento de assinatura (após o contrato) sem fatura paga que cubra a data. */
    private function unrecordedPayment(Tenant $tenant): ?Transaction
    {
        if (!$tenant->contract_signed_at) {
            return null;
        }
        $pay = Transaction::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', 'paid')->where('external_id', 'like', 'VIVENSI_%')
            ->where('paid_at', '>=', Carbon::parse($tenant->contract_signed_at)->startOfDay())
            ->orderByDesc('paid_at')->first();
        if (!$pay) {
            return null;
        }
        $day = Carbon::parse($pay->paid_at)->toDateString();
        $covered = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('status', Invoice::STATUS_PAID)
            ->whereDate('period_start', '<=', $day)->whereDate('period_end', '>=', $day)
            ->exists();
        return $covered ? null : $pay;
    }
}
