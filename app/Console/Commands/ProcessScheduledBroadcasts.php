<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BroadcastCampaign;
use App\Jobs\ProcessBroadcastCampaignJob;
use Illuminate\Support\Facades\Log;

class ProcessScheduledBroadcasts extends Command
{
    protected $signature   = 'broadcast:process-scheduled';
    protected $description = 'Despacha campanhas de broadcast agendadas que estão na hora de enviar.';

    public function handle(): void
    {
        $campaigns = BroadcastCampaign::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'processing']);
            ProcessBroadcastCampaignJob::dispatch($campaign->id);
            Log::info("Broadcast agendado disparado: campanha #{$campaign->id} (tenant {$campaign->tenant_id})");
        }

        if ($campaigns->isNotEmpty()) {
            $this->info("{$campaigns->count()} campanha(s) despachada(s).");
        }
    }
}
