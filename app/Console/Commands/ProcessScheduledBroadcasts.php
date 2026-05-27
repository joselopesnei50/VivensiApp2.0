<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensagemCampanhaJob;
use App\Jobs\ProcessBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\Campanha;
use Illuminate\Console\Command;
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
                ->update(['status' => 'queued']);

            if ($updated === 0) {
                Log::info("Broadcast #{$campaign->id} já foi disparado por outro processo. Skipping.");
                continue;
            }

            ProcessBroadcastCampaignJob::dispatch($campaign->id, $campaign->tenant_id);
            Log::info("Broadcast agendado disparado: campanha #{$campaign->id} (tenant {$campaign->tenant_id})");
            $this->info("Campanha #{$campaign->id} despachada.");
        }

        if ($campaigns->isNotEmpty()) {
            $this->info("{$campaigns->count()} broadcast(s) verificado(s).");
        }

        // Campanhas opt-in agendadas (model Campanha)
        $optin = DB::transaction(function () {
            return Campanha::withoutGlobalScopes()
                ->where('status', 'agendada')
                ->where('agendada_para', '<=', now())
                ->lockForUpdate()
                ->get();
        });

        foreach ($optin as $campanha) {
            $updated = Campanha::withoutGlobalScopes()
                ->where('id', $campanha->id)
                ->where('status', 'agendada')
                ->update(['status' => 'processando', 'total_enviados' => 0, 'total_falhas' => 0]);

            if ($updated === 0) {
                Log::info("Campanha opt-in #{$campanha->id} já processada por outro worker. Skipping.");
                continue;
            }

            $total = \App\Models\ContatoWhatsapp::withoutGlobalScopes()
                ->where('tenant_id', $campanha->tenant_id)
                ->where('opt_in', true)
                ->where('opt_out', false)
                ->count();

            Campanha::withoutGlobalScopes()
                ->where('id', $campanha->id)
                ->update(['total_contatos' => $total]);

            EnviarMensagemCampanhaJob::dispatch($campanha->id)->onQueue('whatsapp');
            Log::info("Campanha opt-in agendada disparada: #{$campanha->id} (tenant {$campanha->tenant_id})");
            $this->info("Campanha opt-in #{$campanha->id} despachada.");
        }
    }
}
