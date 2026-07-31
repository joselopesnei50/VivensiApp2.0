<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService\WhatsappQuotaService;
use Illuminate\Console\Command;

/**
 * Reseta cota mensal WhatsApp de todos os tenants.
 * Agendado no scheduler pra rodar dia 1 de cada mês às 00:05.
 * Idempotente — pode rodar 2x no mesmo dia sem side effect (zera pra zero).
 */
class ResetMonthlyWhatsappQuotas extends Command
{
    protected $signature = 'whatsapp:reset-monthly-quotas';
    protected $description = 'Zera contador mensal + packs extras remanescentes de todos tenants (Modelo comercial C).';

    public function handle(WhatsappQuotaService $service): int
    {
        $this->info('Iniciando reset mensal de cotas WhatsApp...');
        $count = $service->resetAllMonthlyQuotas();
        $this->info("Reset aplicado a {$count} tenants.");
        return self::SUCCESS;
    }
}
