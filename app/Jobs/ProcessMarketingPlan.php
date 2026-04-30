<?php

namespace App\Jobs;

use App\Models\MarketingPlan;
use App\Services\MarketingAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMarketingPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(protected int $planId) {}

    public function handle(MarketingAIService $ai): void
    {
        $plan = MarketingPlan::find($this->planId);
        if (!$plan) return;

        $plan->update(['status' => 'processing']);

        try {
            $ai->generate($plan);
        } catch (\Exception $e) {
            Log::error("ProcessMarketingPlan #{$this->planId}: " . $e->getMessage());
            $plan->update(['status' => 'failed']);
        }
    }
}
