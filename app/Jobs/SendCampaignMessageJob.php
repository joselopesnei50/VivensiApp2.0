<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WhatsappCampaignMessage;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappConfig;
use App\Services\EvolutionApiService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;
    
    // Allow up to 3 tries per job
    public $tries = 3;
    
    // Tell Laravel to wait before retrying
    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = WhatsappCampaignMessage::with('campaign')->find($this->messageId);

        if (!$message || $message->status !== 'pending') {
            return;
        }

        $campaign = $message->campaign;
        
        if (!$campaign || $campaign->status !== 'processing') {
            return;
        }

        $tenantId = $campaign->tenant_id;
        
        // Anti-ban: Blacklist Check
        $isBlacklisted = WhatsappBlacklist::where('tenant_id', $tenantId)
            ->where('phone', $message->contact_phone)
            ->exists();

        if ($isBlacklisted) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Número na Blacklist (Opt-out).'
            ]);
            return;
        }

        // Get Context Model (e.g., Tenant)
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant || !$tenant->evolution_instance_name) {
             $message->update([
                'status' => 'failed',
                'error_message' => 'Instância da Evolution API não configurada no Tenant.'
            ]);
            return;
        }

        // Send via Evolution API
        try {
            $evo = new EvolutionApiService($tenant);
            
            // Note: EvolutionApiService applySpintax will handle the {{contact_name}} variables and the Spintax itself.
            $rawMessage = $campaign->message_template;
            
            // Anti-ban: Block known shorteners usually flagged as spam by WhatsApp
            $blockedShorteners = ['bit.ly', 'cutt.ly', 't.ly', 'tinyurl.com', 'is.gd', 'rebrand.ly'];
            foreach ($blockedShorteners as $shortener) {
                if (stripos($rawMessage, $shortener) !== false) {
                    $message->update([
                        'status' => 'failed',
                        'error_message' => 'Bloqueado por conter link encurtador (Anti-Ban).'
                    ]);
                    return;
                }
            }
            
            // Replace generic variables before Spintax
            if ($message->contact_name) {
                 $rawMessage = str_replace(['{{nome}}', '{{contato}}', '{{name}}', '{{contact_name}}'], $message->contact_name, $rawMessage);
            }

            // We apply a delay between 3 and 10 seconds per message to simulate human typing
            $delay = rand(3, 10);
            
            $result = $evo->sendMessage($message->contact_phone, $rawMessage, $delay);
            
            if (isset($result['key'])) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    // Rendered message is whatever EvoApi actually sent, but we can't easily retrieve the final spintax from the sendMessage method unless we grab it before sending. As applySpintax is inside sendMessage, we just mark it sent.
                    'rendered_message' => $rawMessage
                ]);
            } else {
                $message->update([
                    'status' => 'failed',
                    'error_message' => isset($result['error']) ? json_encode($result['error']) : 'Erro desconhecido.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SendCampaignMessageJob Failed.', ['error' => $e->getMessage(), 'message_id' => $this->messageId]);
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
