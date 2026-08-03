<?php

namespace App\Console\Commands;

use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

/**
 * Marca invoices open vencidas há >3 dias como overdue.
 * Agendado diário às 06:00 no scheduler.
 */
class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';
    protected $description = 'Marca invoices vencidas há mais de 3 dias como overdue.';

    public function handle(InvoiceService $service): int
    {
        $count = $service->markOverdue();
        $this->info("Faturas marcadas como vencidas: {$count}");
        return self::SUCCESS;
    }
}
