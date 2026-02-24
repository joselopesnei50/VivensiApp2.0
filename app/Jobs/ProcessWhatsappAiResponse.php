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
            if (!$policy->canSend($config, $chat, false, $reason, $code, true)) {
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

            // Garante que o instanceName está correto mesmo quando o tenant não tem evolution_instance_name
            // O webhook armazena o instance_name na ProcessWhatsappWebhook, aqui buscamos da API
            $instanceName = $tenantModel->evolution_instance_name ?? '';
            $instanceToken = $tenantModel->evolution_instance_token ?? '';

            if (empty($instanceName)) {
                // Fallback: busca a instância pelo tenant via API
                $globalKey = config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY', ''));
                $baseUrl   = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', ''));
                try {
                    $instances = \Illuminate\Support\Facades\Http::timeout(5)
                        ->withHeaders(['apikey' => $globalKey])
                        ->get("{$baseUrl}/instance/fetchInstances")
                        ->json();
                    // Pega a primeira instância conectada
                    foreach ((array) $instances as $inst) {
                        if (($inst['connectionStatus'] ?? '') === 'open') {
                            $instanceName = $inst['name'] ?? '';
                            $instanceToken = $globalKey; // usa global key
                            break;
                        }
                    }
                } catch (\Throwable $ignored) {}
            }

            // Cria um objeto sintético com os dados corretos da instância
            $syntheticModel = (object) [
                'evolution_instance_name'  => $instanceName,
                'evolution_instance_token' => $instanceToken,
            ];

            $evo = new EvolutionApiService($syntheticModel);

            $toJid = $chat->wa_id;
            // Force standard JID if an LID is detected by querying the API
            if (str_contains($toJid, '@lid')) {
                $resolved = $evo->fetchProfile($toJid);
                if (!empty($resolved['jid'])) {
                    $toJid = $resolved['jid'];
                    // Update chat model so we don't have to resolve again
                    $chat->update(['wa_id' => $toJid]);
                } else {
                    // Fallback to number prefix if API fails, but it's risky
                    $numberPart = explode('@', $toJid)[0];
                    $toJid = $numberPart . '@s.whatsapp.net';
                }
            }

            if (!str_contains($toJid, '@')) {
                $toJid .= '@s.whatsapp.net';
            }

            Log::info('WhatsApp AI: enviando resposta', [
                'instance'  => $instanceName,
                'to'        => $toJid,
                'reply_len' => mb_strlen($replyText),
            ]);

            $res = $evo->sendMessage($toJid, $replyText, null, 2);

            if (isset($res['error'])) {
                Log::error('WhatsApp AI sendMessage failed', ['error' => $res, 'instance' => $instanceName, 'to' => $chat->wa_id]);
            }


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

