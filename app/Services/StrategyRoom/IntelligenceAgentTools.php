<?php

namespace App\Services\StrategyRoom;

use App\Enums\StrategyRoomMode;
use App\Models\Client;
use App\Models\NgoGrant;
use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\Prospect;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1. Tools do Agente de Inteligencia.
 *
 * DeepSeek function calling: cada tool retorna um payload que o modelo
 * usa pra fundamentar a fala. Regra do agente: nunca afirmar existencia
 * de edital sem retorno de uma tool call REAL desta sessao (nao pode
 * "lembrar" edital de memoria do modelo).
 *
 * Nao expoe PII individual (doador, beneficiario). Editais sao entidade
 * institucional — nome, orgao, valor, prazo — nao sensivel.
 */
class IntelligenceAgentTools
{
    /**
     * Definicoes no formato OpenAI/DeepSeek tools, por modo da Sala.
     * Institucional: editais + projetos. Negocio (MEI/PJ): clientes,
     * recibos e prospeccao — editais nao fazem sentido nesse perfil.
     */
    public static function definitions(StrategyRoomMode $mode = StrategyRoomMode::Institucional): array
    {
        if ($mode === StrategyRoomMode::Negocio) {
            return self::businessDefinitions();
        }

        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_editais_cadastrados',
                    'description' => 'Consulta os editais/grants cadastrados pelo tenant no modulo NGO Grants. Use SEMPRE que precisar mencionar existencia, prazo, valor ou status de edital. Filtros opcionais permitem restringir por status (aberto, andamento, concluido) ou proximidade do prazo. Se a lista vir vazia, NAO invente edital — informe que nao ha edital cadastrado nesse escopo.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type'        => 'string',
                                'enum'        => ['aberto', 'andamento', 'concluido', 'todos'],
                                'description' => 'Filtro por status. Default "todos".',
                            ],
                            'com_deadline_ate_dias' => [
                                'type'        => 'integer',
                                'description' => 'Se preenchido, retorna somente editais com deadline dentro de N dias (util pra achar oportunidades curtas). Ex: 30 = proximos 30 dias.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_projetos_ativos_com_score',
                    'description' => 'Consulta os projetos ativos do tenant com score financeiro mais recente (0-100) e vinculo com edital (se houver). Use pra cruzar oportunidades de captacao com pipeline atual — ex: projeto vinculado a edital vencendo, ou projeto com score baixo que precisa de reforco. Se filtrar por sem_score=true, retorna so projetos que nunca tiveram snapshot (candidatos a priorizar).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'sem_score' => [
                                'type'        => 'boolean',
                                'description' => 'Se true, retorna somente projetos que nao tem snapshot de saude registrado. Util pra apontar lacuna de dado.',
                            ],
                            'com_edital' => [
                                'type'        => 'boolean',
                                'description' => 'Se true, retorna somente projetos vinculados a um edital (ngo_grant_id != null). Se false, so projetos sem vinculo.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function businessDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'pipeline_de_clientes',
                    'description' => 'Consulta a base de clientes do tenant e a receita paga vinculada a cada um no periodo. Use SEMPRE que precisar falar de cliente, faturamento por cliente ou concentracao de receita. Se a lista vir vazia, NAO invente cliente — informe que nao ha cliente com receita no periodo.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Janela de analise em dias (default 90).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'recibos_emitidos',
                    'description' => 'Mede a formalizacao das receitas: quantas receitas pagas do periodo tem recibo emitido (com link publico) e o valor coberto. Use pra apontar receita sem comprovante — risco de formalizacao pro MEI/PJ.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Janela de analise em dias (default 90).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'prospeccao',
                    'description' => 'Consulta os leads gerados pela Prospeccao IA do tenant: contagem por status, lead score medio e os 5 melhores leads. Use pra cruzar pipeline de vendas com a base de clientes atual. Se vier vazio, NAO invente lead — sugira rodar uma busca de prospeccao.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type'        => 'string',
                                'enum'        => ['new', 'analyzed', 'contacted', 'converted', 'todos'],
                                'description' => 'Filtro por status do lead. Default "todos".',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Roteador de tools. Recebe tenantId via param (nao Auth::) pq roda
     * em fila/console fora de sessao web.
     */
    public static function execute(string $name, array $args, int $tenantId): array
    {
        Log::info('StrategyRoom/Intelligence: tool executada', [
            'name' => $name, 'tenant_id' => $tenantId, 'args' => $args,
        ]);

        return match ($name) {
            'buscar_editais_cadastrados'       => self::buscarEditaisCadastrados($args, $tenantId),
            'buscar_projetos_ativos_com_score' => self::buscarProjetosAtivosComScore($args, $tenantId),
            'pipeline_de_clientes'             => self::pipelineDeClientes($args, $tenantId),
            'recibos_emitidos'                 => self::recibosEmitidos($args, $tenantId),
            'prospeccao'                       => self::prospeccao($args, $tenantId),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    private static function buscarProjetosAtivosComScore(array $args, int $tenantId): array
    {
        $semScore  = isset($args['sem_score']) ? (bool) $args['sem_score'] : null;
        $comEdital = isset($args['com_edital']) ? (bool) $args['com_edital'] : null;

        $query = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if ($comEdital === true) {
            $query->whereNotNull('ngo_grant_id');
        } elseif ($comEdital === false) {
            $query->whereNull('ngo_grant_id');
        }

        $rows = $query->limit(20)
            ->get(['id', 'name', 'budget', 'start_date', 'end_date', 'ngo_grant_id']);

        $projetos = [];
        foreach ($rows as $p) {
            // Score financeiro mais recente por projeto
            $latest = ProjectHealthHistory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('project_id', $p->id)
                ->orderByDesc('recorded_at')
                ->first(['financial_score', 'recorded_at']);

            $hasScore = $latest !== null;
            if ($semScore === true && $hasScore) continue;
            if ($semScore === false && !$hasScore) continue;

            $projetos[] = [
                'id'                => (int) $p->id,
                'nome'              => (string) $p->name,
                'budget'            => $p->budget !== null ? (float) $p->budget : null,
                'start_date'        => $p->start_date?->toDateString(),
                'end_date'          => $p->end_date?->toDateString(),
                'dias_para_fim'     => $p->end_date
                    ? (int) now()->startOfDay()->diffInDays($p->end_date->startOfDay(), false)
                    : null,
                'ngo_grant_id'      => $p->ngo_grant_id !== null ? (int) $p->ngo_grant_id : null,
                'financial_score'   => $hasScore ? (int) $latest->financial_score : null,
                'score_recorded_at' => $hasScore ? $latest->recorded_at?->toDateString() : null,
            ];
        }

        return [
            'success' => true,
            'filtros' => ['sem_score' => $semScore, 'com_edital' => $comEdital],
            'total'   => count($projetos),
            'projetos' => $projetos,
            'instrucao_llm' => count($projetos) === 0
                ? 'Nenhum projeto ativo bate com o filtro. NAO invente projeto. Diga honestamente que nao ha projeto ativo nesse escopo.'
                : 'Voce PODE citar estes projetos pelo nome, budget, prazo e score — dados reais. Ao cruzar com editais, use o campo ngo_grant_id pra saber se o projeto ja tem vinculo. NAO cite projeto fora desta lista.',
        ];
    }

    private static function buscarEditaisCadastrados(array $args, int $tenantId): array
    {
        $status  = $args['status']  ?? 'todos';
        $ateDias = isset($args['com_deadline_ate_dias']) ? (int) $args['com_deadline_ate_dias'] : null;

        $query = NgoGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }
        if ($ateDias !== null && $ateDias > 0) {
            $query->whereNotNull('deadline')
                  ->whereBetween('deadline', [now()->toDateString(), now()->addDays($ateDias)->toDateString()]);
        }

        $rows = $query->orderByRaw('deadline IS NULL, deadline ASC')
            ->limit(20) // teto duro pra prompt nao explodir
            ->get(['id', 'title', 'agency', 'value', 'start_date', 'deadline', 'status']);

        $editais = $rows->map(function ($g) {
            return [
                'id'         => (int) $g->id,
                'titulo'     => (string) $g->title,
                'orgao'      => (string) ($g->agency ?? ''),
                'valor'      => $g->value !== null ? (float) $g->value : null,
                'start_date' => $g->start_date?->toDateString(),
                'deadline'   => $g->deadline?->toDateString(),
                'dias_para_prazo' => $g->deadline
                    ? (int) now()->startOfDay()->diffInDays($g->deadline->startOfDay(), false)
                    : null,
                'status'     => (string) ($g->status ?? ''),
            ];
        })->all();

        return [
            'success'  => true,
            'filtros'  => ['status' => $status, 'com_deadline_ate_dias' => $ateDias],
            'total'    => count($editais),
            'editais'  => $editais,
            'instrucao_llm' => count($editais) === 0
                ? 'Nao ha edital cadastrado nesse escopo. NAO invente. Seja honesto: "Nao ha edital cadastrado no sistema no filtro X". Sugira ao tenant cadastrar/monitorar.'
                : 'Voce PODE citar estes editais pelo titulo, orgao, valor e prazo — sao dados reais do sistema. NAO cite edital que nao esteja nesta lista.',
        ];
    }

    // ── Tools do modo Negocio (MEI/PJ) ────────────────────────────────────────

    private static function pipelineDeClientes(array $args, int $tenantId): array
    {
        $dias   = max(7, min(365, (int) ($args['periodo_dias'] ?? 90)));
        $inicio = now()->subDays($dias)->toDateString();

        $totalClientes = Client::withoutGlobalScopes()->where('tenant_id', $tenantId)->count();

        $porCliente = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereNotNull('client_id')
            ->where('date', '>=', $inicio)
            ->selectRaw('client_id, SUM(amount) as receita_total, MAX(date) as ultima_compra')
            ->groupBy('client_id')
            ->orderByDesc('receita_total')
            ->limit(5)
            ->get();

        $nomes = Client::withoutGlobalScopes()
            ->whereIn('id', $porCliente->pluck('client_id'))
            ->pluck('name', 'id');

        $top = $porCliente->map(fn ($r) => [
            'id'            => (int) $r->client_id,
            'nome'          => (string) ($nomes[$r->client_id] ?? 'Cliente removido'),
            'receita_total' => (float) $r->receita_total,
            'ultima_compra' => (string) $r->ultima_compra,
        ])->all();

        $comReceita = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereNotNull('client_id')
            ->where('date', '>=', $inicio)
            ->distinct()
            ->count('client_id');

        return [
            'success'              => true,
            'periodo_dias'         => $dias,
            'total_clientes'       => $totalClientes,
            'clientes_com_receita' => $comReceita,
            'top_clientes'         => $top,
            'instrucao_llm' => count($top) === 0
                ? 'Nenhum cliente com receita paga no periodo. NAO invente cliente. Diga honestamente que nao ha receita vinculada a cliente e sugira vincular lancamentos a clientes no cadastro.'
                : 'Voce PODE citar estes clientes pelo nome e receita — dados reais. NAO cite cliente fora desta lista. Se a receita estiver concentrada em 1-2 clientes, aponte o risco de dependencia.',
        ];
    }

    private static function recibosEmitidos(array $args, int $tenantId): array
    {
        $dias   = max(7, min(365, (int) ($args['periodo_dias'] ?? 90)));
        $inicio = now()->subDays($dias)->toDateString();

        $base = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('type', 'income')
            ->where('status', 'paid')
            ->where('date', '>=', $inicio);

        $totalReceitas = (clone $base)->count();
        $valorReceitas = (float) (clone $base)->sum('amount');
        $comRecibo     = (clone $base)->whereNotNull('public_receipt_token')->count();
        $valorRecibo   = (float) (clone $base)->whereNotNull('public_receipt_token')->sum('amount');

        return [
            'success'            => true,
            'periodo_dias'       => $dias,
            'receitas_pagas'     => $totalReceitas,
            'com_recibo'         => $comRecibo,
            'sem_recibo'         => $totalReceitas - $comRecibo,
            'valor_total'        => $valorReceitas,
            'valor_com_recibo'   => $valorRecibo,
            'percentual_recibo'  => $totalReceitas > 0 ? round($comRecibo / $totalReceitas * 100, 1) : 0.0,
            'instrucao_llm' => $totalReceitas === 0
                ? 'Nenhuma receita paga no periodo. NAO invente numero. Diga honestamente que nao ha receita registrada nessa janela.'
                : 'Percentuais e valores reais do sistema. Se a cobertura de recibo for baixa, aponte o risco de formalizacao e recomende emitir recibo pelas Receitas.',
        ];
    }

    private static function prospeccao(array $args, int $tenantId): array
    {
        $status = $args['status'] ?? 'todos';

        $query = Prospect::withoutGlobalScopes()->where('tenant_id', $tenantId);
        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $porStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $scoreMedio = (clone $query)->whereNotNull('lead_score')->avg('lead_score');

        $top = (clone $query)
            ->whereNotNull('lead_score')
            ->orderByDesc('lead_score')
            ->limit(5)
            ->get(['id', 'company_name', 'lead_score', 'status'])
            ->map(fn ($p) => [
                'id'           => (int) $p->id,
                'empresa'      => (string) $p->company_name,
                'lead_score'   => (int) $p->lead_score,
                'status'       => (string) $p->status,
            ])->all();

        $total = array_sum($porStatus);

        return [
            'success'     => true,
            'filtros'     => ['status' => $status],
            'total'       => $total,
            'por_status'  => $porStatus,
            'score_medio' => $scoreMedio !== null ? round((float) $scoreMedio, 1) : null,
            'prospects'   => $top,
            'instrucao_llm' => $total === 0
                ? 'Nenhum lead de prospeccao nesse escopo. NAO invente lead. Sugira rodar uma busca na Prospeccao IA pra alimentar o funil.'
                : 'Voce PODE citar estes leads pela empresa e score — dados reais. NAO cite lead fora desta lista. Leads "analyzed" com score alto e ainda nao contatados sao a prioridade natural.',
        ];
    }
}
