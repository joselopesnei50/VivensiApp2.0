<?php

namespace App\Services\StrategyRoom;

use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 4. Agente de Programas (papel COO — Sofia).
 *
 * Responde "os projetos estao ENTREGANDO na ponta?": frequencia das
 * aulas/atividades (Lista de Presenca), risco de evasao de beneficiarios
 * e execucao de tarefas dos projetos ativos.
 *
 * Mesmo shape de saida (fala + fatos_usados + confianca) e mesmas traves
 * anti-alucinacao dos outros 3 agentes de dado. Nunca recebe PII —
 * evasao chega como contagem agregada por projeto.
 */
class ProgramsAgentService
{
    public const AGENT_KEY     = 'programas';
    public const DEFAULT_MODEL = 'deepseek-v4-flash';
    private const MAX_TOOL_ITERATIONS = 3;

    private function model(): string
    {
        return (string) config('strategy_room.models.programas', self::DEFAULT_MODEL);
    }

    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    public function speak(int $tenantId, ?int $sessionId = null): array
    {
        $session = $sessionId
            ? StrategySession::withoutGlobalScopes()->findOrFail($sessionId)
            : StrategySession::create([
                'tenant_id'    => $tenantId,
                'trigger_type' => 'manual_test',
                'status'       => 'em_andamento',
            ]);

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ['role' => 'user',   'content' => 'Analise a execucao dos programas do tenant — frequencia/evasao e tarefas dos projetos. Chame as ferramentas relevantes. Devolva SOMENTE o JSON no formato instruido.'],
        ];
        $tools = ProgramsAgentTools::definitions();

        $rawContent  = '';
        $toolsCalled = [];

        for ($iter = 0; $iter < self::MAX_TOOL_ITERATIONS + 1; $iter++) {
            $response = $this->deepSeek->chat($messages, $this->model(), $tools);

            if (isset($response['error'])) {
                Log::warning('StrategyRoom/Programas: DeepSeek erro', [
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

            $messages[] = $assistantMsg;
            foreach ($toolCalls as $tc) {
                $name   = data_get($tc, 'function.name', '');
                $args   = json_decode(data_get($tc, 'function.arguments', '{}'), true) ?: [];
                $result = ProgramsAgentTools::execute($name, $args, $tenantId);

                $toolsCalled[] = $name;

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
            Log::warning('StrategyRoom/Programas: JSON invalido', [
                'tenant_id' => $tenantId, 'session' => $session->id, 'raw_len' => strlen($rawContent),
            ]);
            return [
                'error'      => 'O modelo devolveu resposta fora do formato JSON esperado.',
                'session_id' => $session->id,
            ];
        }

        $fala = trim((string) ($parsed['fala'] ?? ''));
        if ($fala === '') {
            if (!$sessionId) $session->update(['status' => 'concluida']);
            return ['error' => 'Modelo devolveu fala vazia.', 'session_id' => $session->id];
        }

        $factsRaw  = is_array($parsed['fatos_usados'] ?? null) ? $parsed['fatos_usados'] : [];
        $factsUsed = array_values(array_unique(array_merge(
            array_map(fn ($t) => "tool:{$t}", array_unique($toolsCalled)),
            $this->cleanHandles($factsRaw),
        )));

        $confianca = $this->reconcileConfidence(
            $this->sanitizeConfidence($parsed['confianca'] ?? 'media'),
            $fala,
            !empty($toolsCalled),
        );

        $message = StrategyMessage::create([
            'tenant_id'           => $tenantId,
            'strategy_session_id' => $session->id,
            'agent'               => self::AGENT_KEY,
            'content'             => $fala,
            'facts_used'          => $factsUsed,
            'confidence'          => $confianca,
        ]);

        if (!$sessionId) $session->update(['status' => 'concluida']);

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
Voce e Sofia, a Diretora de Programas da Sala de Estrategia do Vivensi, papel de COO. Analisa se os projetos estao ENTREGANDO na ponta: frequencia dos beneficiarios nas aulas/atividades, risco de evasao e execucao de tarefas. Fala em portugues direto, sem rodeios. Sem emojis.

## REGRA DURA DE LGPD E ANTI-ALUCINACAO
Voce NUNCA recebe nome de beneficiario, aluno ou pessoa individual — nem no prompt nem nas tools. Risco de evasao chega como CONTAGEM por projeto. Se em qualquer lugar da conversa aparecer PII individual, e erro do sistema — nao repita esse dado na fala.

Voce so pode citar numeros que vieram do retorno das tools desta sessao. NAO invente taxa de presenca, beneficiario especifico ou tarefa nomeada. Se uma tool retornar vazio, seja honesto ("nao ha registro de X no periodo") e aponte a lacuna de monitoramento.

## FERRAMENTAS DISPONIVEIS

### 1) frequencia_e_evasao({periodo_dias?})
Por projeto: total de aulas, taxa de presenca, beneficiarios acompanhados e QUANTOS estao em risco de evasao (3 faltas seguidas ou >=30% de ausencia). Risco de evasao e o sinal mais grave da sua area — priorize.

### 2) execucao_de_tarefas({periodo_dias?})
Por projeto ativo: tarefas totais, concluidas, VENCIDAS (prazo estourado) e % de conclusao. Sinal de gestao operacional.

## ESTRATEGIA
Chame as 2 ferramentas (ou 1 se a outra nao fizer sentido). Prioridade de analise: (1) beneficiarios em risco de evasao, (2) tarefas vencidas, (3) taxa de presenca geral. Se nao ha aula registrada, isso e lacuna de monitoramento — aponte, nao invente.

Sintetize em 3-4 frases: situacao da entrega na ponta + UMA acao prioritaria (ex: busca ativa dos beneficiarios em risco, destravar tarefas vencidas do projeto X).

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo)
{"fala": "string em portugues 3-4 frases", "fatos_usados": ["tool:frequencia_e_evasao","tool:execucao_de_tarefas"], "confianca": "alta"}

Regras do JSON:
- fala: sem quebra de linha, aspas duplas escapadas se precisar. Sem PII em nenhuma hipotese.
- fatos_usados: array com handles "tool:{nome_da_tool}" pra cada tool chamada. Sistema adiciona automaticamente pelas tools chamadas.
- confianca: "alta" | "media" | "baixa"
  - alta: as tools retornaram dado que cobre frequencia E execucao
  - media: uma dimensao veio vazia mas a outra tem dado
  - baixa: tudo vazio, ou nenhuma tool foi chamada
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

    private function cleanHandles(array $raw): array
    {
        return array_values(array_filter(
            array_map(fn ($f) => is_string($f) ? trim($f) : null, $raw),
            fn ($h) => is_string($h) && $h !== '' && !str_contains($h, ' '),
        ));
    }

    /**
     * Se nenhuma tool foi chamada, forca 'baixa'. Rebaixa 'alta' pra
     * 'media' quando a fala admite ausencia generalizada de dado.
     */
    private function reconcileConfidence(string $confianca, string $fala, bool $toolWasCalled): string
    {
        if (!$toolWasCalled) return 'baixa';
        if ($confianca !== 'alta') return $confianca;

        $t = mb_strtolower($fala);
        $red_flags = [
            'nao ha registro', 'não há registro', 'nenhuma aula',
            'sem aulas', 'sem tarefas', 'nenhuma tarefa',
            'lacuna de monitoramento', 'sem dados',
        ];
        foreach ($red_flags as $flag) {
            if (str_contains($t, $flag)) return 'media';
        }
        return 'alta';
    }
}
