<?php

namespace App\Services\StrategyRoom;

use App\Models\NgoGrant;
use App\Models\Project;
use App\Models\ProjectHealthHistory;
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
     * Definicoes no formato OpenAI/DeepSeek tools.
     */
    public static function definitions(): array
    {
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
}
