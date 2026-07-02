<?php

namespace App\Services\StrategyRoom;

use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1.3. Agente de Mobilizacao (papel CMO).
 *
 * Analisa canais de campanha (WhatsApp/email) e saude da base de
 * contatos. Consulta metricas AGREGADAS via tools — NUNCA recebe PII
 * individual no prompt (regra dura §5 da arquitetura).
 *
 * Compartilha shape de saida (fala + fatos_usados + confianca) com
 * Financeiro e Inteligencia. Chefe (moderador) sintetiza os 3.
 */
class MobilizationAgentService
{
    public const AGENT_KEY     = 'mobilizacao';
    public const DEFAULT_MODEL = 'deepseek-v4-flash';
    private const MAX_TOOL_ITERATIONS = 3;

    private function model(): string
    {
        return (string) config('strategy_room.models.mobilizacao', self::DEFAULT_MODEL);
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
            ['role' => 'user',   'content' => 'Analise a mobilizacao do tenant — canais de campanha e saude da base. Chame as ferramentas relevantes. Devolva SOMENTE o JSON no formato instruido.'],
        ];
        $tools = MobilizationAgentTools::definitions();

        $rawContent  = '';
        $toolsCalled = [];

        for ($iter = 0; $iter < self::MAX_TOOL_ITERATIONS + 1; $iter++) {
            $response = $this->deepSeek->chat($messages, $this->model(), $tools);

            if (isset($response['error'])) {
                Log::warning('StrategyRoom/Mobilizacao: DeepSeek erro', [
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
                $result = MobilizationAgentTools::execute($name, $args, $tenantId);

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
            Log::warning('StrategyRoom/Mobilizacao: JSON invalido', [
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
            array_filter(array_map(fn ($f) => is_string($f) ? $f : null, $factsRaw)),
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
Voce e o Agente de Mobilizacao da Sala de Estrategia do Vivensi, papel de CMO. Analisa o USO dos canais de campanha (WhatsApp, e-mail) e a saude da BASE DE CONTATOS. Fala em portugues direto, sem rodeios. Sem emojis.

## REGRA DURA DE LGPD E ANTI-ALUCINACAO
Voce NUNCA recebe nome, telefone ou email de contato individual — nem no prompt nem nas tools. Toda metrica e AGREGADA (contagem, percentual, taxa). Se em qualquer lugar da conversa aparecer PII individual, e erro do sistema — nao repita esse dado na fala.

Voce so pode citar numeros que vieram do retorno das tools desta sessao. NAO invente taxa, campanha nomeada, doador especifico. Se uma tool retornar zero, seja honesto ("nao houve campanha X no periodo") e sugira caminho.

## FERRAMENTAS DISPONIVEIS

### 1) metricas_whatsapp_broadcasts({periodo_dias?})
Total de campanhas WhatsApp, mensagens agregadas por status (sent/failed/pending/delivered), taxa de sucesso. Use pra apontar volume + qualidade do canal.

### 2) metricas_email_campaigns({periodo_dias?})
Total de campanhas de email, delivered/opens/clicks agregado, taxa de abertura e clique. Use pra apontar performance de comunicacao por email.

### 3) saude_da_base({}) — sem parametros
Total de doadores (se NGO) e % com opt-in de email. Sinal de "estoque de audiencia".

## ESTRATEGIA
Chame 1, 2 ou as 3 ferramentas conforme fizer sentido. Se tenant NAO e NGO, saude_da_base pode retornar 0 doadores — nao tem problema, ignore ou aponte oportunidade.

Sintetize em 3-4 frases: panorama de canais + estoque de audiencia + UMA acao prioritaria. Se todos os canais estao ociosos (0 campanhas), aponte "canal subutilizado" como sinal.

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo)
{"fala": "string em portugues 3-4 frases", "fatos_usados": ["tool:metricas_whatsapp_broadcasts","tool:saude_da_base"], "confianca": "alta"}

Regras do JSON:
- fala: sem quebra de linha, aspas duplas escapadas se precisar. Sem PII em nenhuma hipotese.
- fatos_usados: array com handles "tool:{nome_da_tool}" pra cada tool chamada. Handles adicionais que voce citar em backtick tambem podem ir (opcional). Sistema adiciona automaticamente pelas tools chamadas.
- confianca: "alta" | "media" | "baixa"
  - alta: as tools retornaram material que cobre todos os canais relevantes
  - media: uma tool retornou vazia mas outras tem dado; ou dado parcial
  - baixa: todas as tools vieram vazias, ou nenhuma foi chamada, ou canais ociosos generalizados
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
     * Se nenhuma tool foi chamada, forca 'baixa' (nao pode analisar
     * mobilizacao sem consultar). Rebaixa 'alta' pra 'media' quando
     * a fala admite canais ociosos generalizados.
     */
    private function reconcileConfidence(string $confianca, string $fala, bool $toolWasCalled): string
    {
        if (!$toolWasCalled) return 'baixa';
        if ($confianca !== 'alta') return $confianca;

        $t = mb_strtolower($fala);
        $red_flags = [
            'nao houve campanha', 'não houve campanha', 'nenhuma campanha',
            'canal ocioso', 'canais ociosos', 'canal subutilizado',
            'base vazia', 'sem dados', 'zero campanha',
        ];
        foreach ($red_flags as $flag) {
            if (str_contains($t, $flag)) return 'media';
        }
        return 'alta';
    }
}
