<?php

namespace App\Services\StrategyRoom;

use App\Enums\StrategyRoomMode;
use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\StrategyMessage;
use App\Models\StrategySession;
use App\Models\Tenant;
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
    public const AGENT_KEY     = 'financeiro';
    public const DEFAULT_MODEL = 'deepseek-v4-flash';
    private const MAX_TOOL_ITERATIONS = 3;

    private function model(): string
    {
        return (string) config('strategy_room.models.financeiro', self::DEFAULT_MODEL);
    }

    public function __construct(
        private DeepSeekService $deepSeek,
        private TenantContextService $tenantCtx,
    ) {}

    /**
     * Se $sessionId for null, cria sessao nova (compat Fase 0 / teste isolado).
     * Se dado, usa a sessao existente (uso normal via StrategyDebateOrchestrator).
     *
     * Devolve:
     *   - Success: ['fala' => '...', 'fatos_usados' => [...], 'confianca' => '...', 'session_id', 'message_id']
     *   - Falha:   ['error' => '...', 'session_id' => ...]
     */
    public function speak(int $tenantId, ?int $sessionId = null): array
    {
        $ctx           = $this->tenantCtx->for($tenantId);
        $projectScores = $this->activeProjectFinancialScores($tenantId);
        $factCatalog   = $this->buildFactCatalog($ctx, $projectScores);

        $session = $sessionId
            ? StrategySession::withoutGlobalScopes()->findOrFail($sessionId)
            : StrategySession::create([
                'tenant_id'    => $tenantId,
                'trigger_type' => 'manual_test',
                'status'       => 'em_andamento',
            ]);

        $mode = Tenant::find($tenantId)?->strategyRoomMode() ?? StrategyRoomMode::Institucional;

        $systemPrompt = $this->buildSystemPrompt($factCatalog, $mode);
        $messages     = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => 'Analise a situacao financeira. Use as ferramentas quando quiser detalhar por projeto ou ver tendencia. Devolva SOMENTE o JSON no formato instruido.'],
        ];
        $tools = FinancialAgentTools::definitions();

        $rawContent  = '';
        $toolsCalled = [];
        $projetoIds  = []; // ids de projetos citados pelas tools — vira handle projeto:X

        for ($iter = 0; $iter < self::MAX_TOOL_ITERATIONS + 1; $iter++) {
            $response = $this->deepSeek->chat($messages, $this->model(), $tools, $tenantId);

            if (isset($response['error'])) {
                $session->update(['status' => 'concluida']);
                Log::warning('StrategyRoom/Financeiro: DeepSeek erro', [
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
                $name = data_get($tc, 'function.name', '');
                $args = json_decode(data_get($tc, 'function.arguments', '{}'), true) ?: [];
                $result = FinancialAgentTools::execute($name, $args, $tenantId);

                $toolsCalled[] = $name;
                foreach (($result['projetos'] ?? []) as $p) {
                    if (isset($p['id'])) $projetoIds[] = (int) $p['id'];
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
            $session->update(['status' => 'concluida']);
            Log::warning('StrategyRoom/Financeiro: JSON invalido', [
                'tenant_id' => $tenantId, 'session' => $session->id, 'raw_len' => strlen($rawContent),
            ]);
            return [
                'error'      => 'O modelo devolveu resposta fora do formato JSON esperado.',
                'session_id' => $session->id,
            ];
        }

        $fala       = trim((string) ($parsed['fala'] ?? ''));
        $factsUsed  = $this->sanitizeFactsUsed($parsed['fatos_usados'] ?? [], $factCatalog);
        // Trave defensiva: se a fala cita `handle` em backtick mas o modelo
        // esqueceu de incluir no array, mescla. Garante consistencia entre
        // texto e metadata mesmo com escorregao do modelo.
        $factsUsed  = $this->mergeFactsFromFala($fala, $factsUsed, $factCatalog);
        // Adiciona rastreabilidade de tools + entidades
        $factsUsed  = array_values(array_unique(array_merge(
            $factsUsed,
            array_map(fn ($t) => "tool:{$t}", array_unique($toolsCalled)),
            array_map(fn ($id) => "projeto:{$id}", array_unique($projetoIds)),
        )));
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

        // Se sessao foi criada aqui (teste isolado), fecha. Orquestrador
        // que passou sessionId decide quando fechar.
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

    private function buildSystemPrompt(array $factCatalog, string $mode): string
    {
        $factsBlock = '';
        foreach ($factCatalog as $key => $f) {
            $factsBlock .= "- `{$key}` — {$f['label']}: {$f['valor']} {$f['unit']}\n";
        }

        $contexto = $mode === StrategyRoomMode::Negocio
            ? 'Este tenant e um pequeno negocio (MEI/autonomo/PJ): a receita vem de CLIENTES e vendas de produtos/servicos. Use vocabulario de faturamento, clientes e fluxo de caixa. NAO fale de doadores, editais nem captacao — esses conceitos nao existem neste perfil.'
            : 'Este tenant e uma organizacao de impacto social (ONG/gestor de projetos): a receita vem de doadores, editais e captacao de recursos. Use vocabulario de captacao, doadores e sustentabilidade dos projetos.';

        return <<<PROMPT
Voce e o Agente Financeiro da Sala de Estrategia do Vivensi, inspirado no papel de CFO. Fala em portugues direto, sem rodeios. Sem emojis, sem saudacoes floreadas.

## CONTEXTO DO TENANT
{$contexto}

## FATOS DISPONIVEIS BASE (unicos que voce pode citar em backticks)
{$factsBlock}
## FERRAMENTAS OPCIONAIS (chame quando quiser aprofundar)

### `analisar_transacoes_por_projeto({periodo_dias?})`
Quebra receita/despesa por projeto ativo no periodo. Retorna status por projeto (sangrando/so_despesa/so_receita/sem_movimentacao/saudavel) e pct de budget usado. Use quando quiser apontar problema em projeto ESPECIFICO em vez de so falar do agregado do tenant.

### `historico_health_score({ultimos_n_snapshots?})`
Ultimos snapshots do score financeiro por projeto — permite ver TENDENCIA (subindo/estavel/caindo/sem_dado). Use quando quiser dizer "score caiu de X pra Y" ou detectar deterioracao ao longo do tempo. Muito util quando o handle `project_health_score` do catalogo mostra so o valor atual.

Chame 0, 1 ou as 2 ferramentas conforme fizer sentido pra sua analise. Se decidir chamar, cite os projetos especificos retornados pelo nome (nao invente).

## REGRA CRITICA DE ANTI-ALUCINACAO
NUNCA invente ou estime um numero que nao esteja listado acima OU vindo de tool call desta sessao. Se a informacao que voce precisa nao estiver disponivel, diga explicitamente "nao tenho essa informacao no momento" em vez de calcular ou aproximar. Nao projete cenario com numero fabricado. Nao cite projeto/valor que nao veio de tool call ou do catalogo base.

## FORMATO DE SAIDA (obrigatorio — devolva SOMENTE o JSON abaixo, sem texto antes nem depois)
{"fala": "string em portugues, direto (2-4 frases). Cite as fontes usando os handles em backticks, ex: baseado no `saldo_mes`", "fatos_usados": ["saldo_mes","receita_mes"], "confianca": "alta"}

Regras do JSON:
- fala: texto sem quebra de linha, aspas duplas escapadas se precisar
- fatos_usados: array com os handles (exatamente como listado acima) que voce citou na fala. REGRA DE CONSISTENCIA: CADA handle do catalogo base que aparece em backtick na fala DEVE estar no array. Se citou 5 handles distintos em backtick, o array tem 5 entradas. Se nao citou nenhum, array vazio []. NUNCA cite handle na fala sem incluir no array (e vice-versa). Handles de tools e projetos sao adicionados automaticamente pelo sistema — voce nao precisa se preocupar com "tool:X" nem "projeto:Y" no array.
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
