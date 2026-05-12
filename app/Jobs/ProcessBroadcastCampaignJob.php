<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BroadcastCampaign;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBroadcastCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignId;
    public $timeout = 3600;
    public $tries   = 1;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle()
    {
        $campaign = BroadcastCampaign::find($this->campaignId);
        if (!$campaign || $campaign->status === 'completed') {
            return;
        }

        $campaign->update(['status' => 'processing']);

        $instance = WhatsappInstance::where('tenant_id', $campaign->tenant_id)
            ->where('status', 'open')->first();

        if (!$instance) {
            $campaign->update(['status' => 'failed']);
            Log::error("Broadcast failed: No active instance for tenant {$campaign->tenant_id}");
            return;
        }

        $evo = new EvolutionApiService($instance);
        $recipients = $this->getRecipients($campaign);

        if ($recipients->isEmpty()) {
            $campaign->update(['status' => 'completed']);
            return;
        }

        $sentCount = 0;
        $failedCount = 0;

        $mediaToSend = null;
        $imageMime   = 'image/jpeg';
        
        if ($campaign->has_image && $campaign->image_path) {
            // Tentamos primeiro enviar a URL pública (mais leve, evita erro 413)
            $mediaToSend = Storage::disk('public')->url($campaign->image_path);
            
            // Se a URL não for absoluta (ex: /storage/...), prefixamos com o APP_URL
            if (!str_starts_with($mediaToSend, 'http')) {
                $mediaToSend = rtrim(config('app.url'), '/') . $mediaToSend;
            }

            if (Storage::disk('public')->exists($campaign->image_path)) {
                $imageMime = Storage::disk('public')->mimeType($campaign->image_path);
            }
        }

        foreach ($recipients as $recipient) {
            // Refresh campaign to check for manual cancellation (optional)
            $campaign->refresh();
            if ($campaign->status !== 'processing') break;

            try {
                $waId = $recipient->wa_id;
                
                $res = $mediaToSend
                    ? $evo->sendMedia($waId, $mediaToSend, $campaign->message, $imageMime)
                    : $evo->sendMessage($waId, $campaign->message, null, rand(1, 3));

                if (!isset($res['error']) && !empty($res)) {
                    if (isset($recipient->id)) {
                        WhatsappMessage::create([
                            'chat_id'    => $recipient->id,
                            'message_id' => $res['key']['id'] ?? ($res['messageId'] ?? ('BROADCAST_' . uniqid())),
                            'content'    => $campaign->has_image ? ('[imagem] ' . $campaign->message) : $campaign->message,
                            'direction'  => 'outbound',
                            'type'       => $campaign->has_image ? 'image' : 'text',
                        ]);
                    }
                    $sentCount++;
                } else {
                    $errorMsg = is_array($res) ? json_encode($res) : ($res ?: 'Unknown Error');
                    Log::warning("Broadcast failed for {$waId}. Campaign ID: {$campaign->id}. Error: " . $errorMsg);
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Broadcast recipient exception for {$waId}. Campaign ID: {$campaign->id}. Message: " . $e->getMessage());
                $failedCount++;
            }

            $campaign->update([
                'total_sent'   => $sentCount,
                'total_failed' => $failedCount
            ]);

            sleep($campaign->cadence ?: 3);
        }

        $campaign->update(['status' => 'completed']);
    }

    protected function getRecipients($campaign)
    {
        if ($campaign->audience_type === 'groups') {
            $groupIds = $campaign->group_ids ?: [];
            return collect($groupIds)->map(fn($id) => (object)['wa_id' => $id, 'id' => null]);
        }

        if ($campaign->audience_type === 'selected' && $campaign->phones) {
            $phones = array_map(
                fn($p) => preg_replace('/\D+/', '', $p),
                explode(',', $campaign->phones)
            );
            $phones = array_filter($phones, fn($p) => strlen($p) >= 10);

            $existingChats = WhatsappChat::where('tenant_id', $campaign->tenant_id)
                ->whereIn('wa_id', $phones)
                ->get()
                ->keyBy('wa_id');

            return collect($phones)->map(function ($phone) use ($existingChats) {
                if ($existingChats->has($phone)) {
                    $chat = $existingChats->get($phone);
                    if ($chat->opt_out_at || $chat->blocked_at) return null;
                    return (object)['wa_id' => $phone, 'id' => $chat->id];
                }
                return (object)['wa_id' => $phone, 'id' => null];
            })->filter()->values();
        }

        return WhatsappChat::where('tenant_id', $campaign->tenant_id)
            ->whereNull('opt_out_at')->whereNull('blocked_at')
            ->get();
    }
}
