<?php

namespace App\Services\StrategyRoom;

use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Services\DeepSeekService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 0. Agente Financeiro sozinho (papel de CFO).
 *
 * Speak(tenantId): monta prompt com dado agregado real do tenant + score
 * financeiro dos projetos, chama deepseek-v4-pro pedindo JSON estruturado,
 * persiste em StrategySession + StrategyMessage, devolve o array decodificado.
 *
 * Anti-alucinacao: o prompt lista EXPLICITAMENTE os unicos fatos que o
 * agente pode citar e proibe estimar valor fora dessa lista. Se o modelo
 * devolver JSON invalido, loga (sem PII) e devolve erro estruturado —
 * nao quebra nem cria mensagem parcial.
 *
 * Cf. md/vivensi-sala-estrategia-arquitetura.md §2 (limites do agente)
 * e md/prompt-fase0-agente-financeiro.md.
 */
class FinancialAgentService
{
    public const AGENT_KEY = 'financeiro';
    public const MODEL     = 'deepseek-v4-pro';

    public function __construct(
        private DeepSeekService $deepSeek,
        private TenantContextService $tenantCtx,
    ) {}

    /**
     * Devolve:
     *   - Success: ['fala' => '...', 'fatos_usados' => [...], 'confianca' => '...']
     *   - Falha:   ['error' => '...']
     */
    public function speak(int $tenantId): array
    {
        $ctx           = $this->tenantCtx->for($tenantId);
        $projectScores = $this->activeProjectFinancialScores($tenantId);
        $factCatalog   = $this->buildFactCatalog($ctx, $projectScores);

        $session = StrategySession::create([
            'tenant_id'    => $tenantId,
            'trigger_type' => 'manual_test',
            'status'       => 'em_andamento',
        ]);

        $systemPrompt = $this->buildSystemPrompt($factCatalog);
        $userPrompt   = 'Baseado APENAS nos fatos listados acima, comece o debate. Em portugues direto, sem rodeios. Devolva SOMENTE o JSON no formato instruido.';

        $response = $this->deepSeek->chat(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            self::MODEL,
        );

        if (isset($response['error'])) {
            $session->update(['status' => 'concluida']);
            Log::warning('StrategyRoom/Financeiro: DeepSeek retornou erro', [
                'tenant_id' => $tenantId,
                'session'   => $session->id,
                'error'     => $response['error'],
            ]);
            return ['error' => $response['error'], 'session_id' => $session->id];
        }

        $raw    = data_get($response, 'choices.0.message.content', '');
        $parsed = $this->parseJson($raw);

        if ($parsed === null) {
            $session->update(['status' => 'concluida']);
            Log::warning('StrategyRoom/Financeiro: JSON invalido do modelo', [
                'tenant_id' => $tenantId,
                'session'   => $session->id,
                'raw_len'   => strlen($raw),
                // NAO logar $raw — pode conter dado agregado; loga so o tamanho.
            ]);
            return [
                'error'      => 'O modelo devolveu resposta fora do formato JSON esperado. Tente novamente.',
                'session_id' => $session->id,
            ];
        }

        $fala       = trim((string) ($parsed['fala'] ?? ''));
        $factsUsed  = $this->sanitizeFactsUsed($parsed['fatos_usados'] ?? [], $factCatalog);
        // Trave defensiva: se a fala cita `handle` em backtick mas o modelo
        // esqueceu de incluir no array, mescla. Garante consistencia entre
        // texto e metadata mesmo com escorregao do modelo.
        $factsUsed  = $this->mergeFactsFromFala($fala, $factsUsed, $factCatalog);
        $confianca  = $this->reconcileConfidence(
            $this->sanitizeConfidence($parsed['confianca'] ?? 'media'),
            $fala,
        );

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

        $session->update(['status' => 'concluida']);

        return [
            'session_id'   => $session->id,
            'message_id'   => $message->id,
            'fala'         => $fala,
            'fatos_usados' => $factsUsed,
            'confianca'    => $confianca,
        ];
    }

    /**
     * Score financeiro mais recente por projeto ativo do tenant. Se um
     * projeto nunca teve snapshot, nao aparece — respeita "so fato existente".
     *
     * @return array<int,array{project_id:int,name:string,score:int,recorded_at:string}>
     */
    private function activeProjectFinancialScores(int $tenantId): array
    {
        $projects = Project::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get(['id', 'name']);

        $out = [];
        foreach ($projects as $p) {
            $latest = ProjectHealthHistory::where('tenant_id', $tenantId)
                ->where('project_id', $p->id)
                ->orderByDesc('recorded_at')
                ->first(['financial_score', 'recorded_at']);
            if (!$latest) continue;

            $out[] = [
                'project_id'  => (int) $p->id,
                'name'        => (string) $p->name,
                'score'       => (int) $latest->financial_score,
                'recorded_at' => $latest->recorded_at?->toDateString() ?? '',
            ];
        }
        return $out;
    }

    /**
     * Catalogo de fatos que o agente pode citar. Cada chave e um "handle"
     * curto — o modelo vai devolver esses handles em facts_used.
     */
    private function buildFactCatalog(array $ctx, array $projectScores): array
    {
        return [
            'saldo_mes' => [
                'label' => 'Saldo do mes corrente',
                'valor' => number_format($ctx['balance'], 2, ',', '.'),
                'unit'  => 'R$',
            ],
            'receita_mes' => [
                'label' => 'Receita paga do mes corrente',
                'valor' => number_format($ctx['income'], 2, ',', '.'),
                'unit'  => 'R$',
            ],
            'despesa_mes' => [
                'label' => 'Despesa paga do mes corrente',
                'valor' => number_format($ctx['expense'], 2, ',', '.'),
                'unit'  => 'R$',
            ],
            'projetos_ativos' => [
                'label' => 'Numero de projetos ativos',
                'valor' => (string) $ctx['active_projects'],
                'unit'  => 'projetos',
            ],
            'tarefas_vencidas' => [
                'label' => 'Tarefas vencidas em aberto',
                'valor' => (string) $ctx['overdue_tasks'],
                'unit'  => 'tarefas',
            ],
            'project_health_score' => [
                'label' => 'Score financeiro (0-100) por projeto ativo, ultimo snapshot',
                'valor' => empty($projectScores)
                    ? 'sem snapshot registrado'
                    : implode(' | ', array_map(
                        fn ($p) => "{$p['name']}: {$p['score']} (em {$p['recorded_at']})",
                        $projectScores
                    )),
                'unit'  => 'score',
            ],
        ];
    }

    private function buildSystemPrompt(array $factCatalog): string
    {
        $factsBlock = '';
        foreach ($factCatalog as $key => $f) {
            $factsBlock .= "- `{$key}` — {$f['label']}: {$f['valor']} {$f['unit']}\n";
        }

        return <<<PROMPT
Voce e o Agente Financeiro da Sala de Estrategia do Vivensi, inspirado no papel de CFO. Fala em portugues direto, sem rodeios. Sem emojis, sem saudacoes floreadas.

## FATOS DISPONIVEIS (unicos que voce pode citar)
{$factsBlock}
## REGRA CRITICA DE ANTI-ALUCINACAO
NUNCA invente ou estime um numero que nao esteja listado acima. Se a informacao que voce precisa nao estiver disponivel, diga explicitamente "nao tenho essa informacao no momento" em vez de calcular ou aproximar. Nao projete cenario com numero fabricado. Nao cite valor de projeto se o handle project_health_score estiver como "sem snapshot registrado".

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo, sem texto antes nem depois)
{"fala": "string em portugues, direto (2-4 frases). Cite as fontes usando os handles em backticks, ex: baseado no `saldo_mes`", "fatos_usados": ["saldo_mes","receita_mes"], "confianca": "alta"}

Regras do JSON:
- fala: texto sem quebra de linha, aspas duplas escapadas se precisar
- fatos_usados: array com os handles (exatamente como listado acima) que voce citou na fala. REGRA DE CONSISTENCIA: CADA handle que aparece em backtick na fala DEVE estar no array. Se citou 5 handles distintos em backtick, o array tem 5 entradas. Se nao citou nenhum, array vazio []. NUNCA cite handle na fala sem incluir no array (e vice-versa).
- confianca: uma das strings "alta" | "media" | "baixa"
  - alta: TODOS os dados relevantes estao presentes E cobrem a resposta completamente, SEM RESSALVA de dado ausente. Se voce escreveu "nao tenho essa informacao", "desconhecido", "sem snapshot", "nao ha como avaliar", "sem dados" ou equivalente em QUALQUER parte da fala, confianca NAO PODE ser alta.
  - media: usou os dados mas com ressalva de contexto ausente (ex: 1 handle chave sem valor, fala menciona "sem snapshot" em ponto nao-critico).
  - baixa: teve que dizer "nao tenho essa informacao no momento" em ponto critico da analise (ex: score do projeto sem snapshot quando isso e o eixo da recomendacao).

## O QUE VOCE FAZ
Analisa a saude financeira do tenant no momento, aponta o que chama atencao (positivo ou negativo), e sugere UM foco de acao. Nao propoe cenario. Nao inventa historico. So o que os fatos mostram agora.
PROMPT;
    }

    /**
     * Parse defensivo: modelo as vezes envolve JSON em markdown ```json ... ```
     * ou joga texto antes/depois. Extrai o primeiro objeto JSON valido.
     */
    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // Tenta parse direto primeiro
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;

        // Extrai bloco entre a primeira { e a ultima } — funciona pra ```json wrappers
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) return null;

        $candidate = substr($raw, $start, $end - $start + 1);
        $decoded   = json_decode($candidate, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Filtra fatos_usados retornados pra so incluir handles que existem no
     * catalogo — se o modelo alucina handle inexistente, cai fora.
     */
    private function sanitizeFactsUsed(mixed $facts, array $catalog): array
    {
        if (!is_array($facts)) return [];
        $valid = array_keys($catalog);
        return array_values(array_filter(
            array_map(fn ($f) => is_string($f) ? $f : null, $facts),
            fn ($f) => $f !== null && in_array($f, $valid, true),
        ));
    }

    private function sanitizeConfidence(mixed $c): string
    {
        $c = is_string($c) ? mb_strtolower(trim($c)) : 'media';
        return in_array($c, ['alta', 'media', 'baixa'], true) ? $c : 'media';
    }

    /**
     * Extrai handles em backtick (`handle`) da fala e mescla com o array
     * que o modelo devolveu. Corrige o caso onde o modelo cita um fato mas
     * esquece de listar. Preserva a ordem original + adiciona os faltantes
     * ao final. So considera handles que existem no catalogo.
     */
    private function mergeFactsFromFala(string $fala, array $factsUsed, array $catalog): array
    {
        if (!preg_match_all('/`([a-z_][a-z0-9_]*)`/u', $fala, $m)) {
            return $factsUsed;
        }
        $valid = array_keys($catalog);
        $out   = $factsUsed;
        foreach ($m[1] as $h) {
            if (in_array($h, $valid, true) && !in_array($h, $out, true)) {
                $out[] = $h;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Trava a calibracao: se o modelo devolveu 'alta' mas a fala admite
     * dado ausente ("sem snapshot", "desconhecido", "nao tenho essa
     * informacao", "nao ha como avaliar"), rebaixa pra 'media'. Nunca
     * eleva — so contem otimismo.
     */
    private function reconcileConfidence(string $confianca, string $fala): string
    {
        if ($confianca !== 'alta') return $confianca;

        $t = mb_strtolower($fala);
        $red_flags = [
            'sem snapshot', 'sem dados', 'desconhecid', 'nao tenho essa informacao',
            'não tenho essa informação', 'nao ha como avaliar', 'não há como avaliar',
            'nao tem snapshot', 'não tem snapshot', 'nao esta disponivel',
            'não está disponível', 'sem registro',
        ];
        foreach ($red_flags as $flag) {
            if (str_contains($t, $flag)) {
                return 'media';
            }
        }
        return 'alta';
    }
}
