<?php

namespace App\Services\StrategyRoom;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 4. Tools do Agente de Programas (COO).
 *
 * Regra dura de LGPD (mesma disciplina do Mobilizacao): NUNCA expor nome
 * de beneficiario/aluno individual. Risco de evasao e devolvido como
 * CONTAGEM por projeto, nunca lista nominal.
 *
 * Criterio de risco: espelho do AttendanceReportController (decisao 1c do
 * modulo Lista de Presenca) — 3 faltas puras consecutivas OU >= 30% de
 * ausencia no periodo. Justificada NAO conta como falta no percentual
 * (decisao 2b) e quebra a sequencia de faltas puras.
 */
class ProgramsAgentTools
{
    private const RISK_CONSECUTIVE    = 3;
    private const RISK_PERCENTUAL_MIN = 30.0;

    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'frequencia_e_evasao',
                    'description' => 'Frequencia agregada das aulas/atividades por projeto no periodo: total de aulas, taxa media de presenca, beneficiarios acompanhados e QUANTOS estao em risco de evasao (3 faltas seguidas ou >=30% de ausencia). Sem PII — retorna contagens, nunca nomes.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Ultimos N dias considerados. Default 90.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'execucao_de_tarefas',
                    'description' => 'Execucao operacional dos projetos ativos: tarefas por status, tarefas vencidas (prazo estourado e nao concluidas) e percentual de conclusao por projeto. Sinal de "o projeto esta entregando na ponta?".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Considera tarefas criadas nos ultimos N dias. Default 90.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function execute(string $name, array $args, int $tenantId): array
    {
        Log::info('StrategyRoom/Programas: tool executada', [
            'name' => $name, 'tenant_id' => $tenantId, 'args' => $args,
        ]);

        return match ($name) {
            'frequencia_e_evasao'  => self::frequenciaEEvasao($args, $tenantId),
            'execucao_de_tarefas'  => self::execucaoDeTarefas($args, $tenantId),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    private static function frequenciaEEvasao(array $args, int $tenantId): array
    {
        $periodoDias = isset($args['periodo_dias']) ? max(1, (int) $args['periodo_dias']) : 90;
        $desde       = now()->subDays($periodoDias)->toDateString();

        $sessions = ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $desde)
            ->get(['id', 'project_id', 'date']);

        if ($sessions->isEmpty()) {
            return [
                'success'       => true,
                'periodo_dias'  => $periodoDias,
                'total_aulas'   => 0,
                'projetos'      => [],
                'instrucao_llm' => 'Nenhuma aula/atividade registrada no periodo. Se o tenant tem projetos com beneficiarios, o modulo de Lista de Presenca esta sem uso — mencione como lacuna de monitoramento, nao invente frequencia.',
            ];
        }

        $sessionById = $sessions->keyBy('id');

        $attendances = ClassAttendance::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('class_session_id', $sessions->pluck('id'))
            ->get(['class_session_id', 'project_person_id', 'status']);

        $projectNames = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->pluck('name', 'id');

        $projetos = [];

        foreach ($attendances->groupBy(fn ($a) => $sessionById[$a->class_session_id]->project_id) as $projectId => $items) {
            $presencas    = $items->where('status', 'presente')->count();
            $faltas       = $items->where('status', 'falta')->count();
            $justificadas = $items->where('status', 'falta_justificada')->count();

            // Decisao 2b: justificada fora do denominador do %.
            $denom = $presencas + $faltas;
            $pct   = $denom > 0 ? round(($presencas / $denom) * 100, 1) : null;

            $emRisco       = 0;
            $beneficiarios = 0;

            foreach ($items->groupBy('project_person_id') as $pessoal) {
                $beneficiarios++;

                $p = $pessoal->where('status', 'presente')->count();
                $f = $pessoal->where('status', 'falta')->count();

                $d       = $p + $f;
                $pctFalta = $d > 0 ? ($f / $d) * 100 : 0;

                // Faltas puras consecutivas a partir da aula mais recente;
                // presente OU justificada quebram a sequencia.
                $consecutivas = 0;
                $ordenado = $pessoal->sortByDesc(
                    fn ($a) => $sessionById[$a->class_session_id]->date?->toDateString() ?? ''
                );
                foreach ($ordenado as $reg) {
                    if ($reg->status !== 'falta') break;
                    $consecutivas++;
                }

                if ($consecutivas >= self::RISK_CONSECUTIVE || $pctFalta >= self::RISK_PERCENTUAL_MIN) {
                    $emRisco++;
                }
            }

            $projetos[] = [
                'projeto'             => (string) ($projectNames[$projectId] ?? "Projeto #{$projectId}"),
                'aulas'               => $sessions->where('project_id', $projectId)->count(),
                'beneficiarios'       => $beneficiarios,
                'taxa_presenca_pct'   => $pct,
                'faltas_justificadas' => $justificadas,
                'em_risco_evasao'     => $emRisco,
            ];
        }

        $totalRisco = array_sum(array_column($projetos, 'em_risco_evasao'));

        return [
            'success'              => true,
            'periodo_dias'         => $periodoDias,
            'total_aulas'          => $sessions->count(),
            'total_em_risco'       => $totalRisco,
            'projetos'             => $projetos,
            'criterio_risco'       => '3 faltas puras seguidas OU >=30% de ausencia no periodo (justificada nao conta)',
            'instrucao_llm'        => $totalRisco > 0
                ? 'Ha beneficiarios em risco de evasao — este e o sinal MAIS importante da sua analise. Cite o numero e o projeto. NUNCA cite nome de beneficiario (voce nem tem esse dado).'
                : 'Frequencia sob controle no periodo. Cite taxa_presenca_pct dos projetos com mais aulas se relevante.',
        ];
    }

    private static function execucaoDeTarefas(array $args, int $tenantId): array
    {
        $periodoDias = isset($args['periodo_dias']) ? max(1, (int) $args['periodo_dias']) : 90;
        $desde       = now()->subDays($periodoDias);

        $projects = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->pluck('name', 'id');

        if ($projects->isEmpty()) {
            return [
                'success'       => true,
                'periodo_dias'  => $periodoDias,
                'projetos'      => [],
                'instrucao_llm' => 'Nenhum projeto ativo. Sem execucao pra analisar — seja honesto sobre isso.',
            ];
        }

        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('project_id', $projects->keys())
            ->where('created_at', '>=', $desde)
            ->get(['project_id', 'status', 'due_date']);

        $concluidos = ['completed', 'done'];
        $projetos   = [];

        foreach ($projects as $projectId => $nome) {
            $doProjeto = $tasks->where('project_id', $projectId);
            $total     = $doProjeto->count();
            $feitas    = $doProjeto->whereIn('status', $concluidos)->count();
            $vencidas  = $doProjeto
                ->filter(fn ($t) => $t->due_date !== null
                    && $t->due_date->isPast()
                    && !in_array($t->status, array_merge($concluidos, ['cancelled']), true))
                ->count();

            $projetos[] = [
                'projeto'           => (string) $nome,
                'total_tarefas'     => $total,
                'concluidas'        => $feitas,
                'vencidas'          => $vencidas,
                'pct_conclusao'     => $total > 0 ? round(($feitas / $total) * 100, 1) : null,
            ];
        }

        $totalVencidas = array_sum(array_column($projetos, 'vencidas'));
        $totalTarefas  = array_sum(array_column($projetos, 'total_tarefas'));

        return [
            'success'        => true,
            'periodo_dias'   => $periodoDias,
            'total_tarefas'  => $totalTarefas,
            'total_vencidas' => $totalVencidas,
            'projetos'       => $projetos,
            'instrucao_llm'  => $totalTarefas === 0
                ? 'Projetos ativos sem nenhuma tarefa registrada no periodo — sinal de que a execucao nao esta sendo gerida no painel. Mencione a lacuna, nao invente andamento.'
                : ($totalVencidas > 0
                    ? 'Ha tarefas vencidas — cite quantas e em qual projeto. Vencida = prazo estourado sem conclusao.'
                    : 'Execucao em dia. Cite pct_conclusao dos maiores projetos se relevante.'),
        ];
    }
}
