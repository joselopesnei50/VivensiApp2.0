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
        match(strtolower($eventType)) {
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
        $sender = (string) ($this->payload['sender'] ?? $messageData['sender'] ?? '');
        $remoteJid = (string) ($messageData['key']['remoteJid'] ?? '');

        // 1. BLOQUEIO DE IDENTIDADE (Bot Protegido contra si mesmo)
        // Se a mensagem é fromMe, o 'sender' é o PRÓPRIO NÚMERO DO BOT.
        // Vamos guardar esse número para NUNCA mais responder a ele.
        if ($isFromMe && $sender !== '') {
            if ($config->instance_id !== $sender) {
                $config->update(['instance_id' => $sender]); 
                Log::info('WhatsApp: Identificado ID oficial do Bot', ['bot_jid' => $sender]);
            }
            return; // Bot ignorando a si mesmo
        }

        // Se o remetente da mensagem for o ID conhecido do bot, ABORTA IMEDIATAMENTE.
        if ($sender !== '' && $sender === $config->instance_id) {
            Log::warning('WhatsApp: Loop detectado e bloqueado (sender é o Bot)', ['sender' => $sender]);
            return;
        }

        // 2. REGRA DE OURO DA JID: O remoteJid É O CHAT. 
        // Para a Evolution API entregar mensagens @lid, precisamos enviar o JID COMPLETO.
        $effectiveJid = $remoteJid;
        $messageId   = (string) ($messageData['key']['id'] ?? '');

        if ($effectiveJid === '' || $messageId === '') {
            Log::warning('WhatsApp webhook: incomplete message data', ['remoteJid' => $remoteJid, 'sender' => $sender]);
            return;
        }

        // Idempotency: ignore duplicate webhook deliveries
        if (WhatsappMessage::where('message_id', $messageId)->exists()) {
            return;
        }

        $phonePrefix = explode('@', $remoteJid)[0]; 
        $messageObj = $messageData['message'] ?? [];
        $content = '';
        if (isset($messageObj['conversation'])) {
            $content = $messageObj['conversation'];
        } elseif (isset($messageObj['extendedTextMessage']['text'])) {
            $content = $messageObj['extendedTextMessage']['text'];
        }

        // 2. Localizar ou criar conversa
        // Buscamos pelo JID efetivo, pelo remoto original ou pelo prefixo numérico
        $chat = WhatsappChat::where('tenant_id', $tenantId)
            ->where(function($q) use ($effectiveJid, $remoteJid, $phonePrefix) {
                $q->where('wa_id', $effectiveJid)
                  ->orWhere('wa_id', $remoteJid)
                  ->orWhere('contact_phone', $phonePrefix)
                  ->orWhere('wa_id', $phonePrefix);
            })
            ->first();

        if ($chat) {
            // Se o chat existia com @lid, mas agora temos o JID real (sender),
            // atualizamos o wa_id para garantir que futuros envios usem o ID correto.
            if ($chat->wa_id !== $effectiveJid) {
                Log::info("Updating chat wa_id from LID to standard JID", ['chat_id' => $chat->id, 'old' => $chat->wa_id, 'new' => $effectiveJid]);
                $chat->update(['wa_id' => $effectiveJid]);
            }
        } else {
            $chat = WhatsappChat::create([
                'tenant_id' => $tenantId,
                'wa_id' => $effectiveJid,
                'contact_name' => $messageData['pushName'] ?? 'Contato WhatsApp',
                'contact_phone' => $phonePrefix,
                'status' => 'open',
                'opt_in_at' => now(), 
            ]);
        }

        // 3. Update Chat States
        $chatUpdates = ['last_message_at' => now()];
        if ($isFromMe) {
            $chatUpdates['last_outbound_at'] = now();
        } else {
            $chatUpdates['last_inbound_at'] = now();
        }
        $chat->update($chatUpdates);

        // 4. Save Message (CRITICAL - DO THIS BEFORE AI)
        WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $messageId,
            'content' => $content ?: '[Mensagem de mídia]',
            'direction' => $isFromMe ? 'outbound' : 'inbound',
            'status' => 'delivered', 
        ]);

        // 5. Trigger AI auto-reply (ONLY for real inbound)
        // LID resolution happens INSIDE ProcessWhatsappAiResponse
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
