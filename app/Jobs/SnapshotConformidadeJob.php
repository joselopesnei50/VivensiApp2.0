<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ComplianceCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SnapshotConformidadeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
        $this->onQueue('default');
    }

    public function handle(ComplianceCalculationService $service): void
    {
        if ($this->tenantId) {
            $service->gerarSnapshot($this->tenantId);
            return;
        }

        // Modo batch: itera tenants ativos e despacha job individual para cada um
        Tenant::withoutGlobalScopes()
            ->where('subscription_status', 'active')
            ->pluck('id')
            ->each(function (int $tenantId) {
                static::dispatch($tenantId)->delay(now()->addSeconds(rand(1, 30)));
            });

        Log::info('SnapshotConformidadeJob: jobs individuais despachados.');
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SnapshotConformidadeJob falhou (tenant={$this->tenantId}): {$e->getMessage()}");
    }
}
