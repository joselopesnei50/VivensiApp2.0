<?php

namespace App\Jobs;

use App\Services\ComplianceCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalcularConformidadeJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
        $this->onQueue('default');
    }

    // Garante no máximo 1 job na fila por tenant
    public function uniqueId(): string
    {
        return (string) $this->tenantId;
    }

    public function uniqueFor(): int
    {
        return 300; // lock de 5 minutos
    }

    public function handle(ComplianceCalculationService $service): void
    {
        $service->invalidarCache($this->tenantId);
        $service->persistirAvaliacoesTipoA($this->tenantId);
        Log::info("RecalcularConformidade: tenant {$this->tenantId} recalculado.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("RecalcularConformidadeJob falhou para tenant {$this->tenantId}: {$e->getMessage()}");
    }
}
