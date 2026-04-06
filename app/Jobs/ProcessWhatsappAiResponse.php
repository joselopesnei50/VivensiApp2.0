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
        $config = WhatsappConfig::find($this->configId);
        $chat = WhatsappChat::find($this->chatId);

        if (!$config || !$chat) {
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
        $systemPrompt .= "1. PERSONA: Assistente virtual da organização '{$orgName}'.\n";
        $systemPrompt .= "2. OBJETIVO: Atendimento gentil, profissional e empático. Você representa uma ONG.\n";
        $systemPrompt .= "3. CONTEXTO DA ORGANIZAÇÃO: {$training}\n";
        $systemPrompt .= "4. REGRAS CRÍTICAS:\n";
        $systemPrompt .= "   - Responda de forma CURTA e DIRETA.\n";
        $systemPrompt .= "   - NUNCA invente informações, valores ou compromissos que não estejam no contexto acima.\n";
        $systemPrompt .= "   - Se não souber a resposta ou se o assunto for sensível/complexo, peça gentilmente para o cliente aguardar um atendente humano.\n";
        $systemPrompt .= "   - NUNCA mencione que você é um robô ou IA, a menos que seja estritamente necessário para o fluxo.\n";
        $systemPrompt .= "   - Se o cliente solicitar falar com um humano, confirme que registrou a solicitação e peça para aguardar.\n";
        
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
                // Fallback para Evolution API (Legado/Em migração)
                $evo = new \App\Services\EvolutionApiService($tenant);
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

