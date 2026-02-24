<?php

namespace App\Jobs;

use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Z-API Webhook Events (Async)
 * 
 * Handles all inbound webhook events from Z-API:
 * - ReceivedMessage: Create chat, save message, trigger AI
 * - MessageDelivered: Update message status to 'delivered'
 * - MessageRead: Update message status to 'read'
 * - Disconnected: Alert admins, log critical event
 * 
 * CRITICAL: This job MUST be dispatched immediately from the webhook controller
 * to ensure HTTP 200 is returned to Z-API within <100ms (prevents retries).
 */
class ProcessWhatsappWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $configId;
    public array $payload;

    /**
     * @param int $configId WhatsappConfig ID
     * @param array $payload Raw webhook payload from Z-API
     */
    public function __construct(int $configId, array $payload)
    {
        $this->configId = $configId;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $config = WhatsappConfig::find($this->configId);
        
        if (!$config) {
            Log::warning('WhatsApp webhook: config not found', ['config_id' => $this->configId]);
            return;
        }

        Log::info('Evolution Webhook Payload', ['payload' => $this->payload]);

        $eventType = (string) ($this->payload['event'] ?? '');

        // Route to appropriate handler based on event type
        match($eventType) {
            'messages.upsert' => $this->handleReceivedMessage($config),
            'messages.update' => $this->handleMessageUpdate($config), // Handles read/delivered
            'connection.update' => $this->handleConnectionUpdate($config),
            default => Log::info('Evolution webhook: unknown event type', [
                'event' => $eventType,
                'tenant_id' => $config->tenant_id,
            ]),
        };
    }

    /**
     * Handle inbound message from customer
     */
    private function handleReceivedMessage(WhatsappConfig $config): void
    {
        $tenantId = $config->tenant_id;
        $messageData = $this->payload['data'] ?? [];
        
        $isFromMe = ($messageData['key']['fromMe'] ?? false) === true;

        $remoteJid = (string) ($messageData['key']['remoteJid'] ?? '');
        $sender = (string) ($this->payload['sender'] ?? '');

        // Standardize wa_id: prefer sender (standard JID) for individual chats if remoteJid is LID
        // This ensures the AI uses a reachable JID for outbound replies.
        $waId = $remoteJid;
        if (!str_contains($remoteJid, '@g.us') && !empty($sender)) {
            $waId = $sender;
        }

        $phonePrefix = explode('@', $waId)[0]; 
        $messageId = (string) ($messageData['key']['id'] ?? '');
        
        $messageObj = $messageData['message'] ?? [];
        $content = '';
        if (isset($messageObj['conversation'])) {
            $content = $messageObj['conversation'];
        } elseif (isset($messageObj['extendedTextMessage']['text'])) {
            $content = $messageObj['extendedTextMessage']['text'];
        }

        // Basic validation
        if ($waId === '' || $messageId === '' || $content === '') {
            return;
        }

        // Idempotency: ignore duplicate webhook deliveries
        if (WhatsappMessage::where('message_id', $messageId)->exists()) {
            return;
        }

        // 1. Smart Resolver: find existing chat by any identifier to avoid duplication
        $chat = WhatsappChat::where('tenant_id', $tenantId)
            ->where(function($q) use ($waId, $phonePrefix) {
                $q->where('wa_id', $waId)
                  ->orWhere('wa_id', $phonePrefix)
                  ->orWhere('contact_phone', $phonePrefix);
            })
            ->first();

        if (!$chat) {
            $chat = WhatsappChat::create([
                'tenant_id' => $tenantId,
                'wa_id' => $waId,
                'contact_name' => $messageData['pushName'] ?? 'Cliente WhatsApp',
                'contact_phone' => $phonePrefix,
                'status' => 'open',
                'opt_in_at' => now(), 
            ]);
        } else {
            // Standardize wa_id to reachable JID if the event provides a full one
            if (str_contains($waId, '@s.whatsapp.net') && $chat->wa_id !== $waId) {
                $chat->update(['wa_id' => $waId]);
            }
            
            // Cleanup: merge other duplicate chats for this number to clean up the sidebar
            $duplicates = WhatsappChat::where('tenant_id', $tenantId)
                ->where('id', '!=', $chat->id)
                ->where(function($q) use ($waId, $phonePrefix) {
                    $q->where('contact_phone', $phonePrefix)
                      ->orWhere('wa_id', $phonePrefix);
                })
                ->get();
            
            foreach ($duplicates as $dupe) {
                WhatsappMessage::where('chat_id', $dupe->id)->update(['chat_id' => $chat->id]);
                WhatsappAuditLog::where('chat_id', $dupe->id)->update(['chat_id' => $chat->id]);
                $dupe->delete();
            }
        }

        // Standardize JID suffix for delivery
        if (!str_contains($chat->wa_id, '@')) {
            $chat->update(['wa_id' => $chat->wa_id . '@s.whatsapp.net']);
        }

        // Ensure opt-in is set (for older chats)
        if (!$chat->opt_in_at) {
            $chat->opt_in_at = now();
            $chat->save();
        }

        // Save message to database
        WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $messageId,
            'content' => $content,
            'direction' => $isFromMe ? 'outbound' : 'inbound',
            'status' => 'delivered', 
        ]);

        // 3. Trigger AI auto-reply (ONLY for real inbound messages, NOT from sync)
        if (!$isFromMe && $config->ai_enabled && (!$chat->assigned_to || $chat->status == 'open') && !$chat->opt_out_at && !$chat->blocked_at) {
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                ->onQueue('whatsapp');
        }
    }

    /**
     * Handle message read/delivered confirmation
     */
    private function handleMessageUpdate(WhatsappConfig $config): void
    {
        $updates = $this->payload['data'] ?? [];
        foreach ($updates as $update) {
            $messageId = (string) ($update['key']['id'] ?? '');
            $status = $update['update']['status'] ?? null; // 3 = delivered, 4 = read
            
            if ($messageId === '' || $status === null) {
                continue;
            }

            $message = WhatsappMessage::where('message_id', $messageId)->first();
            
            if ($message) {
                if ($status == 3 || $status === 'DELIVERY_ACK') {
                    $message->update(['status' => 'delivered']);
                } elseif ($status == 4 || $status === 'READ') {
                    $message->update(['status' => 'read']);
                }
            }
        }
    }

    /**
     * Handle instance disconnection or state updates
     */
    private function handleConnectionUpdate(WhatsappConfig $config): void
    {
        $state = $this->payload['data']['state'] ?? '';
        
        if ($state === 'close') {
            Log::critical('WhatsApp instance disconnected', [
                'tenant_id' => $config->tenant_id,
                'instance_name' => $this->payload['instance'] ?? 'unknown',
                'payload' => $this->payload,
            ]);

            WhatsappAuditLog::create([
                'tenant_id' => $config->tenant_id,
                'actor_type' => 'webhook',
                'event' => 'instance_disconnected',
                'details' => $this->payload,
            ]);
        }
    }
}
