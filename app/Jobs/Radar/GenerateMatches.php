<?php

namespace App\Jobs\Radar;

use App\Services\Radar\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 120;
    public array $backoff = [30, 60];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(MatchingService $matching): void
    {
        $total = $matching->generateAll();

        Log::info("GenerateMatches: {$total} novos matches criados.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateMatches falhou.', ['error' => $e->getMessage()]);
    }
}
