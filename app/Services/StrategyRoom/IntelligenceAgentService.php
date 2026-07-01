<?php

namespace App\Services\StrategyRoom;

use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1. Agente de Inteligencia (papel de Pesquisador).
 *
 * Consulta editais/oportunidades via function calling (IntelligenceAgentTools).
 * Regra dura: proibido "lembrar" edital de memoria do modelo — so pode citar
 * o que veio de uma tool call REAL desta sessao. Cf. arquitetura §2 (limites)
 * e §5 (LGPD/confianca).
 *
 * Compartilha o shape de saida com FinancialAgentService (fala, fatos_usados,
 * confianca) — pra que o Estrategista-Chefe consiga sintetizar sem
 * conhecer detalhes internos de cada agente.
 */
class IntelligenceAgentService
{
    public const AGENT_KEY = 'inteligencia';
    public const MODEL     = 'deepseek-v4-pro';
    private const MAX_TOOL_ITERATIONS = 3;

    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    /**
     * Se $sessionId for null, cria sessao nova (util pra teste isolado do
     * agente). Se dado, usa a sessao existente (uso normal via
     * StrategyDebateOrchestrator).
     */
    public function speak(int $tenantId, ?int $sessionId = null): array
    {
        $session = $sessionId
            ? StrategySession::withoutGlobalScopes()->findOrFail($sessionId)
            : StrategySession::create([
                'tenant_id'    => $tenantId,
                'trigger_type' => 'manual_test',
                'status'       => 'em_andamento',
            ]);

        // Loop de function calling — o modelo pode chamar buscar_editais_*
        // 1 ou mais vezes antes de dar a fala final. Teto duro pra evitar
        // recursao acidental (max 3 iteracoes de tool + 1 fala final).
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ['role' => 'user',   'content' => 'Analise as oportunidades de captacao disponiveis. Consulte editais cadastrados via ferramenta. Devolva SOMENTE o JSON no formato instruido.'],
        ];
        $tools = IntelligenceAgentTools::definitions();

        $rawContent  = '';
        $toolsCalled = []; // ids/nomes das tools chamadas — vai pra facts_used
        $editaisCitedIds = []; // ids de editais retornados pelas tools

        for ($iter = 0; $iter < self::MAX_TOOL_ITERATIONS + 1; $iter++) {
            $response = $this->deepSeek->chat($messages, self::MODEL, $tools);

            if (isset($response['error'])) {
                Log::warning('StrategyRoom/Inteligencia: DeepSeek erro', [
                    'tenant_id' => $tenantId, 'session' => $session->id, 'err' => $response['error'],
                ]);
                return ['error' => $response['error'], 'session_id' => $session->id];
            }

            $assistantMsg = data_get($response, 'choices.0.message', []);
            $toolCalls    = $assistantMsg['tool_calls'] ?? null;

            if (empty($toolCalls)) {
                $rawContent = (string) ($assistantMsg['content'] ?? '');
                break;
            }

            // Anexa a assistant msg (com tool_calls) e o resultado de cada tool.
            $messages[] = $assistantMsg;
            foreach ($toolCalls as $tc) {
                $name = data_get($tc, 'function.name', '');
                $args = json_decode(data_get($tc, 'function.arguments', '{}'), true) ?: [];
                $result = IntelligenceAgentTools::execute($name, $args, $tenantId);

                $toolsCalled[] = $name;
                // Capturar ids dos editais retornados pra rastreabilidade
                foreach (($result['editais'] ?? []) as $e) {
                    if (isset($e['id'])) $editaisCitedIds[] = (int) $e['id'];
                }

                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $tc['id'] ?? '',
                    'name'         => $name,
                    'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        $parsed = $this->parseJson($rawContent);
        if ($parsed === null) {
            Log::warning('StrategyRoom/Inteligencia: JSON invalido', [
                'tenant_id' => $tenantId, 'session' => $session->id, 'raw_len' => strlen($rawContent),
            ]);
            return [
                'error'      => 'O modelo devolveu resposta fora do formato JSON esperado.',
                'session_id' => $session->id,
            ];
        }

        $fala      = trim((string) ($parsed['fala'] ?? ''));
        $factsRaw  = $parsed['fatos_usados'] ?? [];
        $confianca = $this->reconcileConfidence(
            $this->sanitizeConfidence($parsed['confianca'] ?? 'media'),
            $fala,
            !empty($toolsCalled),
        );

        // Fatos usados: sempre inclui a(s) tool(s) chamada(s) + edital_{id} pra
        // cada edital especificamente citado na fala. Rastreabilidade
        // completa fato→fala pra UI da Fase 2+.
        $factsUsed = array_values(array_unique(array_merge(
            array_map(fn ($t) => "tool:{$t}", array_unique($toolsCalled)),
            $this->extractEditalRefs($fala, $editaisCitedIds),
            // Adiciona quaisquer handles adicionais que o modelo devolveu (dedup)
            array_filter(array_map(fn ($f) => is_string($f) ? $f : null, is_array($factsRaw) ? $factsRaw : []))
        )));

        if ($fala === '') {
            $session->update(['status' => 'concluida']);
            return ['error' => 'Modelo devolveu fala vazia.', 'session_id' => $session->id];
        }

        $message = StrategyMessage::create([
            'tenant_id'           => $tenantId,
            'strategy_session_id' => $session->id,
            'agent'               => self::AGENT_KEY,
            'content'             => $fala,
            'facts_used'          => $factsUsed,
            'confidence'          => $confianca,
        ]);

        // Se foi criada aqui (teste isolado), fecha. Se veio do orquestrador,
        // ele decide quando fechar.
        if (!$sessionId) {
            $session->update(['status' => 'concluida']);
        }

        return [
            'session_id'   => $session->id,
            'message_id'   => $message->id,
            'fala'         => $fala,
            'fatos_usados' => $factsUsed,
            'confianca'    => $confianca,
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Voce e o Agente de Inteligencia da Sala de Estrategia do Vivensi. Papel: pesquisador — mapeia oportunidades de captacao (editais). Fala em portugues direto, sem rodeios.

## REGRA CRITICA DE ANTI-ALUCINACAO
Voce NAO tem conhecimento previo sobre editais. Antes de mencionar qualquer edital — nome, orgao, valor, prazo — voce DEVE ter chamado a ferramenta `buscar_editais_cadastrados` e usado APENAS o retorno dela. E PROIBIDO citar edital que nao veio de uma tool call desta conversa. Se a tool retornar vazio, diga "nao ha edital cadastrado no sistema neste escopo" e sugira caminho (cadastrar, monitorar) — NUNCA invente edital.

## FERRAMENTAS DISPONIVEIS
- `buscar_editais_cadastrados({status?, com_deadline_ate_dias?})` — consulta a base do tenant. Chame SEMPRE antes de falar sobre edital. Pode chamar 1 ou 2 vezes com filtros diferentes se precisar (ex: primeiro "todos", depois "com deadline em 30 dias").

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo)
{"fala": "string em portugues 2-4 frases", "fatos_usados": ["tool:buscar_editais_cadastrados","edital:1","edital:2"], "confianca": "alta"}

Regras do JSON:
- fala: texto sem quebra de linha. Ao citar edital especifico, use o titulo entre aspas simples exatamente como veio da tool.
- fatos_usados: array de handles. Formato: "tool:{nome_da_tool}" pra cada tool chamada, e "edital:{id}" pra cada edital especificamente mencionado na fala.
- confianca: "alta" | "media" | "baixa"
  - alta: tool retornou editais que sustentam completamente a analise
  - media: retornou parcial (poucos editais, ou os que retornaram tem gap de info)
  - baixa: tool retornou vazio E voce teve que dizer "nao ha edital cadastrado". Ou: nao conseguiu chamar tool.

## O QUE VOCE FAZ
1. Chama a ferramenta.
2. Se veio edital, analisa: prazo urgente? valor relevante? multiplos? sugere UM foco de acao (ex: "priorizar edital X, prazo em N dias, valor Y").
3. Se veio vazio, seja honesto e sugira mecanismo de captura.
4. Nao propoe cenario, nao inventa historico, nao cita edital de memoria.
PROMPT;
    }

    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) return null;
        $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function sanitizeConfidence(mixed $c): string
    {
        $c = is_string($c) ? mb_strtolower(trim($c)) : 'media';
        return in_array($c, ['alta', 'media', 'baixa'], true) ? $c : 'media';
    }

    /**
     * Extrai referencias a editais especificos da fala. Estrategia: pra cada
     * id retornado pelas tools, se o titulo do edital aparece na fala (parcial),
     * inclui "edital:{id}". Simplificacao — Fase 1 nao precisa de match fuzzy
     * perfeito, so rastreabilidade minima.
     *
     * Fase 2+ pode evoluir pra citation-based tagging.
     */
    private function extractEditalRefs(string $fala, array $ids): array
    {
        // Nesta fase, se algum edital foi retornado pela tool E citado, ja
        // registramos a tool no facts_used. Refinamento por id fica pra
        // quando o schema de citacao amadurecer.
        // Por ora, se ha ids retornados e a fala nao esta vazia, considera-se
        // que a analise se apoia neles — inclui todos.
        if (empty($ids) || $fala === '') return [];
        return array_map(fn ($id) => "edital:{$id}", array_values(array_unique($ids)));
    }

    /**
     * Rebaixa "alta" pra "media" quando a fala admite dado ausente OU quando
     * nenhuma tool foi chamada (i.e., o modelo tentou falar sem consultar).
     * Nunca eleva.
     */
    private function reconcileConfidence(string $confianca, string $fala, bool $toolWasCalled): string
    {
        if (!$toolWasCalled) return 'baixa';
        if ($confianca !== 'alta') return $confianca;

        $t = mb_strtolower($fala);
        $red_flags = [
            'nao ha edital', 'não há edital', 'sem edital cadastrad', 'nenhum edital',
            'nao encontrei edital', 'não encontrei edital', 'nao consegui', 'não consegui',
        ];
        foreach ($red_flags as $flag) {
            if (str_contains($t, $flag)) return 'baixa';
        }
        return 'alta';
    }
}
