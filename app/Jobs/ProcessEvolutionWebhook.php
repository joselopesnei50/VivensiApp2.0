<?php

namespace App\Jobs;

use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use App\Services\ContatoOptInService;
use App\Services\EvolutionApiService;
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
    public int    $timeout = 60;
    public array  $backoff = [10, 30, 60];

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

        // Se a mensagem partiu de nós (celular ou painel), desativamos o bot para esta conversa
        if ($fromMe) {
            $remoteJid = $key['remoteJid'] ?? '';
            $phone = preg_replace('/@.*/', '', $remoteJid);
            if ($phone) {
                WhatsappChat::where('tenant_id', $tenantId)
                    ->where('wa_id', $phone)
                    ->update(['is_bot_active' => false]);
            }
            return;
        }

        if (!$messageId) return;

        // Idempotência: ignorar re-entregas
        if (WhatsappMessage::where('message_id', $messageId)->exists()) return;

        $remoteJid = $key['remoteJid'] ?? '';
        $chatLid   = $messageData['chatLid'] ?? ($key['chatLid'] ?? null);

        // Se remoteJid for @lid (identificador de privacidade do WhatsApp),
        // usa o chatLid como identificador estável. Caso contrário extrai o número.
        if (str_ends_with($remoteJid, '@lid')) {
            // Usa chatLid se disponível, senão usa o próprio @lid como fallback
            $phone = $chatLid ?? $remoteJid;
        } else {
            // Extrai número limpo do JID (ex: 5511999999999@s.whatsapp.net → 5511999999999)
            $phone = preg_replace('/@.*/', '', $remoteJid);
        }

        if (empty($phone)) return;

        // Extrair conteúdo da mensagem
        $msg      = $messageData['message'] ?? [];
        $content  = $msg['conversation'] ?? ($msg['extendedTextMessage']['text'] ?? '');
        $isAudio  = isset($msg['audioMessage']);
        
        // Se for áudio, tentamos obter a transcrição ou o base64 (se disponível no webhook)
        if ($isAudio && empty($content)) {
            $content = "[Mensagem de Áudio]";
        }
        
        $senderName = $messageData['pushName'] ?? 'WhatsApp';

        // 0. Fluxo de opt-in explícito — intercede antes de qualquer outro processamento
        if (!$isAudio && $content) {
            $respostaOptIn = app(ContatoOptInService::class)->handle($phone, $content, $tenantId);
            if ($respostaOptIn !== null) {
                try {
                    (new EvolutionApiService($instance))->sendMessage($phone, $respostaOptIn);
                } catch (\Throwable $e) {
                    Log::warning("ProcessEvolutionWebhook: falha ao enviar resposta opt-in para {$phone}: " . $e->getMessage());
                }
                return;
            }
        }

        // 1. Verificar blacklist
        if (WhatsappBlacklist::where('tenant_id', $tenantId)->where('phone', $phone)->exists()) {
            Log::info("ProcessEvolutionWebhook: Mensagem de {$phone} ignorada (blacklist).");
            return;
        }

        // 2. Localizar ou criar conversa — updateOrCreate evita duplicate key em concorrência
        $chat = WhatsappChat::updateOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $phone],
            [
                'contact_name'   => $senderName,
                'contact_phone'  => $phone,
                'status'         => 'open',
                'opt_in_at'      => now(),
                'last_message_at' => now(),
                'last_inbound_at' => now(),
            ]
        );

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

                WhatsappBlacklist::updateOrCreate(
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

        // 5. Automações por palavra-chave (tempo real)
        $keywordFired = false;
        if (!$isAudio && $content && !$chat->opt_out_at && !$chat->blocked_at) {
            $keywordFired = $this->processKeywordAutomations($tenantId, $chat, $content, $instance);
        }

        // 6. Disparar resposta da IA se habilitada e nenhuma automação de keyword disparou
        $config = \App\Models\WhatsappConfig::where('tenant_id', $tenantId)->first();
        $isBotAllowed = $chat->is_bot_active && is_null($chat->assigned_to);

        if (!$keywordFired && $config?->ai_enabled && $isBotAllowed && !$chat->opt_out_at && !$chat->blocked_at) {
            $base64Audio = $msg['audioMessage']['base64'] ?? null;
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content, $base64Audio);
        }
    }

    private function processKeywordAutomations(int $tenantId, \App\Models\WhatsappChat $chat, string $content, WhatsappInstance $instance): bool
    {
        $automations = \App\Models\WhatsappAutomation::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('trigger', 'keyword_received')
            ->where('is_active', true)
            ->get();

        $fired = false;

        foreach ($automations as $automation) {
            if (!$automation->isWithinSendWindow()) continue;
            if (!$automation->keyword) continue;

            $keyword = mb_strtolower(trim($automation->keyword));
            if (!str_contains(mb_strtolower($content), $keyword)) continue;

            // Anti-spam
            $alreadySent = $automation->send_once
                ? \App\Models\WhatsappAutomationLog::where('automation_id', $automation->id)
                    ->where('contact_phone', $chat->wa_id)
                    ->where('status', 'sent')
                    ->exists()
                : \App\Models\WhatsappAutomationLog::where('automation_id', $automation->id)
                    ->where('contact_phone', $chat->wa_id)
                    ->where('sent_at', '>=', now()->subHours(24))
                    ->exists();

            if ($alreadySent) continue;

            $orgName = \App\Models\Tenant::find($tenantId)?->name ?? 'nossa organização';
            $message = $automation->renderMessage($chat->contact_name ?? 'Olá', $orgName);

            try {
                (new EvolutionApiService($instance))->sendMessage($chat->wa_id, $message, null, rand(1, 3));

                \App\Models\WhatsappAutomationLog::create([
                    'automation_id' => $automation->id,
                    'tenant_id'     => $tenantId,
                    'contact_phone' => $chat->wa_id,
                    'contact_name'  => $chat->contact_name,
                    'message_sent'  => $message,
                    'status'        => 'sent',
                    'sent_at'       => now(),
                ]);

                $fired = true;
                Log::info("Keyword automation [{$automation->id}] disparada para {$chat->wa_id} (keyword: {$keyword})");
            } catch (\Throwable $e) {
                \App\Models\WhatsappAutomationLog::create([
                    'automation_id' => $automation->id,
                    'tenant_id'     => $tenantId,
                    'contact_phone' => $chat->wa_id,
                    'contact_name'  => $chat->contact_name,
                    'message_sent'  => $message,
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at'       => now(),
                ]);
                Log::error("Keyword automation [{$automation->id}] falhou para {$chat->wa_id}: " . $e->getMessage());
            }
        }

        return $fired;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessEvolutionWebhook falhou definitivamente", [
            'instance_id' => $this->instanceId,
            'event'       => $this->event,
            'error'       => $exception->getMessage(),
        ]);
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
            $updateData['owner_jid'] = $ownerJid;
            // Extrai o número do JID — ignora se for @lid (identificador de privacidade)
            if (!str_ends_with($ownerJid, '@lid')) {
                $updateData['phone_number'] = preg_replace('/@.*/', '', $ownerJid);
            }
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
