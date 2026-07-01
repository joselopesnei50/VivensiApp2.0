<?php

namespace App\Services\StrategyRoom;

use App\Models\NgoGrant;
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
            'buscar_editais_cadastrados' => self::buscarEditaisCadastrados($args, $tenantId),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
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
