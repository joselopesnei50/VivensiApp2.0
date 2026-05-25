<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Beneficiary;
use App\Models\NgoDonor;
use App\Jobs\GeocodeAddressJob;

class GeocodeBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geocode:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch geocoding jobs for existing beneficiaries and donors that have addresses but no coordinates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando geocodificação retroativa...');

        $beneficiaries = Beneficiary::whereNotNull('address')
            ->where('address', '!=', '')
            ->whereNull('latitude')
            ->get(['id', 'address', 'tenant_id']);

        $donors = NgoDonor::whereNotNull('address')
            ->where('address', '!=', '')
            ->whereNull('latitude')
            ->get(['id', 'address', 'tenant_id']);

        $total = $beneficiaries->count() + $donors->count();
        $this->info("Registros encontrados: {$beneficiaries->count()} beneficiários, {$donors->count()} doadores. Total: {$total}");

        if ($total === 0) {
            $this->info('Nenhum registro para geocodificar.');
            return Command::SUCCESS;
        }

        // Espaçar jobs de 3 em 3 segundos para respeitar o rate limit do Nominatim (1 req/s)
        $delay = 5;
        foreach ($beneficiaries as $b) {
            GeocodeAddressJob::dispatch($b)->delay(now()->addSeconds($delay));
            $delay += 3;
        }
        foreach ($donors as $d) {
            GeocodeAddressJob::dispatch($d)->delay(now()->addSeconds($delay));
            $delay += 3;
        }

        $eta = round($delay / 60, 1);
        $this->info("{$total} jobs enfileirados com escalonamento. Tempo estimado: ~{$eta} minutos.");

        return Command::SUCCESS;
    }
}
