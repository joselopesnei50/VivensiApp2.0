<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IbgeIndicatorCache;
use App\Services\IBGEDataService;

class SyncIBGEIndicators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vivensi:sync-ibge {city_code?}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sync IBGE indicators for all cached cities or a specific city';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(IBGEDataService $ibgeService)
    {
        $cityCode = $this->argument('city_code');

        if ($cityCode) {
            $this->info("Syncing indicators for city: $cityCode");
            $ibgeService->getCityIndicators($cityCode);
            $this->success("Sync completed for $cityCode.");
            return 0;
        }

        $cities = IbgeIndicatorCache::distinct()->pluck('city_ibge_code');
        
        if ($cities->isEmpty()) {
            $this->warn("No cities found in cache to sync.");
            return 0;
        }

        $this->info("Syncing indicators for " . $cities->count() . " cities...");
        $bar = $this->output->createProgressBar($cities->count());

        foreach ($cities as $code) {
            $ibgeService->getCityIndicators($code);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sync completed successfully.");

        return 0;
    }
}
