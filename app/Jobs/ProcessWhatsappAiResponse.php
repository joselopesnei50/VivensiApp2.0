<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappMessage;
use App\Services\DeepSeekService;
use App\Services\GeminiService;
use App\Services\EvolutionApiService;
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

        $tenant = Tenant::find($config->tenant_id);
        $orgName = $tenant->name ?? ('Tenant #' . $config->tenant_id);

        $training = $config->ai_training
            ?? "Você é o Bruce AI, assistente virtual avançado da Vivensi. Seja gentil, profissional e altamente eficiente.";

        $prompt = "Contexto da Organização ({$orgName}): {$training}\n";
        $prompt .= "Instrução: Responda ao cliente de forma curta e objetiva. Se não souber a resposta, peça para ele aguardar um atendente humano.\n";
        $prompt .= "Usuário: {$this->userMessage}";

        $replyText = '';

        try {
            $ai = new GeminiService();
            $aiResponse = $ai->callGemini([['text' => $prompt]]);

            if (isset($aiResponse['candidates'][0]['content']['parts'][0]['text'])) {
                $replyText = (string) $aiResponse['candidates'][0]['content']['parts'][0]['text'];
            } else {
                $ds = new DeepSeekService();
                $dsRes = $ds->chat([['role' => 'user', 'content' => $prompt]]);
                $replyText = (string) ($dsRes['choices'][0]['message']['content'] ?? '');
            }

            $replyText = trim($replyText);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI generation failed', [
                'tenant_id' => $config->tenant_id,
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($replyText === '') {
            $replyText = "Entendi. Vou encaminhar sua solicitação para um especialista. Aguarde um momento.";
        }

        // Send via Z-API and store locally
        try {
            $policy = app(WhatsappOutboundPolicy::class);
            $reason = null;
            $code = null;
            if (!$policy->canSend($config, $chat, false, $reason, $code)) {
                Log::info('WhatsApp AI outbound blocked by policy', [
                    'tenant_id' => $config->tenant_id,
                    'chat_id' => $chat->id,
                    'reason' => $reason,
                    'code' => $code,
                ]);
                WhatsappAuditLog::create([
                    'tenant_id' => (int) $config->tenant_id,
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

            $tenantModel = Tenant::find($config->tenant_id);
            $evo = new EvolutionApiService($tenantModel);
            $res = $evo->sendMessage($chat->wa_id, $replyText, null, 2);

            $messageId = (string) ($res['key']['id'] ?? ($res['messageId'] ?? ''));
            if ($messageId === '') {
                $messageId = 'AI_' . uniqid();
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
                'tenant_id' => (int) $config->tenant_id,
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

