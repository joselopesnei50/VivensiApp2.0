<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAbacatePayWebhook;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\AbacatePayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reconcilia Transactions AbacatePay pendentes consultando a API direta.
 *
 * Cobre o cenário em que o webhook de confirmação foi perdido (worker fora do
 * ar, rede ruim, payload rejeitado) e a Transaction ficou stuck em "pending"
 * com o cliente já tendo pago no checkout hospedado da AbacatePay.
 *
 * Quando acha uma Transaction paga na API mas pendente local, dispara um job
 * sintético ProcessAbacatePayWebhook com event='checkout.completed' — reusa
 * TODA a lógica do handler (idempotência via processed_webhooks, AuditLog,
 * ativação do tenant). Se o webhook real chegar depois, o handler reconhece
 * o webhook_id sintético no processed_webhooks e ignora — sem duplicação.
 *
 * Recomendado agendar a cada 5 min via scheduler (Kernel.php).
 */
class AbacatePayReconcile extends Command
{
    protected $signature = 'abacatepay:reconcile
        {--minutes-old=5  : idade mínima (em minutos) da Transaction pendente pra entrar na varredura}
        {--limit=100      : máximo de transactions inspecionadas por execução}
        {--dry-run        : só relata o que faria, sem disparar jobs}';

    protected $description = 'Reconcilia Transactions AbacatePay pendentes consultando o status direto na API';

    public function handle(AbacatePayService $abacate): int
    {
        $minutesOld = (int) $this->option('minutes-old');
        $limit      = (int) $this->option('limit');
        $dryRun     = (bool) $this->option('dry-run');
        $cutoff     = Carbon::now()->subMinutes($minutesOld);

        // Filtra AbacatePay pelo padrão do external_id (sempre VIVENSI_*) +
        // gateway_id setado. Transactions não-AbacatePay com o mesmo padrão
        // (improvavel) sao filtradas no loop quando getCheckout devolve erro.
        $pending = Transaction::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('external_id', 'like', 'VIVENSI_%')
            ->whereNotNull('gateway_id')
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at')
            ->limit($limit)
            ->get(['id', 'tenant_id', 'external_id', 'gateway_id', 'created_at']);

        $this->info("Encontradas {$pending->count()} transactions pendentes há > {$minutesOld} min" . ($dryRun ? ' [DRY-RUN]' : ''));

        $reconciled = 0;
        $stillPending = 0;
        $expired = 0;
        $errors = 0;

        foreach ($pending as $tx) {
            $checkout = null;
            try {
                $checkout = $abacate->getCheckout((string) $tx->gateway_id);
            } catch (\Throwable $e) {
                $errors++;
                $this->warn("Tx #{$tx->id} ({$tx->external_id}): erro ao consultar API — " . $e->getMessage());
                continue;
            }

            if (!$checkout) {
                $errors++;
                $this->warn("Tx #{$tx->id} ({$tx->external_id}): API não devolveu checkout.");
                continue;
            }

            $status = strtoupper((string) ($checkout['status'] ?? ''));

            if (in_array($status, ['PAID', 'COMPLETED', 'APPROVED'], true)) {
                $reconciled++;
                $this->info("Tx #{$tx->id} ({$tx->external_id}): PAGO no gateway. " . ($dryRun ? 'Pulando dispatch (dry-run).' : 'Disparando job sintético.'));

                if (!$dryRun) {
                    // Payload sintético com o mesmo formato do webhook real.
                    // webhook_id prefixado com "reconcile_" pra distinguir no log
                    // e pra não colidir com IDs reais do gateway.
                    ProcessAbacatePayWebhook::dispatch('checkout.completed', [
                        'id'   => 'reconcile_' . $tx->external_id . '_' . time(),
                        'data' => ['checkout' => $checkout],
                    ]);
                }
            } elseif (in_array($status, ['EXPIRED', 'CANCELLED', 'CANCELED', 'FAILED'], true)) {
                $expired++;
                $this->line("Tx #{$tx->id} ({$tx->external_id}): {$status} no gateway. Considere marcar como cancelada via comando separado.");
            } else {
                $stillPending++;
                $this->line("Tx #{$tx->id} ({$tx->external_id}): ainda {$status} no gateway.");
            }
        }

        if ($pending->isNotEmpty()) {
            $this->newLine();
            $this->info("== Resumo ==");
            $this->info("Reconciliadas (paid): {$reconciled}");
            $this->info("Ainda pendentes:      {$stillPending}");
            $this->info("Expiradas/canceladas: {$expired}");
            $this->info("Erros de API:         {$errors}");
        }

        $this->reconcileInvoices($abacate, $cutoff, $limit, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Segunda varredura: invoices mensais (externalId invoice_<id>_<ts>) não
     * geram Transaction, então precisam de reconciliação própria. Consulta o
     * checkout salvo em abacatepay_charge_id e, se pago, dispara o mesmo job
     * sintético checkout.completed — o handler reconhece o prefixo invoice_.
     */
    private function reconcileInvoices(AbacatePayService $abacate, Carbon $cutoff, int $limit, bool $dryRun): void
    {
        $pending = Invoice::withoutGlobalScopes()
            ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
            ->whereNotNull('abacatepay_charge_id')
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at')
            ->limit($limit)
            ->get(['id', 'tenant_id', 'abacatepay_charge_id', 'status', 'created_at']);

        $this->newLine();
        $this->info("Invoices abertas com checkout AbacatePay: {$pending->count()}" . ($dryRun ? ' [DRY-RUN]' : ''));

        if ($pending->isEmpty()) {
            return;
        }

        $reconciled = 0;

        foreach ($pending as $invoice) {
            try {
                $checkout = $abacate->getCheckout((string) $invoice->abacatepay_charge_id);
            } catch (\Throwable $e) {
                $this->warn("Invoice #{$invoice->id}: erro ao consultar API — " . $e->getMessage());
                continue;
            }

            if (!$checkout) {
                $this->warn("Invoice #{$invoice->id}: API não devolveu checkout.");
                continue;
            }

            $status = strtoupper((string) ($checkout['status'] ?? ''));

            if (in_array($status, ['PAID', 'COMPLETED', 'APPROVED'], true)) {
                $reconciled++;
                $this->info("Invoice #{$invoice->id}: PAGA no gateway. " . ($dryRun ? 'Pulando dispatch (dry-run).' : 'Disparando job sintético.'));

                if (!$dryRun) {
                    // Garante externalId no payload sintético — o handler resolve
                    // tenant e invoice a partir dele.
                    $checkout['externalId'] = $checkout['externalId'] ?? ('invoice_' . $invoice->id);

                    ProcessAbacatePayWebhook::dispatch('checkout.completed', [
                        'id'   => 'reconcile_invoice_' . $invoice->id . '_' . time(),
                        'data' => ['checkout' => $checkout],
                    ]);
                }
            } else {
                $this->line("Invoice #{$invoice->id}: ainda {$status} no gateway.");
            }
        }

        $this->info("Invoices reconciliadas (paid): {$reconciled}");
    }
}
