<?php

namespace App\Services\Messaging;

use App\Models\Lead;
use App\Models\LeadTimelineItem;
use App\Models\TenantOperationalProfile;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\DeepSeekService;
use App\Services\GeminiService;
use App\Services\LeadService;
use App\Services\PerfilOperacionalService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * LeadQualificationService — Fase 4 (item 2.3 do roadmap).
 *
 * Classifica uma conversa do OmniChannel usando LLM e devolve um dicionário
 * estruturado pronto pra virar card no Kanban. Roteia o provedor por risco
 * LGPD: perfil 'campanha_eleitoral' usa Gemini (região BR), demais usam
 * DeepSeek (mais barato).
 *
 * Schema de saída:
 *   qualification: 'frio' | 'morno' | 'quente'
 *   intent       : 'interesse' | 'suporte' | 'reclamacao' | 'outro'
 *   summary      : string curta (≤300 chars)
 *   next_action  : sugestão do que fazer a seguir
 *   confidence   : 0..1
 *   provider     : 'deepseek' | 'gemini' (qual modelo decidiu)
 *
 * Quando o LLM falha ou devolve algo inválido, o Service retorna um
 * fallback marcado (qualification=null + error) — o controller decide se
 * mostra erro ao usuário ou cria card só com summary do histórico.
 */
class LeadQualificationService
{
    public const MAX_MESSAGES_PER_QUALIFY = 30;
    public const ALLOWED_QUALIFICATIONS   = ['frio', 'morno', 'quente'];
    public const ALLOWED_INTENTS          = ['interesse', 'suporte', 'reclamacao', 'outro'];

    public function __construct(
        private DeepSeekService $deepSeek,
        private GeminiService $gemini,
        private PerfilOperacionalService $perfil,
        private LeadService $leads,
    ) {
    }

    /**
     * @return array{
     *   qualification: ?string,
     *   intent: ?string,
     *   summary: string,
     *   next_action: ?string,
     *   confidence: float,
     *   provider: string,
     *   error: ?string,
     * }
     */
    public function qualifyChat(WhatsappChat $chat): array
    {
        Log::info('LeadQualification: invocado', [
            'chat_id'   => $chat->id,
            'tenant_id' => $chat->tenant_id,
        ]);

        $tenant = $chat->tenant;
        if ($tenant === null) {
            throw new RuntimeException('Chat sem tenant — qualificação cancelada.');
        }

        $messages = $this->collectMessages($chat);
        Log::info('LeadQualification: mensagens coletadas', [
            'chat_id' => $chat->id,
            'count'   => count($messages),
        ]);
        if ($messages === []) {
            return $this->fallback('Conversa vazia — nada para classificar.', 'deepseek');
        }

        $categoria   = $this->perfil->getCategoria($tenant);
        $provider    = $this->chooseProvider($categoria);
        $systemPrompt = $this->buildSystemPrompt($categoria, $tenant->name ?: 'a organização');
        $userPrompt   = $this->buildUserPrompt($chat, $messages);

        try {
            $raw = $provider === 'gemini'
                ? $this->callGemini($systemPrompt, $userPrompt)
                : $this->callDeepSeek($systemPrompt, $userPrompt);
        } catch (\Throwable $e) {
            Log::warning('LeadQualification: provedor falhou', [
                'provider' => $provider,
                'tenant_id'=> $tenant->id,
                'chat_id'  => $chat->id,
                'error'    => $e->getMessage(),
            ]);
            return $this->fallback("Falha no provedor de IA ({$provider}).", $provider);
        }

        $parsed = $this->parseLlmJson($raw);
        if ($parsed === null) {
            Log::warning('LeadQualification: JSON inválido', [
                'provider' => $provider,
                'tenant_id'=> $tenant->id,
                'chat_id'  => $chat->id,
                'raw_preview' => mb_substr($raw, 0, 300),
            ]);
            return $this->fallback('A IA não respondeu em formato esperado.', $provider);
        }

        $result = $this->normalize($parsed, $provider);

        // P1.7 — anexa sugestão do Bruce à timeline do lead vinculado, se houver.
        $this->attachToLeadTimeline($chat, $result);

        return $result;
    }

    // ── Provedor ───────────────────────────────────────────────────────────

    public function chooseProvider(string $categoria): string
    {
        // Categoria eleitoral envolve opinião política (dado sensível LGPD)
        // — rota para Gemini (região BR está no roadmap; aqui é só o gateway).
        if ($categoria === TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL) {
            return 'gemini';
        }
        return 'deepseek';
    }

    // ── Prompts ────────────────────────────────────────────────────────────

    public function buildSystemPrompt(string $categoria, string $tenantName): string
    {
        $perfilCtx = $this->perfil->bruceContextForCategoria($categoria, null, $tenantName);

        return <<<PROMPT
{$perfilCtx}

Você é o qualificador de leads do Vivensi para essa organização. Sua tarefa
ÚNICA é analisar o histórico de uma conversa de WhatsApp e devolver um
diagnóstico estruturado pra alimentar o Kanban do gestor.

Saída obrigatória: APENAS um objeto JSON, sem prefácio, sem markdown, sem
comentários. Schema:

{
  "qualification": "frio" | "morno" | "quente",
  "intent": "interesse" | "suporte" | "reclamacao" | "outro",
  "summary": "resumo curto (até 300 caracteres) do que o contato quer",
  "next_action": "sugestão objetiva de próxima ação do atendente",
  "confidence": 0.0 a 1.0
}

Regras:
- 'frio' = pouco engajamento ou sondagem distante.
- 'morno' = interesse demonstrado mas sem decisão.
- 'quente' = quer agir agora (comprar, agendar, confirmar).
- Não invente informação que não esteja na conversa.
- Não exponha dado pessoal sensível no summary ou next_action.
- Se a conversa for ambígua, prefira 'frio' com confidence baixa.
PROMPT;
    }

    /**
     * @param array<int,array{direction:string,content:string,created_at:?string}> $messages
     */
    public function buildUserPrompt(WhatsappChat $chat, array $messages): string
    {
        $lines = ['Conversa com ' . ($chat->contact_name ?: 'contato sem nome') . ':'];
        foreach ($messages as $m) {
            $who = $m['direction'] === 'inbound' ? 'CONTATO' : 'ATENDENTE';
            $lines[] = "[{$who}] " . $m['content'];
        }
        $lines[] = '';
        $lines[] = 'Devolva o JSON do diagnóstico.';

        return implode("\n", $lines);
    }

    // ── Provider calls ─────────────────────────────────────────────────────

    private function callDeepSeek(string $system, string $user): string
    {
        $res = $this->deepSeek->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ]);

        if (isset($res['error'])) {
            throw new RuntimeException('DeepSeek error: ' . (is_string($res['error']) ? $res['error'] : json_encode($res['error'])));
        }

        $content = (string) data_get($res, 'choices.0.message.content', '');
        if ($content === '') {
            throw new RuntimeException('DeepSeek devolveu resposta vazia.');
        }
        return $content;
    }

    private function callGemini(string $system, string $user): string
    {
        // GeminiService::callGemini recebe array de parts — concatena system+user.
        $res = $this->gemini->callGemini([
            ['text' => $system . "\n\n---\n\n" . $user],
        ]);

        $content = (string) data_get($res, 'candidates.0.content.parts.0.text', '');
        if ($content === '') {
            throw new RuntimeException('Gemini devolveu resposta vazia.');
        }
        return $content;
    }

    // ── Parser de JSON tolerante (LLMs costumam embrulhar em ```json) ──────

    public function parseLlmJson(string $raw): ?array
    {
        $trimmed = trim($raw);
        // Remove markdown code block se vier.
        $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: tenta encontrar o primeiro {...} balanceado.
        if (preg_match('/\{(?:[^{}]|(?R))*\}/', $trimmed, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    private function normalize(array $parsed, string $provider): array
    {
        $qualification = isset($parsed['qualification']) && in_array($parsed['qualification'], self::ALLOWED_QUALIFICATIONS, true)
            ? $parsed['qualification']
            : null;
        $intent = isset($parsed['intent']) && in_array($parsed['intent'], self::ALLOWED_INTENTS, true)
            ? $parsed['intent']
            : null;
        $summary = isset($parsed['summary']) && is_string($parsed['summary'])
            ? mb_substr(trim($parsed['summary']), 0, 300)
            : '';
        $nextAction = isset($parsed['next_action']) && is_string($parsed['next_action'])
            ? mb_substr(trim($parsed['next_action']), 0, 300)
            : null;
        $confidence = isset($parsed['confidence']) && is_numeric($parsed['confidence'])
            ? max(0.0, min(1.0, (float) $parsed['confidence']))
            : 0.5;

        return [
            'qualification' => $qualification,
            'intent'        => $intent,
            'summary'       => $summary,
            'next_action'   => $nextAction,
            'confidence'    => $confidence,
            'provider'      => $provider,
            'error'         => null,
        ];
    }

    private function fallback(string $error, string $provider): array
    {
        Log::warning('LeadQualification: fallback', [
            'provider' => $provider,
            'error'    => $error,
        ]);

        return [
            'qualification' => null,
            'intent'        => null,
            'summary'       => '',
            'next_action'   => null,
            'confidence'    => 0.0,
            'provider'      => $provider,
            'error'         => $error,
        ];
    }

    /**
     * @return array<int,array{direction:string,content:string,created_at:?string}>
     */
    private function collectMessages(WhatsappChat $chat): array
    {
        return WhatsappMessage::where('chat_id', $chat->id)
            ->orderBy('id', 'desc')
            ->limit(self::MAX_MESSAGES_PER_QUALIFY)
            ->get(['direction', 'content', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'direction'  => $m->direction,
                'content'    => mb_substr((string) $m->content, 0, 400),
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * P1.7 — grava AI_SUGGESTION (resumo da IA) e NEXT_ACTION (próxima
     * ação proposta) na timeline do lead vinculado ao chat. Falha silenciosa:
     * timeline é histórico cosmético, não pode quebrar a qualificação.
     */
    private function attachToLeadTimeline(WhatsappChat $chat, array $result): void
    {
        try {
            if (!empty($result['error'])) {
                return;
            }
            $phoneInput = $chat->contact_phone ?: $chat->wa_id;
            if (empty($phoneInput)) {
                return;
            }
            $normalized = $this->leads->normalizePhone((string) $phoneInput);
            if ($normalized === null) {
                return;
            }

            $lead = Lead::where('tenant_id', $chat->tenant_id)
                ->where('phone_normalized', $normalized)
                ->first();
            if ($lead === null) {
                return;
            }

            $summary = trim((string) ($result['summary'] ?? ''));
            if ($summary !== '') {
                $this->leads->addTimelineItem(
                    $lead,
                    LeadTimelineItem::TYPE_AI_SUGGESTION,
                    $summary,
                    null,
                    [
                        'qualification' => $result['qualification'] ?? null,
                        'intent'        => $result['intent'] ?? null,
                        'confidence'    => $result['confidence'] ?? null,
                        'provider'      => $result['provider'] ?? null,
                        'chat_id'       => $chat->id,
                    ]
                );
            }

            $nextAction = trim((string) ($result['next_action'] ?? ''));
            if ($nextAction !== '') {
                $this->leads->addTimelineItem(
                    $lead,
                    LeadTimelineItem::TYPE_NEXT_ACTION,
                    $nextAction,
                    null,
                    [
                        'qualification' => $result['qualification'] ?? null,
                        'provider'      => $result['provider'] ?? null,
                        'chat_id'       => $chat->id,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('LeadQualification: falha ao gravar timeline do lead', [
                'tenant_id' => $chat->tenant_id,
                'chat_id'   => $chat->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Renderiza a qualificação como descrição de card no Kanban (Markdown
     * leve). Usado pelo controller depois de chamar qualifyChat.
     */
    public function renderForKanbanDescription(array $result): string
    {
        $linhas = [];
        if (!empty($result['summary'])) {
            $linhas[] = $result['summary'];
            $linhas[] = '';
        }
        if (!empty($result['qualification'])) {
            $linhas[] = '• Classificação: ' . strtoupper($result['qualification'])
                . ' (confiança ' . round(($result['confidence'] ?? 0.0) * 100) . '%)';
        }
        if (!empty($result['intent'])) {
            $linhas[] = '• Intenção: ' . ucfirst($result['intent']);
        }
        if (!empty($result['next_action'])) {
            $linhas[] = '• Próxima ação: ' . $result['next_action'];
        }
        $linhas[] = '';
        $linhas[] = '— Gerado por IA (' . ($result['provider'] ?? 'desconhecido') . ')';

        return trim(implode("\n", $linhas));
    }
}
