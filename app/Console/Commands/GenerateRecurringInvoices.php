<?php

namespace App\Console\Commands;

use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

/**
 * Gera invoices mensais recorrentes pra todos tenants active + não-cortesia.
 * Agendado dia 1 às 00:10 no scheduler. Idempotente por (tenant, period_start).
 */
class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Gera as faturas mensais dos tenants ativos com plano pago.';

    public function handle(InvoiceService $service): int
    {
        $this->info('Gerando faturas mensais...');
        $r = $service->generateForAllActive();
        $this->info("Concluído: {$r['created']} criadas, {$r['skipped']} puladas (cortesia/inativos/sem plano/já existente).");
        return self::SUCCESS;
    }
}
