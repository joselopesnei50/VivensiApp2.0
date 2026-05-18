<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BroadcastCampaign;
use App\Jobs\ProcessBroadcastCampaignJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessScheduledBroadcasts extends Command
{
    protected $signature   = 'broadcast:process-scheduled';
    protected $description = 'Despacha campanhas de broadcast agendadas que estão na hora de enviar.';

    public function handle(): void
    {
        // lockForUpdate previne race condition se o command rodar em paralelo
        $campaigns = DB::transaction(function () {
            return BroadcastCampaign::withoutGlobalScopes()
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<=', now())
                ->lockForUpdate()
                ->get();
        });

        foreach ($campaigns as $campaign) {
            // Garante transição atômica: só dispara se ainda estiver 'scheduled'
            $updated = BroadcastCampaign::withoutGlobalScopes()
                ->where('id', $campaign->id)
                ->where('status', 'scheduled')
                ->update(['status' => 'processing']);

            if ($updated === 0) {
                Log::info("Broadcast #{$campaign->id} já foi disparado por outro processo. Skipping.");
                continue;
            }

            ProcessBroadcastCampaignJob::dispatch($campaign->id);
            Log::info("Broadcast agendado disparado: campanha #{$campaign->id} (tenant {$campaign->tenant_id})");
            $this->info("Campanha #{$campaign->id} despachada.");
        }

        if ($campaigns->isNotEmpty()) {
            $this->info("{$campaigns->count()} campanha(s) verificada(s).");
        }
    }
}
