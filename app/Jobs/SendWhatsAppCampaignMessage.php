<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\EvolutionApiService;

class SendWhatsAppCampaignMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;
    protected $delaySeconds;

    /**
     * Create a new job instance.
     *
     * @param int $messageId
     * @param int $delaySeconds
     */
    public function __construct($messageId, $delaySeconds = 15)
    {
        $this->messageId = $messageId;
        $this->delaySeconds = $delaySeconds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = DB::table('whatsapp_campaign_messages')->find($this->messageId);
        
        if (!$message || $message->status !== 'pending') {
            return;
        }

        $campaign = DB::table('whatsapp_campaigns')->find($message->whatsapp_campaign_id);
        
        if (!$campaign || $campaign->status !== 'running') {
            return; 
        }

        // Determina o contexto: ONG ou Manager
        $contextModel = null;
        if ($campaign->tenant_id) {
            $contextModel = \App\Models\Tenant::find($campaign->tenant_id);
        } elseif ($campaign->user_id) {
            $contextModel = \App\Models\User::find($campaign->user_id);
        }

        if (!$contextModel) {
            DB::table('whatsapp_campaign_messages')->where('id', $this->messageId)->update([
                'status' => 'failed',
                'error_message' => 'Context (Tenant/User) not found'
            ]);
            return;
        }

        DB::table('whatsapp_campaign_messages')->where('id', $this->messageId)->update(['status' => 'sending']);

        $service = new EvolutionApiService($contextModel);

        // O EvolutionApiService cuidará de Spintax e do Delay/Presence através do construtor/método.
        $result = $service->sendMessage(
            $message->contact_phone, 
            $campaign->message_template, // We send template, service applies spintax
            null, // idempotency
            $this->delaySeconds
        );

        if (isset($result['error'])) {
            DB::table('whatsapp_campaign_messages')->where('id', $this->messageId)->update([
                'status' => 'failed',
                'error_message' => json_encode($result),
                'updated_at' => now()
            ]);
        } else {
            DB::table('whatsapp_campaign_messages')->where('id', $this->messageId)->update([
                'status' => 'sent',
                'rendered_message' => 'Sent via API (Spintax resolved)',
                'sent_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
