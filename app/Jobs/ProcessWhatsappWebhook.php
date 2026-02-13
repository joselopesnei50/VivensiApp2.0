<?php

namespace App\Jobs;

use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use App\Services\ZApiService;
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

        $eventType = (string) ($this->payload['type'] ?? '');

        // Route to appropriate handler based on event type
        match($eventType) {
            'ReceivedMessage' => $this->handleReceivedMessage($config),
            'MessageDelivered', 'MessageSent' => $this->handleDelivered($config),
            'MessageRead' => $this->handleRead($config),
            'Disconnected', 'DisconnectedMobile' => $this->handleDisconnected($config),
            default => Log::info('WhatsApp webhook: unknown event type', [
                'type' => $eventType,
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
        $waId = (string) ($this->payload['phone'] ?? '');
        $content = (string) ($this->payload['text']['message'] ?? '');
        $messageId = (string) ($this->payload['messageId'] ?? '');

        // Basic validation
        if ($waId === '' || $messageId === '') {
            Log::warning('WhatsApp webhook: missing required fields', [
                'tenant_id' => $tenantId,
                'payload' => $this->payload,
            ]);
            return;
        }

        // Idempotency: ignore duplicate webhook deliveries
        if (WhatsappMessage::where('message_id', $messageId)->exists()) {
            Log::info('WhatsApp webhook: duplicate message ignored', [
                'message_id' => $messageId,
                'tenant_id' => $tenantId,
            ]);
            return;
        }

        // 1. Find or create chat
        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $waId],
            [
                'contact_name' => $this->payload['senderName'] ?? 'Cliente WhatsApp',
                'contact_phone' => $waId,
                'status' => 'open',
                'opt_in_at' => now(), // Inbound message implies opt-in
            ]
        );

        $chat->update(['last_message_at' => now(), 'last_inbound_at' => now()]);

        WhatsappAuditLog::create([
            'tenant_id' => $tenantId,
            'chat_id' => $chat->id,
            'actor_type' => 'webhook',
            'event' => 'webhook_inbound',
            'details' => [
                'message_id' => $messageId,
                'content_len' => mb_strlen($content),
            ],
        ]);

        // STOP keyword compliance (opt-out detection)
        $normalized = mb_strtolower(trim($content));
        $stopKeywords = [
            'stop', 'parar', 'pare', 'sair', 'cancelar', 'cancele', 
            'descadastrar', 'remover', 'não quero', 'nao quero'
        ];
        
        foreach ($stopKeywords as $kw) {
            if ($kw !== '' && str_contains($normalized, $kw)) {
                $chat->opt_out_at = now();
                $chat->blocked_at = now();
                $chat->blocked_reason = 'STOP keyword';
                $chat->status = 'closed';
                $chat->save();
                
                WhatsappAuditLog::create([
                    'tenant_id' => $tenantId,
                    'chat_id' => $chat->id,
                    'actor_type' => 'webhook',
                    'event' => 'compliance_action',
                    'details' => ['action' => 'opt_out', 'reason' => 'STOP keyword', 'keyword' => $kw],
                ]);
                
                return; // Don't process further
            }
        }

        // Ensure opt-in is set (for older chats)
        if (!$chat->opt_in_at) {
            $chat->opt_in_at = now();
            $chat->save();
        }

        // 2. Save message to database
        WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $messageId,
            'content' => $content,
            'direction' => 'inbound',
            'status' => 'delivered', // Inbound messages are always delivered
        ]);

        // 3. Trigger AI auto-reply if enabled
        if ($config->ai_enabled && (!$chat->assigned_to || $chat->status == 'open') && !$chat->opt_out_at && !$chat->blocked_at) {
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                ->onQueue('whatsapp');
        }
    }

    /**
     * Handle message delivered confirmation
     */
    private function handleDelivered(WhatsappConfig $config): void
    {
        $messageId = (string) ($this->payload['messageId'] ?? '');
        
        if ($messageId === '') {
            return;
        }

        $message = WhatsappMessage::where('message_id', $messageId)->first();
        
        if ($message) {
            $message->update(['status' => 'delivered']);
            
            Log::info('WhatsApp message delivered', [
                'message_id' => $messageId,
                'tenant_id' => $config->tenant_id,
            ]);
        }
    }

    /**
     * Handle message read confirmation
     */
    private function handleRead(WhatsappConfig $config): void
    {
        $messageId = (string) ($this->payload['messageId'] ?? '');
        
        if ($messageId === '') {
            return;
        }

        $message = WhatsappMessage::where('message_id', $messageId)->first();
        
        if ($message) {
            $message->update(['status' => 'read']);
            
            Log::info('WhatsApp message read', [
                'message_id' => $messageId,
                'tenant_id' => $config->tenant_id,
            ]);
        }
    }

    /**
     * Handle instance disconnection (critical event)
     */
    private function handleDisconnected(WhatsappConfig $config): void
    {
        Log::critical('WhatsApp instance disconnected', [
            'tenant_id' => $config->tenant_id,
            'instance_id' => $config->instance_id,
            'payload' => $this->payload,
        ]);

        WhatsappAuditLog::create([
            'tenant_id' => $config->tenant_id,
            'actor_type' => 'webhook',
            'event' => 'instance_disconnected',
            'details' => $this->payload,
        ]);

        // TODO: Send alert to admins (email/Slack/SMS)
        // Example: Mail::to($config->tenant->admin_email)->send(new WhatsAppDisconnectedAlert($config));
    }
}
