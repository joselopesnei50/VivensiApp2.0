<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappMessage;
use App\Services\DeepSeekService;
use App\Services\GeminiService;
use App\Services\Messaging\MetaCloudApiService;
use App\Services\WhatsappOutboundPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $configId;
    public int $chatId;
    public string $userMessage;

    /**
     * @param int $configId WhatsappConfig id
     * @param int $chatId WhatsappChat id
     * @param string $userMessage inbound message text
     */
    public function __construct(int $configId, int $chatId, string $userMessage)
    {
        $this->configId = $configId;
        $this->chatId = $chatId;
        $this->userMessage = $userMessage;
    }

    public function handle(): void
    {
        $config = WhatsappConfig::withoutGlobalScopes()->find($this->configId);
        $chat = WhatsappChat::find($this->chatId);

        if (!$config || !$chat) {
            Log::warning('AI Job: config ou chat não encontrado', ['config_id' => $this->configId, 'chat_id' => $this->chatId]);
            return;
        }

        // Safety checks
        if (!$config->ai_enabled) {
            return;
        }

        // If a human agent is assigned and chat is not open, don't auto-reply
        if ($chat->assigned_to && $chat->status !== 'open') {
            return;
        }

        $tenantId = $config->tenant_id;
        $tenant = Tenant::find($tenantId);
        $orgName = $tenant->name ?? ('Tenant #' . $tenantId);

        // "Treinamento" da IA vindo do banco
        $training = $config->ai_training ?? "Você é o Bruce AI, assistente virtual avançado da Vivensi.";

        $systemPrompt = "### INSTRUÇÕES DE SISTEMA (BRUCE AI) ###\n";
        $systemPrompt .= "1. PERSONA: Você é o *Bruce*, assistente digital humanizado da **ONG Vivensi**. Seu tom é acolhedor, empático e extremamente prestativo.\n";
        $systemPrompt .= "2. OBJETIVO: Ajudar o usuário com informações sobre a Vivensi e acolhê-lo. Você é a porta de entrada para um atendimento social de qualidade.\n";
        $systemPrompt .= "3. CONTEXTO ESPECÍFICO: {$training}\n";
        $systemPrompt .= "4. REGRAS DE OURO:\n";
        $systemPrompt .= "   - Respostas CURTAS (máximo 2 ou 3 parágrafos pequenos).\n";
        $systemPrompt .= "   - Use emojis de forma moderada para passar empatia (ex: 💙, 🙏).\n";
        $systemPrompt .= "   - Se o usuário pedir para falar com um humano, diga que vai avisar a equipe e peça para ele aguardar um momento.\n";
        $systemPrompt .= "   - NUNCA invente links ou telefones que não estejam no contexto.\n";
        $systemPrompt .= "   - Se não souber algo, não tente adivinhar. Peça para o usuário aguardar que um colega humano irá complementar a informação.\n";
        
        $prompt = "{$systemPrompt}\n---\nÚLTIMA MENSAGEM DO USUÁRIO: {$this->userMessage}";

        $replyText = '';
        $provider = $config->ai_provider ?? 'gemini';

        try {
            if ($provider === 'deepseek') {
                $ds = new DeepSeekService();
                $dsRes = $ds->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $this->userMessage]
                ]);
                $replyText = (string) ($dsRes['choices'][0]['message']['content'] ?? '');
                
                // Fallback to Gemini if DeepSeek fails
                if ($replyText === '') {
                    $ai = new GeminiService();
                    $aiResponse = $ai->callGemini([['text' => $prompt]]);
                    $replyText = (string) ($aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
                }
            } else {
                // Default to Gemini
                $ai = new GeminiService();
                $aiResponse = $ai->callGemini([['text' => $prompt]]);
                $replyText = (string) ($aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');

                // Fallback to DeepSeek if Gemini fails
                if ($replyText === '') {
                    $ds = new DeepSeekService();
                    $dsRes = $ds->chat([
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $this->userMessage]
                    ]);
                    $replyText = (string) ($dsRes['choices'][0]['message']['content'] ?? '');
                }
            }

            $replyText = trim($replyText);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI generation failed', [
                'tenant_id' => $tenantId,
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($replyText === '') {
            $replyText = "Entendi. Vou encaminhar sua solicitação para um especialista. Aguarde um momento.";
        }

        // Send via Meta Cloud API or Evolution API
        try {
            $policy = app(WhatsappOutboundPolicy::class);
            $reason = null;
            $code = null;
            if (!$policy->canSend($config, $chat, false, $reason, $code, true)) {
                Log::info('WhatsApp AI outbound blocked by policy', [
                    'tenant_id' => $tenantId,
                    'chat_id' => $chat->id,
                    'reason' => $reason,
                    'code' => $code,
                ]);
                WhatsappAuditLog::create([
                    'tenant_id' => (int) $tenantId,
                    'chat_id' => (int) $chat->id,
                    'actor_type' => 'ai',
                    'event' => 'outbound_blocked',
                    'details' => [
                        'code' => $code,
                        'reason' => $reason,
                        'content_len' => mb_strlen($replyText),
                        'content_hash' => hash('sha256', $replyText),
                    ],
                ]);
                return;
            }

            $res = null;
            $messageId = 'AI_' . uniqid();

            // Prioridade: Meta Cloud API (Oficial)
            if (!empty($config->meta_phone_number_id) && !empty($config->meta_access_token)) {
                $metaService = new MetaCloudApiService($config);
                $res = $metaService->sendTextMessage($chat->wa_id, $replyText);
                
                if (isset($res['messages'][0]['id'])) {
                    $messageId = $res['messages'][0]['id'];
                } elseif (isset($res['error'])) {
                    Log::error('Meta Cloud API AI Response failed', ['error' => $res, 'chat_id' => $chat->id]);
                    return;
                }
            } else {
                // Fallback para Evolution API
                $instance = \App\Models\WhatsappInstance::where('tenant_id', $tenantId)
                    ->where('status', 'open')
                    ->first();
                if (!$instance) {
                    Log::error('AI Response: nenhuma instância WhatsApp conectada', ['tenant_id' => $tenantId]);
                    return;
                }
                $evo = new \App\Services\EvolutionApiService($instance);
                $res = $evo->sendMessage($chat->wa_id, $replyText, null, 2);
                $messageId = $res['key']['id'] ?? ($res['messageId'] ?? $messageId);
            }

            WhatsappMessage::create([
                'chat_id' => $chat->id,
                'message_id' => $messageId,
                'content' => $replyText,
                'direction' => 'outbound',
                'type' => 'text',
            ]);

            $chat->update(['last_message_at' => now()]);
            $policy->recordSend($config, $chat);

            WhatsappAuditLog::create([
                'tenant_id' => (int) $tenantId,
                'chat_id' => (int) $chat->id,
                'actor_type' => 'ai',
                'event' => 'outbound_allowed',
                'details' => [
                    'provider_message_id' => $messageId,
                    'content_len' => mb_strlen($replyText),
                    'content_hash' => hash('sha256', $replyText),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI send failed', [
                'tenant_id' => $config->tenant_id,
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);

            try {
                WhatsappAuditLog::create([
                    'tenant_id' => (int) $config->tenant_id,
                    'chat_id' => (int) $chat->id,
                    'actor_type' => 'ai',
                    'event' => 'outbound_error',
                    'details' => ['error' => $e->getMessage()],
                ]);
            } catch (\Throwable $ignore) {
            }
        }
    }
}

