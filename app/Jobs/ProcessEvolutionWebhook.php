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
use App\Services\Messaging\WhatsappFormEngine;
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

        // Extrair conteúdo da mensagem cobrindo todos os tipos do Baileys.
        // Regra Fase 0: histórico nunca pode persistir vazio. Mídia sem legenda
        // vira marcador legível ("[imagem]", "[áudio]", etc).
        $msg        = $messageData['message'] ?? [];
        $extracted  = $this->extractMessageContent($msg);
        $content    = $extracted['content'];
        $type       = $extracted['type'];
        $mediaPath  = $extracted['media_path'];
        $mediaCaption = $extracted['media_caption'];
        $userText   = $extracted['user_text']; // null quando só mídia sem texto digitado
        $isAudio    = ($type === 'audio');

        $senderName = $messageData['pushName'] ?? 'WhatsApp';

        // 0. Fluxo de opt-in explícito — só processa quando há texto real do usuário
        if ($userText !== null) {
            $respostaOptIn = app(ContatoOptInService::class)->handle($phone, $userText, $tenantId);
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

        // 3. Verificar palavras de opt-out (STOP compliance) — só vale para texto real
        $normalized   = $userText !== null ? mb_strtolower(trim($userText)) : '';
        $stopKeywords = ['stop', 'parar', 'pare', 'sair', 'cancelar', 'cancele', 'descadastrar', 'remover', 'não quero', 'nao quero'];

        foreach ($stopKeywords as $kw) {
            if ($normalized !== '' && $kw !== '' && str_contains($normalized, $kw)) {
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
            'chat_id'       => $chat->id,
            'message_id'    => $messageId,
            'content'       => $content,
            'direction'     => 'inbound',
            'type'          => $type,
            'media_path'    => $mediaPath,
            'media_caption' => $mediaCaption,
        ]);

        WhatsappAuditLog::create([
            'tenant_id'  => $tenantId,
            'chat_id'    => $chat->id,
            'actor_type' => 'webhook_evolution',
            'event'      => 'inbound_message',
            'details'    => [
                'message_id'  => $messageId,
                'type'        => $type,
                'content_len' => mb_strlen($content),
                'has_media'   => $mediaPath !== null,
            ],
        ]);

        // 4.5 Formulário conversacional (Fase 4 — item 2.5). Sessão ativa
        // intercepta o fluxo normal: nem keyword nem IA disparam enquanto
        // a máquina de estados está rodando. Exige texto real do usuário —
        // mídia sem legenda no meio de um form aborta com aviso amigável.
        if ($userText !== null) {
            $formEngine = app(WhatsappFormEngine::class);
            $session    = $formEngine->activeSessionFor($chat);
            if ($session !== null) {
                $this->advanceFormSession($formEngine, $session, $userText, $chat, $instance, $msg);
                return;
            }
        }

        // 5. Automações por palavra-chave (tempo real) — exige texto real
        $keywordFired = false;
        if ($userText !== null && !$chat->opt_out_at && !$chat->blocked_at) {
            $keywordFired = $this->processKeywordAutomations($tenantId, $chat, $userText, $instance);
        }

        // 6. Disparar resposta da IA. Texto puro/caption usam $userText.
        //    Áudio segue para STT no próprio job da IA. Mídia sem texto e tipos
        //    não suportados ficam só no histórico (sem disparar IA).
        $config = \App\Models\WhatsappConfig::where('tenant_id', $tenantId)->first();
        $isBotAllowed = $chat->is_bot_active && is_null($chat->assigned_to);
        $shouldFireAi = !$keywordFired
            && $config?->ai_enabled
            && $isBotAllowed
            && !$chat->opt_out_at
            && !$chat->blocked_at
            && ($userText !== null || $isAudio);

        if ($shouldFireAi) {
            $base64Audio  = $isAudio ? ($msg['audioMessage']['base64'] ?? null) : null;
            $contentForAi = $userText ?? '';
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $contentForAi, $base64Audio);
        }
    }

    /**
     * Dispatcher do formulário conversacional. Resolve shortcut numérico
     * (1/2/3 → label da opção), processa a resposta no FormEngine e manda
     * a próxima pergunta ou mensagem de conclusão via Evolution.
     */
    private function advanceFormSession(
        WhatsappFormEngine $formEngine,
        \App\Models\WhatsappFormSession $session,
        string $userText,
        \App\Models\WhatsappChat $chat,
        WhatsappInstance $instance,
        array $rawMsg
    ): void {
        $question = $session->currentQuestion;
        if ($question === null) {
            $formEngine->complete($session);
            return;
        }

        $resolvedText = $formEngine->resolveButtonShortcut($question, $userText);
        $result       = $formEngine->processInbound($session, $resolvedText, ['raw' => $rawMsg['conversation'] ?? null]);

        $evo = new EvolutionApiService($instance);

        try {
            if ($result['error']) {
                // Resposta inválida — manda mensagem amigável + repete pergunta.
                $evo->sendMessage($chat->wa_id, $result['error'] . "\n\n" . $formEngine->renderQuestionAsText($question), null, rand(1, 2));
                return;
            }

            if ($result['next_question'] === null) {
                // Sessão completa.
                $form     = \App\Models\WhatsappForm::find($session->form_id);
                $formName = $form?->name ?: 'questionário';
                $evo->sendMessage($chat->wa_id, "Obrigado! Suas respostas para *{$formName}* foram registradas.", null, rand(1, 2));

                WhatsappAuditLog::create([
                    'tenant_id'  => $session->tenant_id,
                    'chat_id'    => $chat->id,
                    'actor_type' => 'system',
                    'event'      => 'form_session_completed',
                    'details'    => [
                        'session_id' => $session->id,
                        'form_id'    => $session->form_id,
                    ],
                ]);
                return;
            }

            // Próxima pergunta.
            $evo->sendMessage(
                $chat->wa_id,
                $formEngine->renderQuestionAsText($result['next_question']),
                null,
                rand(1, 2)
            );
        } catch (\Throwable $e) {
            Log::warning('ProcessEvolutionWebhook: falha ao avançar sessão de formulário', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
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

    /**
     * Extrai o corpo legível e o tipo da mensagem do payload Baileys.
     * Garante content não-vazio (regra Fase 0: thread nunca em branco no reload).
     * user_text é null quando não há texto digitado pelo usuário (mídia sem legenda),
     * sinalizando que opt-in/keywords/IA-texto não devem ser acionados.
     *
     * @return array{content:string,type:string,media_path:?string,media_caption:?string,user_text:?string}
     */
    private function extractMessageContent(array $msg): array
    {
        if (isset($msg['conversation']) && $msg['conversation'] !== '') {
            return [
                'content'       => $msg['conversation'],
                'type'          => 'text',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => $msg['conversation'],
            ];
        }

        if (isset($msg['extendedTextMessage']['text']) && $msg['extendedTextMessage']['text'] !== '') {
            return [
                'content'       => $msg['extendedTextMessage']['text'],
                'type'          => 'text',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => $msg['extendedTextMessage']['text'],
            ];
        }

        if (isset($msg['imageMessage'])) {
            $caption = $msg['imageMessage']['caption'] ?? '';
            return [
                'content'       => $caption !== '' ? $caption : '[imagem]',
                'type'          => 'image',
                'media_path'    => $msg['imageMessage']['url'] ?? null,
                'media_caption' => $caption !== '' ? $caption : null,
                'user_text'     => $caption !== '' ? $caption : null,
            ];
        }

        if (isset($msg['videoMessage'])) {
            $caption = $msg['videoMessage']['caption'] ?? '';
            return [
                'content'       => $caption !== '' ? $caption : '[vídeo]',
                'type'          => 'video',
                'media_path'    => $msg['videoMessage']['url'] ?? null,
                'media_caption' => $caption !== '' ? $caption : null,
                'user_text'     => $caption !== '' ? $caption : null,
            ];
        }

        if (isset($msg['audioMessage'])) {
            return [
                'content'       => '[Mensagem de Áudio]',
                'type'          => 'audio',
                'media_path'    => $msg['audioMessage']['url'] ?? null,
                'media_caption' => null,
                'user_text'     => null,
            ];
        }

        if (isset($msg['documentMessage'])) {
            $fileName = $msg['documentMessage']['fileName']
                ?? ($msg['documentMessage']['title'] ?? 'documento');
            $caption  = $msg['documentMessage']['caption'] ?? '';
            return [
                'content'       => "[documento: {$fileName}]" . ($caption !== '' ? " — {$caption}" : ''),
                'type'          => 'document',
                'media_path'    => $msg['documentMessage']['url'] ?? null,
                'media_caption' => $caption !== '' ? $caption : null,
                'user_text'     => $caption !== '' ? $caption : null,
            ];
        }

        if (isset($msg['stickerMessage'])) {
            return [
                'content'       => '[sticker]',
                'type'          => 'sticker',
                'media_path'    => $msg['stickerMessage']['url'] ?? null,
                'media_caption' => null,
                'user_text'     => null,
            ];
        }

        // Localização: minimização LGPD — não persistimos coordenadas no histórico.
        if (isset($msg['locationMessage']) || isset($msg['liveLocationMessage'])) {
            return [
                'content'       => '[localização compartilhada]',
                'type'          => 'location',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => null,
            ];
        }

        // Contato compartilhado: PII de terceiros — só marcador, sem vCard.
        if (isset($msg['contactMessage']) || isset($msg['contactsArrayMessage'])) {
            return [
                'content'       => '[contato compartilhado]',
                'type'          => 'contact',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => null,
            ];
        }

        if (isset($msg['buttonsResponseMessage'])) {
            $text = $msg['buttonsResponseMessage']['selectedDisplayText']
                ?? ($msg['buttonsResponseMessage']['selectedButtonId'] ?? '[resposta de botão]');
            return [
                'content'       => $text,
                'type'          => 'button_reply',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => $text,
            ];
        }

        if (isset($msg['listResponseMessage'])) {
            $text = $msg['listResponseMessage']['title']
                ?? ($msg['listResponseMessage']['singleSelectReply']['selectedRowId'] ?? '[resposta de lista]');
            return [
                'content'       => $text,
                'type'          => 'list_reply',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => $text,
            ];
        }

        if (isset($msg['interactiveResponseMessage'])) {
            $text = $msg['interactiveResponseMessage']['body']['text']
                ?? '[resposta interativa]';
            return [
                'content'       => $text,
                'type'          => 'interactive_reply',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => $text,
            ];
        }

        // Reação (emoji em mensagem) — sinaliza no histórico mas não dispara IA.
        if (isset($msg['reactionMessage'])) {
            $emoji = $msg['reactionMessage']['text'] ?? '';
            return [
                'content'       => $emoji !== '' ? "[reação: {$emoji}]" : '[reação]',
                'type'          => 'reaction',
                'media_path'    => null,
                'media_caption' => null,
                'user_text'     => null,
            ];
        }

        // Fallback: tipo desconhecido / novo. Loga o tipo para mapeamento futuro
        // (sem o payload — pode conter PII).
        $unknownKey = is_array($msg) && !empty($msg) ? (array_key_first($msg) ?? 'null') : 'null';
        Log::info("ProcessEvolutionWebhook: tipo de mensagem Baileys não mapeado: {$unknownKey}");

        return [
            'content'       => '[mensagem não suportada]',
            'type'          => 'unsupported',
            'media_path'    => null,
            'media_caption' => null,
            'user_text'     => null,
        ];
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
