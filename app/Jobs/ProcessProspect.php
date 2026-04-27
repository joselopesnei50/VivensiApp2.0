<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiAnalysisService;

class ProcessProspect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    protected int $prospectId;

    public function __construct(\App\Models\Prospect $prospect)
    {
        $this->prospectId = $prospect->id;
    }

    public function handle(GeminiAnalysisService $ai)
    {
        $prospect = \App\Models\Prospect::find($this->prospectId);

        if (!$prospect) {
            Log::warning("ProcessProspect: Prospect #{$this->prospectId} não encontrado, job ignorado.");
            return;
        }

        if ($prospect->status === 'analyzed') {
            return; // já analisado, ignora
        }

        $ai->analyze($prospect);
    }
}
