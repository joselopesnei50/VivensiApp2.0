<?php

namespace App\Jobs\Radar;

use App\Models\RadarFinding;
use App\Services\Radar\FindingEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnrichRadarFindings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 2;
    public int   $timeout = 300;
    public array $backoff = [60];

    private const BATCH_SIZE = 50;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(FindingEnrichmentService $service): void
    {
        $findings = RadarFinding::whereNull('ai_processed_at')
            ->orderBy('created_at')
            ->limit(self::BATCH_SIZE)
            ->get();

        if ($findings->isEmpty()) {
            Log::info('EnrichRadarFindings: nenhum finding pendente.');
            return;
        }

        $enriched = 0;

        foreach ($findings as $finding) {
            try {
                if ($service->enrich($finding)) {
                    $enriched++;
                }
            } catch (\Throwable $e) {
                Log::warning("EnrichRadarFindings: falha no finding #{$finding->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("EnrichRadarFindings: {$enriched}/{$findings->count()} findings enriquecidos.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('EnrichRadarFindings falhou.', ['error' => $e->getMessage()]);
    }
}
