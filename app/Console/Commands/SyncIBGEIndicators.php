<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IbgeIndicatorCache;
use App\Services\IBGEDataService;

class SyncIBGEIndicators extends Command
{
    protected $signature   = 'vivensi:sync-ibge {city_code?}';
    protected $description = 'Sync IBGE indicators for all cached cities or a specific city';

    public function handle(IBGEDataService $ibgeService): int
    {
        $cityCode = $this->argument('city_code');

        if ($cityCode) {
            $this->info("Sincronizando indicadores para o município: {$cityCode}");
            try {
                $ibgeService->getCityIndicators($cityCode);
                $this->info("✅ Sincronização concluída para {$cityCode}.");
            } catch (\Exception $e) {
                $this->error("❌ Falha ao sincronizar {$cityCode}: " . $e->getMessage());
                return 1;
            }
            return 0;
        }

        $cities = IbgeIndicatorCache::distinct()->pluck('city_ibge_code');

        if ($cities->isEmpty()) {
            $this->warn('Nenhuma cidade no cache para sincronizar.');
            return 0;
        }

        $this->info("Sincronizando {$cities->count()} municípios...");
        $bar    = $this->output->createProgressBar($cities->count());
        $errors = 0;

        foreach ($cities as $code) {
            try {
                $ibgeService->getCityIndicators($code);
            } catch (\Exception $e) {
                $errors++;
                \Illuminate\Support\Facades\Log::warning("vivensi:sync-ibge falhou para {$code}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($errors > 0) {
            $this->warn("Concluído com {$errors} falha(s). Verifique o log.");
        } else {
            $this->info('✅ Sincronização concluída com sucesso.');
        }

        return 0;
    }
}
