<?php

namespace App\Console\Commands;

use App\Jobs\RecalcularConformidadeJob;
use App\Models\Tenant;
use App\Services\ComplianceCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConformidadeRecalculateAll extends Command
{
    protected $signature = 'conformidade:recalculate-all
                            {--tenant= : ID de um tenant específico (opcional)}
                            {--sync   : Roda de forma síncrona sem enfileirar}
                            {--dry-run : Simula sem recalcular}';

    protected $description = 'Invalida cache e reagenda recálculo de conformidade para todos os tenants NGO ativos';

    public function handle(ComplianceCalculationService $service): int
    {
        $isDryRun = $this->option('dry-run');
        $isSync   = $this->option('sync');
        $tenantId = $this->option('tenant');

        $query = Tenant::where('subscription_status', 'active')->where('type', 'ngo');
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();
        $count   = 0;

        $this->info("Processando {$tenants->count()} tenant(s)...");

        foreach ($tenants as $tenant) {
            if ($isDryRun) {
                $this->line("  [dry-run] #{$tenant->id} {$tenant->name} → pulando");
                continue;
            }

            $service->invalidarCache($tenant->id);

            if ($isSync) {
                $service->calcularDashboard($tenant->id);
                $this->line("  ✅ #{$tenant->id} {$tenant->name} recalculado (síncrono)");
            } else {
                RecalcularConformidadeJob::dispatch($tenant->id);
                $this->line("  ✅ #{$tenant->id} {$tenant->name} → job despachado");
            }

            $count++;
        }

        Log::info('conformidade:recalculate-all concluído', [
            'processados' => $count,
            'sync'        => $isSync,
        ]);
        $this->info("Concluído: {$count} tenant(s) processado(s).");

        return 0;
    }
}
