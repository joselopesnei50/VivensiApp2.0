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
        $this->info("Starting geocoding backfill...");

        $beneficiaries = Beneficiary::whereNotNull('address')
            ->where('address', '!=', '')
            ->whereNull('latitude')
            ->get();

        $beneficiaryCount = $beneficiaries->count();
        $this->info("Found {$beneficiaryCount} beneficiaries to geocode.");

        foreach ($beneficiaries as $beneficiary) {
            GeocodeAddressJob::dispatch($beneficiary);
        }

        $donors = NgoDonor::whereNotNull('address')
            ->where('address', '!=', '')
            ->whereNull('latitude')
            ->get();

        $donorCount = $donors->count();
        $this->info("Found {$donorCount} donors to geocode.");

        foreach ($donors as $donor) {
            GeocodeAddressJob::dispatch($donor);
        }

        $this->info("Backfill complete. " . ($beneficiaryCount + $donorCount) . " jobs dispatched to the queue.");
        
        return Command::SUCCESS;
    }
}
