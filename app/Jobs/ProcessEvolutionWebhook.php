<?php

namespace App\Jobs;

use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessEvolutionWebhook
 * 
 * Processa os eventos da Evolution API (nossa infra própria):
 * - MESSAGES_UPSERT   → cria/atualiza chat e mensagem no banco
 * - CONNECTION_UPDATE → atualiza status da instância
 * - Opt-out keywords  → blacklist automática
 */
class ProcessEvolutionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries   = 3;
    public int    $timeout = 30;
    public array  $backoff = [10, 30];

    public function __construct(
        protected int    $instanceId,
        protected string $event,
        protected array  $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $instance = WhatsappInstance::find($this->instanceId);
        if (!$instance) {
            Log::warning("ProcessEvolutionWebhook: Instance #{$this->instanceId} não encontrada.");
            return;
        }

        match ($this->event) {
            'MESSAGES_UPSERT', 'messages.upsert' => $this->handleInboundMessage($instance),
            'CONNECTION_UPDATE', 'connection.update', 'QRCODE_UPDATED', 'qrcode.updated' => $this->handleConnectionUpdate($instance),
            default => Log::debug("ProcessEvolutionWebhook: Evento '{$this->event}' ignorado."),
        };
    }

    // ── Handlers ────────────────────────────────────────────────────────────

    private function handleInboundMessage(WhatsappInstance $instance): void
    {
        $tenantId = $instance->tenant_id;

        // Navega pela estrutura do payload da Evolution API v2
        $messageData = $this->payload['data'] ?? $this->payload;
        $key         = $messageData['key'] ?? [];
        $messageId   = $key['id'] ?? null;
        $fromMe      = (bool) ($key['fromMe'] ?? false);

        // Ignorar mensagens enviadas pela própria instância
        if ($fromMe || !$messageId) return;

        // Idempotência: ignorar re-entregas
        if (WhatsappMessage::where('message_id', $messageId)->exists()) return;

        $remoteJid = $key['remoteJid'] ?? '';
        // Extrair número limpo do JID (ex: 5511999999999@s.whatsapp.net → 5511999999999)
        $phone = preg_replace('/@.*/', '', $remoteJid);
        if (empty($phone)) return;

        // Extrair conteúdo da mensagem (texto simples)
        $msg     = $messageData['message'] ?? [];
        $content = $msg['conversation'] ?? ($msg['extendedTextMessage']['text'] ?? '');
        $senderName = $messageData['pushName'] ?? 'WhatsApp';

        // 1. Verificar blacklist
        if (WhatsappBlacklist::where('tenant_id', $tenantId)->where('phone', $phone)->exists()) {
            Log::info("ProcessEvolutionWebhook: Mensagem de {$phone} ignorada (blacklist).");
            return;
        }

        // 2. Localizar ou criar conversa
        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $phone],
            [
                'contact_name'  => $senderName,
                'contact_phone' => $phone,
                'status'        => 'open',
                'opt_in_at'     => now(),
            ]
        );

        $chat->update([
            'last_message_at' => now(),
            'last_inbound_at' => now(),
        ]);

        // Garantir opt-in implícito (mensagem inbound = consentimento para resposta)
        if (!$chat->opt_in_at) {
            $chat->update(['opt_in_at' => now()]);
        }

        // 3. Verificar palavras de opt-out (STOP compliance)
        $normalized   = mb_strtolower(trim($content));
        $stopKeywords = ['stop', 'parar', 'pare', 'sair', 'cancelar', 'cancele', 'descadastrar', 'remover', 'não quero', 'nao quero'];

        foreach ($stopKeywords as $kw) {
            if ($kw !== '' && str_contains($normalized, $kw)) {
                $chat->update([
                    'opt_out_at'     => now(),
                    'blocked_at'     => now(),
                    'blocked_reason' => "STOP keyword via Evolution: {$kw}",
                    'status'         => 'closed',
                ]);

                WhatsappBlacklist::firstOrCreate(
                    ['tenant_id' => $tenantId, 'phone' => $phone],
                    ['reason' => "Opt-out via keyword: {$kw}"]
                );

                WhatsappAuditLog::create([
                    'tenant_id'  => $tenantId,
                    'chat_id'    => $chat->id,
                    'actor_type' => 'webhook_evolution',
                    'event'      => 'compliance_action',
                    'details'    => ['action' => 'opt_out', 'reason' => 'STOP keyword', 'keyword' => $kw],
                ]);
                return;
            }
        }

        // 4. Salvar mensagem inbound
        WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
            'content'    => $content,
            'direction'  => 'inbound',
            'type'       => 'text',
        ]);

        WhatsappAuditLog::create([
            'tenant_id'  => $tenantId,
            'chat_id'    => $chat->id,
            'actor_type' => 'webhook_evolution',
            'event'      => 'inbound_message',
            'details'    => ['message_id' => $messageId, 'content_len' => mb_strlen($content)],
        ]);

        // 5. Disparar resposta da IA se habilitada
        $config = \App\Models\WhatsappConfig::where('tenant_id', $tenantId)->first();
        if ($config?->ai_enabled && !$chat->opt_out_at && !$chat->blocked_at) {
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                ->onQueue('whatsapp');
        }
    }

    private function handleConnectionUpdate(WhatsappInstance $instance): void
    {
        $data   = $this->payload['data'] ?? $this->payload;
        $state  = $data['state']    ?? ($data['connection'] ?? null);
        $qrcode = $data['qrcode']   ?? null;
        $ownerJid = $data['instance']['ownerJid'] ?? null;

        $updateData = [];

        if ($state) {
            $statusMap = [
                'open'       => 'open',
                'connecting' => 'connecting',
                'close'      => 'close',
            ];
            $updateData['status'] = $statusMap[$state] ?? 'close';
        }

        if ($ownerJid) {
            $updateData['owner_jid']    = $ownerJid;
            // Extrai o número do JID: 5511999999999@s.whatsapp.net
            $updateData['phone_number'] = preg_replace('/@.*/', '', $ownerJid);
        }

        $cacheKey = 'evo_qr_' . $instance->instance_name;
        if ($qrcode && isset($qrcode['base64'])) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $qrcode['base64'], 120);
        } elseif (is_string($qrcode) && str_starts_with($qrcode, 'data:image')) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $qrcode, 120);
        }

        if (!empty($updateData)) {
            $instance->update($updateData);
        }

        Log::info("ProcessEvolutionWebhook: Instance [{$instance->instance_name}] status={$state}");
    }
}
