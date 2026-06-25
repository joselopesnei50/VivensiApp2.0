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

    public int   $tries   = 2;
    public int   $timeout = 90;
    public array $backoff  = [30, 60];

    public int $configId;
    public int $chatId;
    public string $userMessage;
    public ?string $base64Audio;

    /**
     * @param int $configId WhatsappConfig id
     * @param int $chatId WhatsappChat id
     * @param string $userMessage inbound message text
     * @param string|null $base64Audio optional audio data in base64
     */
    public function __construct(int $configId, int $chatId, string $userMessage, ?string $base64Audio = null)
    {
        $this->configId = $configId;
        $this->chatId = $chatId;
        $this->userMessage = $userMessage;
        $this->base64Audio = $base64Audio;
        $this->onQueue('ai');
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

        // Não responder automaticamente se: bot desativado, agente atribuído ou chat encerrado
        if (!$chat->is_bot_active) {
            return;
        }

        if ($chat->assigned_to !== null) {
            return;
        }

        if (in_array($chat->status, ['closed', 'resolved'])) {
            return;
        }

        $tenantId = $config->tenant_id;
        $tenant = Tenant::find($tenantId);
        $orgName = $tenant->name ?? ('Tenant #' . $tenantId);

        // Treinamento personalizado do tenant — é o protagonista do prompt
        $training = $config->ai_training ?? "Você é um assistente virtual prestativo da organização {$orgName}. Responda de forma acolhedora e objetiva.";

        $systemPrompt  = $training . "\n\n";
        $systemPrompt .= "### REGRAS GERAIS ###\n";
        $systemPrompt .= "- IDIOMA: Identifique o idioma do usuário e responda SEMPRE no mesmo idioma (Português, Inglês, Espanhol, etc).\n";
        $systemPrompt .= "- Respostas CURTAS (máximo 2 ou 3 parágrafos pequenos).\n";
        $systemPrompt .= "- Use emojis de forma moderada para passar empatia.\n";
        $systemPrompt .= "- Se o usuário pedir para falar com um humano, diga que vai avisar a equipe e peça para ele aguardar.\n";
        $systemPrompt .= "- NUNCA invente links ou telefones que não estejam no treinamento acima.\n";
        $systemPrompt .= "- Se não souber algo, não invente. Peça para o usuário aguardar que a equipe irá complementar.\n";

        // MEMÓRIA CONVERSACIONAL — sem isso o LLM trata cada mensagem como
        // primeira interação e fica repetindo "Olá, sou o assistente..." em
        // todo turno. Pega o histórico textual e injeta no prompt.
        $historico = $this->buildHistoryMessages($chat, $this->userMessage);
        $historicoTextoParaGemini = $this->historyAsPlainText($historico);

        // Prompt para Gemini text-only (concat histórico + mensagem atual).
        // Gemini multimodal de áudio NÃO recebe histórico — áudio é one-shot
        // e o histórico bagunçaria o entendimento da gravação.
        $prompt = "{$systemPrompt}\n"
            . ($historicoTextoParaGemini !== '' ? "\n### HISTÓRICO DA CONVERSA ###\n{$historicoTextoParaGemini}\n" : "")
            . "\n---\nMENSAGEM DO USUÁRIO: {$this->userMessage}";

        $replyText = '';
        // Default to DeepSeek as primary engine
        $provider = $config->ai_provider ?? 'deepseek';

        try {
            // Se tivermos áudio, o Gemini é OBRIGATÓRIO (DeepSeek não processa áudio nativo)
            if ($this->base64Audio) {
                $ai = new GeminiService();
                $aiResponse = $ai->callGemini([
                    ['text' => $systemPrompt . "\nO usuário enviou um áudio. Entenda o que ele disse e responda no mesmo idioma dele."],
                    [
                        'inline_data' => [
                            'mime_type' => 'audio/ogg', // Padrão WhatsApp (Opus)
                            'data' => $this->base64Audio
                        ]
                    ]
                ]);
                $replyText = (string) ($aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
            } else {
                // Tentativa Principal: DeepSeek (ou o provedor explicitamente configurado)
                if ($provider === 'deepseek') {
                    $ds = new DeepSeekService();
                    $dsRes = $ds->chat(array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $historico,
                        [['role' => 'user', 'content' => $this->userMessage]]
                    ));
                    $replyText = (string) ($dsRes['choices'][0]['message']['content'] ?? '');
                } else {
                    // Se estiver explicitamente como Gemini
                    $ai = new GeminiService();
                    $aiResponse = $ai->callGemini([['text' => $prompt]]);
                    $replyText = (string) ($aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
                }

                // Fallback: Se o provedor principal falhar, tenta o secundário
                if (empty(trim($replyText))) {
                    if ($provider === 'deepseek') {
                        // DeepSeek falhou, tenta Gemini como bote salva-vidas
                        $ai = new GeminiService();
                        $aiResponse = $ai->callGemini([['text' => $prompt]]);
                        $replyText = (string) ($aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
                    } else {
                        // Gemini falhou, tenta DeepSeek como bote salva-vidas
                        $ds = new DeepSeekService();
                        $dsRes = $ds->chat(array_merge(
                            [['role' => 'system', 'content' => $systemPrompt]],
                            $historico,
                            [['role' => 'user', 'content' => $this->userMessage]]
                        ));
                        $replyText = (string) ($dsRes['choices'][0]['message']['content'] ?? '');
                    }
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
            // Ambos provedores falharam: desativa bot e sinaliza para atendimento humano
            $chat->update(['is_bot_active' => false]);
            WhatsappAuditLog::create([
                'tenant_id'  => (int) $tenantId,
                'chat_id'    => (int) $chat->id,
                'actor_type' => 'ai',
                'event'      => 'ai_escalated_to_human',
                'details'    => ['reason' => 'Todos os provedores de IA falharam'],
            ]);
            Log::error('ProcessWhatsappAiResponse: todos os provedores falharam, chat escalado para humano', [
                'tenant_id' => $tenantId,
                'chat_id'   => $chat->id,
            ]);
            $replyText = "Olá! No momento estou com dificuldades técnicas. Um de nossos atendentes irá responder em breve. Pedimos desculpas! 🙏";
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

    // ── Memória conversacional (sem isso o bot vira robô amnésico) ──────────

    public const HISTORY_TURNS_LIMIT = 20;       // ~10 trocas (user+assistant)
    public const HISTORY_MSG_MAX_CHARS = 500;    // trunca mensagem isolada gigante
    public const HISTORY_TOTAL_MAX_CHARS = 6000; // ceil aproximado do contexto histórico

    /**
     * Monta o histórico da conversa em formato OpenAI-like (lista de
     * ['role' => 'user'|'assistant', 'content' => ...]) pra alimentar o LLM.
     *
     * Garante:
     *  - ordem cronológica ascendente (a mais antiga primeiro);
     *  - exclui a mensagem atual do usuário (evita duplicar quando o caller
     *    adiciona ela depois do histórico);
     *  - filtra conteúdo vazio/marcador (ex: '[áudio]' sem transcrição) — o
     *    LLM se confunde com placeholders soltos no histórico;
     *  - trunca cada mensagem isolada e limita o tamanho total.
     *
     * @return array<int,array{role:string,content:string}>
     */
    private function buildHistoryMessages(WhatsappChat $chat, string $currentUserMessage): array
    {
        $atual = trim($currentUserMessage);

        $msgs = WhatsappMessage::where('chat_id', $chat->id)
            ->orderByDesc('id')
            ->take(self::HISTORY_TURNS_LIMIT + 5) // folga pra absorver descartes
            ->get(['content', 'direction', 'created_at']);

        $items = [];
        $totalChars = 0;
        foreach ($msgs->reverse() as $m) {
            $content = trim((string) $m->content);
            if ($content === '') {
                continue;
            }
            // Pula marcadores de mídia sem conteúdo legível.
            if (in_array($content, ['[áudio]', '[imagem]', '[vídeo]', '[sticker]', '[localização]', '[contato]', '[mensagem não suportada]'], true)) {
                continue;
            }
            // Última mensagem inbound = atual; não duplicar (o caller adiciona).
            if ($m->direction === 'inbound' && $content === $atual) {
                continue;
            }
            if (mb_strlen($content) > self::HISTORY_MSG_MAX_CHARS) {
                $content = mb_substr($content, 0, self::HISTORY_MSG_MAX_CHARS) . '…';
            }
            $items[] = [
                'role' => $m->direction === 'outbound' ? 'assistant' : 'user',
                'content' => $content,
            ];
            $totalChars += mb_strlen($content);
        }

        // Defesa em profundidade: corta as MAIS ANTIGAS se passar do teto.
        while ($totalChars > self::HISTORY_TOTAL_MAX_CHARS && !empty($items)) {
            $removida = array_shift($items);
            $totalChars -= mb_strlen($removida['content']);
        }
        // E limita o nº de turnos absoluto.
        if (count($items) > self::HISTORY_TURNS_LIMIT) {
            $items = array_slice($items, -self::HISTORY_TURNS_LIMIT);
        }

        return array_values($items);
    }

    /**
     * Converte o histórico estruturado em uma string única pra prompts
     * que não suportam multi-turn nativamente (Gemini text-only no fluxo atual).
     *
     * @param array<int,array{role:string,content:string}> $history
     */
    private function historyAsPlainText(array $history): string
    {
        if (empty($history)) {
            return '';
        }
        $lines = [];
        foreach ($history as $m) {
            $who = $m['role'] === 'assistant' ? 'Você (assistente)' : 'Usuário';
            $lines[] = "{$who}: {$m['content']}";
        }
        return implode("\n", $lines);
    }
}

