<?php

namespace App\Services;

use App\Exceptions\WhatsAppPolicyException;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use App\Services\Messaging\MetaCloudApiService;
use App\Services\WhatsappOutboundPolicy;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    // ── Text / Template ───────────────────────────────────────────────────────

    /**
     * Send a text or template message, routing to Meta Cloud API when configured,
     * falling back to Evolution API.
     *
     * Returns ['message_id' => string, 'provider' => 'meta'|'evolution']
     * Throws \RuntimeException on hard failure (blocked by policy or provider error).
     */
    public function sendMessage(
        WhatsappChat   $chat,
        WhatsappConfig $config,
        string         $content,
        bool           $isTemplate = false,
        array          $templateData = [],
        ?int           $actorUserId = null
    ): array {
        $policy = app(WhatsappOutboundPolicy::class);
        $reason = null;
        $code   = null;

        if (!$policy->canSend($config, $chat, $isTemplate, $reason, $code)) {
            $this->auditLog($chat, $actorUserId, 'outbound_blocked', [
                'code'         => $code,
                'reason'       => $reason,
                'is_template'  => $isTemplate,
                'content_hash' => hash('sha256', $content),
                'content_len'  => mb_strlen($content),
            ]);
            throw new WhatsAppPolicyException($reason ?: 'Envio não permitido.', $code ?? 'BLOCKED', 422);
        }

        $messageId = 'MANUAL_' . uniqid();
        $provider  = 'evolution';

        if (!empty($config->meta_phone_number_id) && !empty($config->meta_access_token)) {
            $provider = 'meta';
            $meta     = new MetaCloudApiService($config);

            if ($isTemplate) {
                $res = $meta->sendTemplateMessage(
                    $chat->wa_id,
                    $templateData['template_name'] ?? 'hello_world',
                    $templateData['language_code'] ?? 'pt_BR',
                    $templateData['vars'] ?? []
                );
                $content = '[Template: ' . ($templateData['template_name'] ?? '') . ']';
            } else {
                $res = $meta->sendTextMessage($chat->wa_id, $content);
            }

            if (isset($res['error'])) {
                throw new \RuntimeException('Erro na Meta API: ' . ($res['error']['message'] ?? 'Desconhecido'), 500);
            }
            $messageId = $res['messages'][0]['id'] ?? $messageId;
        } else {
            $instance = $this->requireInstance($chat->tenant_id);
            $evo      = new EvolutionApiService($instance);
            $res      = $evo->sendMessage($chat->wa_id, $content, null, 0);

            if (isset($res['error'])) {
                Log::error('WhatsAppService: Evolution sendMessage falhou', ['chat' => $chat->wa_id, 'error' => $res]);
                throw new \RuntimeException('Falha ao enviar: ' . ($res['error'] ?? 'Erro desconhecido'), 500);
            }
            $messageId = $res['key']['id'] ?? ($res['messageId'] ?? $messageId);
        }

        $policy->recordSend($config, $chat);

        $this->auditLog($chat, $actorUserId, 'outbound_allowed', [
            'is_template'         => $isTemplate,
            'provider'            => $provider,
            'provider_message_id' => $messageId,
            'content_len'         => mb_strlen($content),
            'content_hash'        => hash('sha256', $content),
        ]);

        $msg = WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
            'content'    => $content,
            'direction'  => 'outbound',
            'type'       => 'text',
        ]);

        $chat->update(['last_message_at' => now()]);

        return ['message_id' => $messageId, 'provider' => $provider, 'message' => $msg];
    }

    // ── Media ─────────────────────────────────────────────────────────────────

    /**
     * Send an image via Evolution API (base64 encoded).
     * Returns ['message_id' => string, 'message' => WhatsappMessage]
     */
    public function sendMedia(
        WhatsappChat   $chat,
        WhatsappConfig $config,
        string         $base64,
        string         $mimetype,
        string         $caption = '',
        ?int           $actorUserId = null
    ): array {
        $policy = app(WhatsappOutboundPolicy::class);
        $reason = null;
        $code   = null;

        if (!$policy->canSend($config, $chat, false, $reason, $code)) {
            throw new WhatsAppPolicyException($reason ?: 'Envio não permitido.', $code ?? 'BLOCKED', 422);
        }

        $instance = $this->requireInstance($chat->tenant_id);
        $evo      = new EvolutionApiService($instance);

        Log::info("WhatsAppService: enviando mídia para {$chat->wa_id}", ['mime' => $mimetype]);
        $res = $evo->sendMedia($chat->wa_id, $base64, $caption, $mimetype);

        if (isset($res['error']) || empty($res)) {
            Log::error('WhatsAppService: Evolution sendMedia falhou', ['chat' => $chat->wa_id, 'res' => $res]);
            throw new \RuntimeException($res['message'] ?? $res['error'] ?? 'Erro na Evolution API', 500);
        }

        $messageId = $res['key']['id'] ?? ('MEDIA_' . uniqid());

        $msg = WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
            'content'    => $caption ? "[imagem] {$caption}" : '[imagem]',
            'direction'  => 'outbound',
            'type'       => 'image',
        ]);

        $chat->update(['last_message_at' => now()]);
        $policy->recordSend($config, $chat);

        $this->auditLog($chat, $actorUserId, 'outbound_media', [
            'type'                => 'image',
            'mimetype'            => $mimetype,
            'provider_message_id' => $messageId,
        ]);

        return ['message_id' => $messageId, 'message' => $msg];
    }

    // ── Audio ─────────────────────────────────────────────────────────────────

    /**
     * Send a PTT audio message (OGG/Opus) via Evolution API.
     * Returns ['message_id' => string, 'message' => WhatsappMessage]
     */
    public function sendAudio(
        WhatsappChat   $chat,
        WhatsappConfig $config,
        string         $base64,
        ?int           $actorUserId = null
    ): array {
        $policy = app(WhatsappOutboundPolicy::class);
        $reason = null;
        $code   = null;

        if (!$policy->canSend($config, $chat, false, $reason, $code)) {
            throw new WhatsAppPolicyException($reason ?: 'Envio não permitido.', $code ?? 'BLOCKED', 422);
        }

        $instance = $this->requireInstance($chat->tenant_id);
        $evo      = new EvolutionApiService($instance);

        sleep(2); // anti-ban delay (WhatsApp requires human-like pacing for PTT)
        $res = $evo->sendAudio($chat->wa_id, $base64);

        if (isset($res['error']) || empty($res)) {
            Log::error('WhatsAppService: Evolution sendAudio falhou', ['chat' => $chat->wa_id, 'res' => $res]);
            throw new \RuntimeException($res['message'] ?? $res['error'] ?? 'Erro na Evolution API', 500);
        }

        $messageId = $res['key']['id'] ?? ('AUDIO_' . uniqid());

        $msg = WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
            'content'    => '🎙️ [Áudio enviado]',
            'direction'  => 'outbound',
            'type'       => 'audio',
        ]);

        $chat->update(['last_message_at' => now()]);
        $policy->recordSend($config, $chat);

        $this->auditLog($chat, $actorUserId, 'outbound_audio', [
            'provider_message_id' => $messageId,
        ]);

        return ['message_id' => $messageId, 'message' => $msg];
    }

    // ── Chat ──────────────────────────────────────────────────────────────────

    /**
     * Finds or creates a chat for the given phone number in the tenant.
     * Registers opt-in if $consent is true.
     */
    public function startChat(
        int    $tenantId,
        string $phone,
        string $name = '',
        bool   $consent = false
    ): WhatsappChat {
        $phone = preg_replace('/\D+/', '', $phone);
        if ($phone === '') {
            throw new \InvalidArgumentException('Telefone inválido');
        }

        $name = trim($name) ?: 'Contato WhatsApp';

        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $phone],
            [
                'contact_name'   => $name,
                'contact_phone'  => $phone,
                'status'         => 'open',
                'last_message_at'=> now(),
            ]
        );

        if (empty($chat->contact_name) || $chat->contact_name === 'Cliente WhatsApp') {
            $chat->contact_name = $name;
        }
        if (empty($chat->contact_phone)) {
            $chat->contact_phone = $phone;
        }

        if ($consent && !$chat->opt_in_at) {
            $chat->opt_in_at = now();
        }

        $chat->last_message_at = now();
        $chat->save();

        return $chat;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function requireInstance(int $tenantId): WhatsappInstance
    {
        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            throw new \RuntimeException('Nenhuma instância WhatsApp conectada. Configure em Configurações.', 422);
        }

        return $instance;
    }

    private function auditLog(WhatsappChat $chat, ?int $actorUserId, string $event, array $details): void
    {
        try {
            WhatsappAuditLog::create([
                'tenant_id'     => $chat->tenant_id,
                'chat_id'       => $chat->id,
                'actor_user_id' => $actorUserId,
                'actor_type'    => $actorUserId ? 'user' : 'system',
                'event'         => $event,
                'details'       => $details,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService: auditLog failed — ' . $e->getMessage());
        }
    }
}
