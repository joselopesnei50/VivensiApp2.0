<?php

namespace App\Services\StrategyRoom;

use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1. Estrategista-Chefe (papel de CEO/Moderador).
 *
 * Recebe APENAS as falas dos outros agentes (Financeiro + Inteligencia) —
 * NAO recebe dado bruto do tenant. Cf. arquitetura §3: "o moderador
 * recebe o resumo das 3 falas e sintetiza — nao o dado bruto de novo".
 *
 * Papel: sintetizar em 3-4 frases + priorizar UMA acao concreta. Nao
 * introduz fato novo — so o que os outros ja disseram. Confianca do
 * chefe e derivada da confianca dos inputs (nunca maior que o pior).
 */
class ChiefStrategistAgentService
{
    public const AGENT_KEY = 'estrategista_chefe';
    public const MODEL     = 'deepseek-v4-pro';

    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    /**
     * Sintetiza as falas anteriores da sessao. Retorna:
     *   - Success: ['fala','fatos_usados','confianca','session_id','message_id']
     *   - Falha:   ['error','session_id']
     */
    public function synthesize(int $sessionId): array
    {
        $session = StrategySession::withoutGlobalScopes()->find($sessionId);
        if (!$session) {
            return ['error' => "Sessao #{$sessionId} nao encontrada.", 'session_id' => $sessionId];
        }

        $priorMessages = StrategyMessage::withoutGlobalScopes()
            ->where('strategy_session_id', $sessionId)
            ->whereIn('agent', ['financeiro', 'inteligencia', 'mobilizacao'])
            ->orderBy('created_at', 'asc')
            ->get(['agent', 'content', 'confidence']);

        if ($priorMessages->isEmpty()) {
            return [
                'error'      => 'Nao ha falas de agente pra sintetizar (financeiro/inteligencia/mobilizacao).',
                'session_id' => $sessionId,
            ];
        }

        $falasBlock  = $this->buildFalasBlock($priorMessages);
        $agentsHeard = $priorMessages->pluck('agent')->unique()->values()->all();

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($falasBlock)],
            ['role' => 'user',   'content' => 'Sintetize as falas acima e proponha UMA acao prioritaria. Devolva SOMENTE o JSON no formato instruido.'],
        ];

        $response = $this->deepSeek->chat($messages, self::MODEL);

        if (isset($response['error'])) {
            Log::warning('StrategyRoom/Chefe: DeepSeek erro', [
                'session' => $sessionId, 'err' => $response['error'],
            ]);
            return ['error' => $response['error'], 'session_id' => $sessionId];
        }

        $raw    = data_get($response, 'choices.0.message.content', '');
        $parsed = $this->parseJson($raw);
        if ($parsed === null) {
            Log::warning('StrategyRoom/Chefe: JSON invalido', [
                'session' => $sessionId, 'raw_len' => strlen($raw),
            ]);
            return [
                'error'      => 'O Chefe devolveu resposta fora do formato JSON esperado.',
                'session_id' => $sessionId,
            ];
        }

        $fala = trim((string) ($parsed['fala'] ?? ''));
        if ($fala === '') {
            return ['error' => 'Chefe devolveu fala vazia.', 'session_id' => $sessionId];
        }

        // fatos_usados do chefe = referencia aos agentes que citou. Filtra
        // pra so incluir agentes que efetivamente falaram nesta sessao.
        $factsRaw   = is_array($parsed['fatos_usados'] ?? null) ? $parsed['fatos_usados'] : [];
        $factsUsed  = array_values(array_intersect(
            array_map(fn ($f) => is_string($f) ? mb_strtolower(trim($f)) : '', $factsRaw),
            $agentsHeard,
        ));
        // Trave defensiva: se o chefe cita "financeiro" ou "inteligencia" na
        // fala mas esqueceu do array, mescla.
        $factsUsed = $this->mergeAgentRefsFromFala($fala, $factsUsed, $agentsHeard);

        // Confianca do chefe: nunca maior que o pior input. Se qualquer input
        // foi "baixa", chefe cai pra "media" (nao "alta"). Isso protege
        // contra o chefe sintetizar com confianca falsamente elevada.
        $chiefConf = $this->sanitizeConfidence($parsed['confianca'] ?? 'media');
        $chiefConf = $this->capConfidenceByInputs($chiefConf, $priorMessages->pluck('confidence')->all());

        $message = StrategyMessage::create([
            'tenant_id'           => (int) $session->tenant_id,
            'strategy_session_id' => $session->id,
            'agent'               => self::AGENT_KEY,
            'content'             => $fala,
            'facts_used'          => $factsUsed,
            'confidence'          => $chiefConf,
        ]);

        // O chefe fecha a sessao — e sempre a ultima fala do debate na Fase 1.
        $session->update(['status' => 'concluida']);

        return [
            'session_id'   => $session->id,
            'message_id'   => $message->id,
            'fala'         => $fala,
            'fatos_usados' => $factsUsed,
            'confianca'    => $chiefConf,
        ];
    }

    private function buildFalasBlock($priorMessages): string
    {
        $out = '';
        foreach ($priorMessages as $m) {
            $agent = mb_strtoupper((string) $m->agent);
            $out .= "### {$agent} (confianca={$m->confidence})\n";
            $out .= trim((string) $m->content) . "\n\n";
        }
        return trim($out);
    }

    private function buildSystemPrompt(string $falasBlock): string
    {
        return <<<PROMPT
Voce e o Estrategista-Chefe da Sala de Estrategia do Vivensi, papel de CEO/moderador. Fala em portugues direto, sem rodeios. Sem emojis.

## FALAS DOS AGENTES QUE VOCE VAI SINTETIZAR
{$falasBlock}

## REGRA CRITICA DE ANTI-ALUCINACAO
Voce so pode sintetizar o que os outros agentes disseram acima. NAO introduza fato novo. NAO cite numero ou edital que nao apareceu nas falas acima. Se as falas foram evasivas ou incompletas, seja honesto ("os agentes indicaram lacunas em X"). Nao invente contexto.

## O QUE VOCE FAZ
1. Sintetiza em 2-3 frases o que os agentes convergem OU divergem sobre a situacao.
2. Prioriza UMA acao concreta — a que oferece maior alavancagem dado o que os agentes disseram.
3. Se ha lacuna importante (dado ausente que impede recomendacao segura), diga isso explicitamente antes de propor acao.

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo)
{"fala": "string em portugues 3-4 frases: sintese + acao prioritaria", "fatos_usados": ["financeiro","inteligencia"], "confianca": "alta"}

Regras do JSON:
- fala: sem quebra de linha, aspas duplas escapadas se precisar. Cite explicitamente os agentes ao referenciar: "conforme o Financeiro..." / "o Inteligencia apontou...".
- fatos_usados: array com os agentes cuja fala voce efetivamente usou na sintese. Valores validos: "financeiro", "inteligencia" e "mobilizacao" (nao inclua outros). So inclua agente cuja fala apareceu na sessao — ignore o que nao veio.
- confianca: "alta" | "media" | "baixa"
  - alta: ambos os agentes deram base solida e a sintese aponta acao clara
  - media: ambos deram base parcial OU um deles teve confianca media
  - baixa: um ou ambos deram confianca baixa OU as falas sao inconclusivas
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
     * Trava do chefe: nao pode exceder o pior input em mais de 1 nivel.
     *
     * Regras:
     *   - Se algum input e 'baixa' → chefe max = 'media' (1 acima do pior)
     *   - Se todos inputs sao 'media' ou melhor → chefe pode ser 'alta'
     *   - Chefe nunca sobe em relacao a propria escolha, so desce
     *
     * Ex:
     *   inputs=[baixa,alta], chief=alta  → media
     *   inputs=[baixa],      chief=alta  → media
     *   inputs=[media,alta], chief=alta  → alta   (media+1 = alta ok)
     *   inputs=[alta,alta],  chief=media → media  (nunca sobe)
     */
    private function capConfidenceByInputs(string $chief, array $inputs): string
    {
        if (empty($inputs)) return $chief;
        $rank = ['baixa' => 0, 'media' => 1, 'alta' => 2];
        $inv  = ['baixa', 'media', 'alta'];

        $chiefRank = $rank[$chief] ?? 1;
        $minInput  = min(array_map(fn ($i) => $rank[$i] ?? 1, $inputs));
        $capRank   = min($chiefRank, $minInput + 1);

        return $inv[$capRank];
    }

    /**
     * Se a fala menciona "Financeiro" ou "Inteligencia" (com maiuscula, sem
     * acento, etc) mas o array esta sem, mescla. Analogo ao mergeFactsFromFala
     * do Financial.
     */
    private function mergeAgentRefsFromFala(string $fala, array $facts, array $heard): array
    {
        $t = mb_strtolower($fala);
        foreach ($heard as $agent) {
            if (str_contains($t, $agent) && !in_array($agent, $facts, true)) {
                $facts[] = $agent;
            }
        }
        return $facts;
    }
}
