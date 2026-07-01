<?php

namespace App\Services\StrategyRoom;

use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1.2. Tools do Agente Financeiro.
 *
 * Duas tools complementares ao catalogo de fatos agregados do tenantContext:
 *
 * 1) analisar_transacoes_por_projeto — quebra receita/despesa por projeto
 *    ativo, permite Financeiro apontar "projeto X esta sangrando" ou
 *    "projeto Y so recebe, nao gasta". Cruza com o pipeline do
 *    Inteligencia.
 *
 * 2) historico_health_score — ultimos N snapshots por projeto, revela
 *    tendencia (subindo/estavel/caindo). Sem isso, Financeiro so ve o
 *    valor atual e nao consegue diagnosticar deterioracao.
 *
 * Ambas: sem PII individual, teto de 20 projetos por chamada,
 * withoutGlobalScopes pra funcionar em console/queue.
 */
class FinancialAgentTools
{
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analisar_transacoes_por_projeto',
                    'description' => 'Quebra receita/despesa/saldo do tenant POR PROJETO ativo, no periodo escolhido. Use pra apontar projeto especifico que esta "sangrando" (despesa >> receita), sem movimentacao (nem receita nem despesa) ou saudavel. Cruza com o dado do Inteligencia sobre projetos vinculados a edital.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Ultimos N dias considerados. Default 60. Use 30 pra tendencia recente, 90 pra visao trimestral.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'historico_health_score',
                    'description' => 'Ultimos N snapshots do score financeiro (0-100) por projeto ativo — permite ver TENDENCIA (subindo, estavel, caindo). Sem isso, so tem o valor atual (via project_health_score no catalogo). Use quando quiser dizer "score caiu de X pra Y em Z dias" ou "score estavel em N".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ultimos_n_snapshots' => [
                                'type'        => 'integer',
                                'description' => 'Quantos snapshots recentes por projeto. Default 5. Max 10.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function execute(string $name, array $args, int $tenantId): array
    {
        Log::info('StrategyRoom/Financeiro: tool executada', [
            'name' => $name, 'tenant_id' => $tenantId, 'args' => $args,
        ]);

        return match ($name) {
            'analisar_transacoes_por_projeto' => self::analisarTransacoesPorProjeto($args, $tenantId),
            'historico_health_score'          => self::historicoHealthScore($args, $tenantId),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    private static function analisarTransacoesPorProjeto(array $args, int $tenantId): array
    {
        $periodoDias = isset($args['periodo_dias']) ? max(1, (int) $args['periodo_dias']) : 60;
        $desde = now()->subDays($periodoDias)->toDateString();

        $projects = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->limit(20)
            ->get(['id', 'name', 'budget']);

        if ($projects->isEmpty()) {
            return [
                'success' => true,
                'periodo_dias' => $periodoDias,
                'total' => 0,
                'projetos' => [],
                'instrucao_llm' => 'Nao ha projeto ativo no tenant. NAO invente. Diga que o pipeline esta vazio e sugira apontar essa lacuna.',
            ];
        }

        // Agregado por (project_id, type) pra evitar N+1
        $projectIds = $projects->pluck('id')->all();
        $agg = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereIn('project_id', $projectIds)
            ->where('date', '>=', $desde)
            ->select('project_id', 'type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as n'))
            ->groupBy('project_id', 'type')
            ->get()
            ->groupBy('project_id');

        $out = [];
        foreach ($projects as $p) {
            $rows    = $agg->get($p->id, collect());
            $receita = (float) ($rows->firstWhere('type', 'income')?->total ?? 0);
            $despesa = (float) ($rows->firstWhere('type', 'expense')?->total ?? 0);
            $saldo   = $receita - $despesa;

            $status = match (true) {
                $receita === 0.0 && $despesa === 0.0 => 'sem_movimentacao',
                $despesa > 0 && $receita === 0.0     => 'so_despesa',
                $receita > 0 && $despesa === 0.0     => 'so_receita',
                $despesa > $receita                  => 'sangrando',
                default                              => 'saudavel',
            };

            $out[] = [
                'id'           => (int) $p->id,
                'nome'         => (string) $p->name,
                'budget'       => $p->budget !== null ? (float) $p->budget : null,
                'receita'      => $receita,
                'despesa'      => $despesa,
                'saldo'        => $saldo,
                'n_transacoes' => (int) $rows->sum('n'),
                'status'       => $status,
                'pct_budget_usado' => ($p->budget !== null && (float) $p->budget > 0)
                    ? round(($despesa / (float) $p->budget) * 100, 1)
                    : null,
            ];
        }

        return [
            'success'      => true,
            'periodo_dias' => $periodoDias,
            'total'        => count($out),
            'projetos'     => $out,
            'instrucao_llm' => 'Cite projeto pelo nome e status (sangrando/so_despesa/so_receita/sem_movimentacao/saudavel). NAO invente projeto fora desta lista. Se todos "sem_movimentacao", aponte estagnacao como sinal a investigar.',
        ];
    }

    private static function historicoHealthScore(array $args, int $tenantId): array
    {
        $n = isset($args['ultimos_n_snapshots'])
            ? max(2, min(10, (int) $args['ultimos_n_snapshots']))
            : 5;

        $projects = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->limit(20)
            ->get(['id', 'name']);

        if ($projects->isEmpty()) {
            return [
                'success' => true, 'total' => 0, 'projetos' => [],
                'instrucao_llm' => 'Nao ha projeto ativo. NAO invente.',
            ];
        }

        $out = [];
        foreach ($projects as $p) {
            $snaps = ProjectHealthHistory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('project_id', $p->id)
                ->orderByDesc('recorded_at')
                ->limit($n)
                ->get(['financial_score', 'recorded_at']);

            if ($snaps->isEmpty()) {
                $out[] = [
                    'id'          => (int) $p->id,
                    'nome'        => (string) $p->name,
                    'snapshots'   => [],
                    'tendencia'   => 'sem_dado',
                ];
                continue;
            }

            $scores = $snaps->pluck('financial_score')->map(fn ($s) => (int) $s)->all();
            $tendencia = self::analisarTendencia($scores); // ordem: mais recente → mais antigo

            $out[] = [
                'id'        => (int) $p->id,
                'nome'      => (string) $p->name,
                'snapshots' => $snaps->map(fn ($s) => [
                    'score'       => (int) $s->financial_score,
                    'recorded_at' => $s->recorded_at?->toDateString(),
                ])->all(),
                'tendencia' => $tendencia,
            ];
        }

        return [
            'success'  => true,
            'total'    => count($out),
            'projetos' => $out,
            'instrucao_llm' => 'Cite tendencia (subindo/estavel/caindo/sem_dado) e valores dos snapshots quando forem chave da recomendacao. NAO invente snapshot fora desta lista.',
        ];
    }

    /**
     * Recebe array de scores em ordem [mais_recente, ..., mais_antigo].
     * Compara o mais recente contra a media dos anteriores. Threshold 10.
     */
    private static function analisarTendencia(array $scores): string
    {
        if (count($scores) < 2) return count($scores) === 1 ? 'unico_snapshot' : 'sem_dado';

        $mais_recente = $scores[0];
        $anteriores   = array_slice($scores, 1);
        $media_ant    = array_sum($anteriores) / count($anteriores);
        $delta        = $mais_recente - $media_ant;

        if ($delta >= 10)  return 'subindo';
        if ($delta <= -10) return 'caindo';
        return 'estavel';
    }
}
