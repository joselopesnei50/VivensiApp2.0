<?php

namespace App\Console\Commands;

use App\Services\Billing\InvoiceNotifier;
use Illuminate\Console\Command;

class NotifyInvoices extends Command
{
    protected $signature = 'invoices:notify';
    protected $description = 'Envia os e-mails de cobrança (nova fatura, lembrete, vencida, bloqueio).';

    public function handle(InvoiceNotifier $notifier): int
    {
        $c = $notifier->runDaily();
        $this->info("E-mails enviados — nova: {$c['nova']}, lembrete: {$c['lembrete']}, vencida: {$c['vencida']}, bloqueio: {$c['bloqueio']}");
        return self::SUCCESS;
    }
}
