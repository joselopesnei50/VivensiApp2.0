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
    public const AGENT_KEY     = 'inteligencia';
    public const DEFAULT_MODEL = 'deepseek-v4-flash';
    private const MAX_TOOL_ITERATIONS = 3;

    private function model(): string
    {
        return (string) config('strategy_room.models.inteligencia', self::DEFAULT_MODEL);
    }

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
        $entityIds   = ['edital' => [], 'projeto' => []]; // ids retornados pelas tools

        for ($iter = 0; $iter < self::MAX_TOOL_ITERATIONS + 1; $iter++) {
            $response = $this->deepSeek->chat($messages, $this->model(), $tools);

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
                // Rastreabilidade: capturar ids dos entidades retornadas pelas tools
                foreach (($result['editais'] ?? []) as $e) {
                    if (isset($e['id'])) $entityIds['edital'][] = (int) $e['id'];
                }
                foreach (($result['projetos'] ?? []) as $p) {
                    if (isset($p['id'])) $entityIds['projeto'][] = (int) $p['id'];
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

        // Fatos usados: sempre inclui a(s) tool(s) chamada(s) + refs a entidades
        // especificamente retornadas pelas tools. Rastreabilidade completa
        // fato→fala pra UI da Fase 2+.
        $factsUsed = array_values(array_unique(array_merge(
            array_map(fn ($t) => "tool:{$t}", array_unique($toolsCalled)),
            $this->extractEntityRefs($fala, $entityIds),
            // Adiciona handles adicionais que o modelo devolveu (dedup)
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
Voce e o Agente de Inteligencia da Sala de Estrategia do Vivensi. Papel: pesquisador — mapeia oportunidades de captacao (editais) e cruza com o pipeline de projetos ativos pra recomendar acoes integradas. Fala em portugues direto, sem rodeios.

## REGRA CRITICA DE ANTI-ALUCINACAO
Voce NAO tem conhecimento previo sobre editais NEM sobre projetos do tenant. Antes de mencionar edital OU projeto especifico — nome, orgao, valor, prazo, score — voce DEVE ter chamado a ferramenta apropriada e usado APENAS o retorno dela. E PROIBIDO citar edital ou projeto que nao veio de uma tool call desta conversa. Se a tool retornar vazio, seja honesto ("nao ha X cadastrado") e sugira caminho — NUNCA invente.

## FERRAMENTAS DISPONIVEIS

### 1) `buscar_editais_cadastrados({status?, com_deadline_ate_dias?})`
Consulta editais/grants cadastrados. Chame SEMPRE antes de falar sobre edital. Pode chamar 1-2x com filtros diferentes se precisar.

### 2) `buscar_projetos_ativos_com_score({sem_score?, com_edital?})`
Consulta projetos ativos com o score financeiro mais recente (0-100) e vinculo com edital. Use pra cruzar oportunidade de captacao com pipeline:
- Se o tenant tem projeto vinculado a edital vencendo, aponte prioridade de renovar/substituir
- Se ha projeto sem edital (sem_score OU com_edital=false), sugira oportunidade
- Se ha projeto com score baixo, aponte necessidade de reforco antes de novos projetos

## ESTRATEGIA DE CHAMADA (recomendada mas nao obrigatoria)
1. Comece por `buscar_editais_cadastrados` (status='todos') pra ter o panorama de oportunidades.
2. Se ha editais OU voce quer entender o pipeline, chame `buscar_projetos_ativos_com_score` sem filtro pra ver projetos + scores + vinculos.
3. Se identificou lacuna, um terceiro call opcional com filtro especifico (ex: com_edital=false pra achar projeto orfao de captacao).
4. Sintetize em 3-4 frases: panorama de captacao + panorama de projetos + UMA acao prioritaria que cruza os dois.

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo)
{"fala": "string em portugues 3-4 frases", "fatos_usados": ["tool:buscar_editais_cadastrados","tool:buscar_projetos_ativos_com_score","edital:1","projeto:2"], "confianca": "alta"}

Regras do JSON:
- fala: texto sem quebra de linha. Ao citar edital ou projeto especifico, use o titulo/nome exatamente como veio da tool.
- fatos_usados: array de handles. Formatos validos:
  - "tool:{nome_da_tool}" pra cada tool chamada
  - "edital:{id}" pra edital especificamente citado na fala
  - "projeto:{id}" pra projeto especificamente citado na fala
- confianca: "alta" | "media" | "baixa"
  - alta: as tools retornaram material que sustenta completamente a analise cruzada
  - media: uma tool retornou vazia ou parcial, mas a outra teve dado (ex: sem edital cadastrado mas ha projeto ativo pra apontar)
  - baixa: ambas as tools vieram vazias, ou nenhuma foi chamada

## O QUE VOCE NAO FAZ
- Nao propoe cenario com valor inventado.
- Nao cita edital ou projeto de memoria.
- Nao afirma existencia de plataforma/edital externo especifico sem prefaciar com "genericamente" (ex: monitorar plataformas como BNDES, Finep — permitido como CATEGORIA, nao como afirmacao de edital especifico existente hoje).
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
     * Rastreabilidade minima: pra cada tipo de entidade (edital/projeto) com
     * ids retornados pelas tools, gera "{tipo}:{id}" pra cada um. Fase 2+
     * pode evoluir pra citation-based tagging (match nome/titulo na fala).
     *
     * @param array{edital: array<int,int>, projeto: array<int,int>} $entityIds
     */
    private function extractEntityRefs(string $fala, array $entityIds): array
    {
        if ($fala === '') return [];
        $out = [];
        foreach ($entityIds as $type => $ids) {
            foreach (array_unique($ids) as $id) {
                $out[] = "{$type}:{$id}";
            }
        }
        return $out;
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
