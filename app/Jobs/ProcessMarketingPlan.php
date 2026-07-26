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
    public int $timeout = 240; // 4min: 120 do plano + 90 do guia + margem

    public function __construct(protected int $planId)
    {
        $this->onQueue('ai');
    }

    public function handle(MarketingAIService $ai): void
    {
        $plan = MarketingPlan::find($this->planId);
        if (!$plan) return;

        $plan->update(['status' => 'processing']);

        try {
            $ok = $ai->generate($plan);
        } catch (\Exception $e) {
            Log::error("ProcessMarketingPlan #{$this->planId}: " . $e->getMessage());
            $plan->update(['status' => 'failed']);
            return;
        }

        if (!$ok) {
            return; // status já ficou 'failed' dentro do generate()
        }

        // Segunda chamada opcional: Guia do Bruce (execução prescritiva).
        // Falha aqui NÃO invalida o plano — guide fica com status 'failed' e
        // pode ser regenerado sob demanda pela UI.
        $plan->refresh();
        try {
            $guide = $ai->generateExecutionGuide($plan);
            if ($guide) {
                $plan->update([
                    'execution_guide'      => $guide,
                    'guide_status'         => 'ready',
                    'guide_generated_at'   => now(),
                ]);
            } else {
                $plan->update([
                    'guide_status'       => 'failed',
                    'guide_generated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("ProcessMarketingPlan guide #{$this->planId}: " . $e->getMessage());
            $plan->update([
                'guide_status'       => 'failed',
                'guide_generated_at' => now(),
            ]);
        }
    }
}
