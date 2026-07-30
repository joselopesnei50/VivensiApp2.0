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
use App\Services\WhatsApp\WhatsAppSenderFactory;
use App\Services\WhatsappOutboundPolicy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // Prioridade: WhatsappInstance do tenant (modelo novo, resolve provider
        // por coluna). Legado por WhatsappConfig só é usado se não existe
        // instance no tenant — evita que instance cloud_api caia em Evolution
        // pelo simples fato de config.meta_* estar vazio.
        //
        // Se o tenant tem Cloud API habilitado no plano, preferimos Cloud
        // sobre Evolution — evita que uma Evolution ativa em paralelo
        // (por exemplo, remanescente de teste antes da migração) roube o
        // envio e mande com token errado. Sem esse orderBy, era pega a mais
        // antiga na tabela — comportamento imprevisível pós-migração.
        $instance = WhatsappInstance::where('tenant_id', $chat->tenant_id)
            ->where('status', 'open')
            ->orderByRaw("CASE WHEN provider = ? THEN 0 ELSE 1 END", [WhatsappInstance::PROVIDER_CLOUD_API])
            ->orderByDesc('id')
            ->first();

        if ($instance) {
            // Defesa em profundidade: se a instance é Cloud API mas o plano
            // do tenant NÃO libera essa capability (admin desmarcou depois),
            // bloqueia envio com mensagem clara. Evolution API segue liberado
            // pra todos os planos (é gratuito).
            if ($instance->isCloudApi() && !$chat->tenant?->hasCapability('whatsapp_cloud')) {
                throw new \RuntimeException(
                    'O envio via WhatsApp Cloud API não está incluído no seu plano atual. Atualize sua assinatura ou contate o suporte.',
                    402
                );
            }

            // Pré-pago: se ativado pra este tenant, bloqueia envio se saldo
            // não cobre o pior cenário (categoria marketing BR). Só Cloud API
            // gera cobrança — Evolution continua livre.
            if ($instance->isCloudApi()) {
                $creditService = app(\App\Services\WhatsAppService\WhatsappCreditService::class);
                if ($creditService->isPrepaidEnabled($chat->tenant_id)
                    && !$creditService->hasBalanceForSend($chat->tenant_id, 'BR')
                ) {
                    throw new \RuntimeException(
                        'Saldo WhatsApp insuficiente. Acesse Consumo → Recarregar para adicionar créditos e voltar a enviar mensagens.',
                        402
                    );
                }
            }

            $sender   = WhatsAppSenderFactory::forInstance($instance);
            $provider = $sender->providerName();

            if ($isTemplate) {
                $res = $sender->sendTemplate(
                    $chat->wa_id,
                    $templateData['template_name'] ?? 'hello_world',
                    $templateData['language_code'] ?? 'pt_BR',
                    $templateData['vars'] ?? []
                );
                $content = '[Template: ' . ($templateData['template_name'] ?? '') . ']';
            } else {
                $res = $sender->sendText($chat->wa_id, $content);
            }

            if (empty($res['ok'])) {
                Log::error('WhatsAppService: sender falhou', [
                    'provider' => $provider,
                    'chat'     => $chat->wa_id,
                    'error'    => $res['error'] ?? 'unknown',
                ]);
                throw new \RuntimeException('Falha ao enviar: ' . ($res['error'] ?? 'Erro desconhecido'), 500);
            }
            $messageId = $res['provider_message_id'] ?? $messageId;
        } elseif (!empty($config->meta_phone_number_id) && !empty($config->meta_access_token)) {
            // Fallback legado: tenant sem WhatsappInstance mas com config Meta
            // preenchida (padrão antigo — nível tenant, não nível instance).
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
            throw new \RuntimeException('Nenhuma instância WhatsApp conectada para este tenant.', 422);
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

        // Persiste a imagem em storage/app/public pra a UI conseguir renderizar
        // o preview mesmo depois. Sem isso o outbound so tinha content='[imagem]'
        // e desaparecia da tela.
        $mediaPath = $this->storeOutboundMedia($chat->tenant_id, $base64, $mimetype);

        $msg = WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $messageId,
            'content'    => $caption ? "[imagem] {$caption}" : '[imagem]',
            'direction'  => 'outbound',
            'type'       => 'image',
            'media_path' => $mediaPath,
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

    // Salva base64 outbound em disco publico, retorna URL relativa acessivel via asset().
    // Sem isso o painel nao consegue renderizar preview de imagens que ele mesmo enviou.
    private function storeOutboundMedia(int $tenantId, string $base64, string $mimetype): ?string
    {
        try {
            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            $ext = $mimeMap[$mimetype] ?? 'bin';

            // Remove prefixo data URL se presente.
            $clean  = preg_replace('/^data:[^;]+;base64,/', '', $base64);
            $binary = base64_decode($clean, true);
            if ($binary === false || $binary === '') {
                return null;
            }

            $path = 'whatsapp-outbound/' . $tenantId . '/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($path, $binary);

            return Storage::url($path); // /storage/whatsapp-outbound/{tenant}/{uuid}.ext
        } catch (\Throwable $e) {
            Log::warning('storeOutboundMedia falhou', ['err' => $e->getMessage()]);
            return null;
        }
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
        $normalized = EvolutionApiService::normalizeBrazilianPhone($phone);
        if ($normalized === null) {
            throw new \InvalidArgumentException('Telefone inválido');
        }

        $name = trim($name) ?: 'Contato WhatsApp';

        // Resolve JID correto (9º dígito) quando há instância ativa disponível
        $waId = $normalized;
        try {
            $instance = $this->requireInstance($tenantId);
            $evo      = new EvolutionApiService($instance);
            $jidMap   = $evo->checkWhatsappNumbers([$normalized]);
            if (isset($jidMap[$normalized])) {
                $waId = $jidMap[$normalized];
            }
        } catch (\Throwable) {
            // Sem instância ativa ou falha na verificação — usa normalizado
        }

        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $waId],
            [
                'contact_name'   => $name,
                'contact_phone'  => $normalized,
                'status'         => 'open',
                'last_message_at'=> now(),
            ]
        );

        if (empty($chat->contact_name) || $chat->contact_name === 'Cliente WhatsApp') {
            $chat->contact_name = $name;
        }
        if (empty($chat->contact_phone)) {
            $chat->contact_phone = $normalized;
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

    /**
     * Tenta enviar por todas as instâncias ativas do tenant até obter sucesso.
     * Garante fallover automático se a instância principal falhar.
     */
    public function sendWithFallover(int $tenantId, string $waId, string $content): array
    {
        $instances = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->orderBy('created_at')
            ->get();

        if ($instances->isEmpty()) {
            throw new \RuntimeException('Nenhuma instância WhatsApp conectada.', 422);
        }

        $lastError = null;
        foreach ($instances as $instance) {
            try {
                $evo = new EvolutionApiService($instance);
                $res = $evo->sendMessage($waId, $content, null, 0);

                if (!isset($res['error'])) {
                    return ['result' => $res, 'instance_id' => $instance->id];
                }

                $lastError = $res['error'] ?? 'Erro desconhecido';
                Log::warning("WhatsApp fallover: instância {$instance->id} falhou, tentando próxima.", ['error' => $lastError]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("WhatsApp fallover: instância {$instance->id} exception.", ['error' => $lastError]);
            }
        }

        throw new \RuntimeException("Todas as instâncias falharam. Último erro: {$lastError}", 500);
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
