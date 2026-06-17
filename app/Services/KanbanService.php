<?php

namespace App\Services;

use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\Tenant;
use App\Models\TenantOperationalProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * KanbanService — Fase 3 (item 2.2 do roadmap).
 *
 * Encapsula a regra do Kanban geral por tenant: provisiona o board padrão
 * sob demanda com colunas vindas dos templates por Perfil Operacional,
 * cria/move cards mantendo posições compactas e isolamento por tenant.
 */
class KanbanService
{
    /** Gap entre posições — facilita reorder sem reescrever vizinhos. */
    public const POSITION_GAP = 1024;

    /**
     * Retorna o board default do tenant, criando-o sob demanda na primeira
     * visita ao Kanban. Colunas seguem o template do Perfil Operacional;
     * sem perfil cai no template 'outro'.
     */
    public function ensureDefaultBoard(Tenant $tenant, ?User $creator = null): KanbanBoard
    {
        $existing = KanbanBoard::where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($tenant, $creator) {
            $board = KanbanBoard::create([
                'tenant_id'  => $tenant->id,
                'created_by' => $creator?->id,
                'name'       => 'Kanban Geral',
                'color'      => '#6366f1',
                'is_default' => true,
            ]);

            $perfilSvc = app(PerfilOperacionalService::class);
            $template  = $this->columnsTemplateForCategoria($perfilSvc->getCategoria($tenant));

            foreach ($template as $i => $col) {
                KanbanColumn::create([
                    'tenant_id' => $tenant->id,
                    'board_id'  => $board->id,
                    'name'      => $col['name'],
                    'color'     => $col['color'] ?? null,
                    'position'  => ($i + 1) * self::POSITION_GAP,
                ]);
            }

            return $board->fresh('columns');
        });
    }

    /**
     * Cria um card no fim da coluna alvo. position = (último position) + GAP.
     */
    public function createCard(KanbanColumn $column, array $attributes, ?User $creator = null): KanbanCard
    {
        $tail = (int) KanbanCard::where('column_id', $column->id)
            ->whereNull('archived_at')
            ->max('position');

        return KanbanCard::create(array_merge([
            'tenant_id'  => $column->tenant_id,
            'column_id'  => $column->id,
            'created_by' => $creator?->id,
            'position'   => $tail + self::POSITION_GAP,
        ], $attributes));
    }

    /**
     * Move um card pra coluna alvo numa posição relativa a um conjunto de
     * IDs já ordenados (ordered_ids vindos do front após drag-and-drop).
     * Recalcula positions em gaps de POSITION_GAP — fica linear e estável.
     *
     * @param array<int,int> $orderedCardIds Lista dos cards (incluindo o
     *   movido) na ordem final desejada dentro da coluna alvo.
     */
    public function moveCard(KanbanCard $card, KanbanColumn $targetColumn, array $orderedCardIds): KanbanCard
    {
        $this->ensureSameTenant($card, $targetColumn);

        if (!in_array($card->id, $orderedCardIds, true)) {
            throw new InvalidArgumentException('orderedCardIds deve incluir o card sendo movido.');
        }

        return DB::transaction(function () use ($card, $targetColumn, $orderedCardIds) {
            // Atualiza a coluna do card movido (mesmo que continue na coluna atual,
            // a posição precisa recalcular).
            $card->column_id = $targetColumn->id;
            $card->save();

            foreach ($orderedCardIds as $index => $cardId) {
                KanbanCard::where('id', $cardId)
                    ->where('tenant_id', $targetColumn->tenant_id)
                    ->update([
                        'column_id' => $targetColumn->id,
                        'position'  => ($index + 1) * self::POSITION_GAP,
                    ]);
            }

            return $card->fresh();
        });
    }

    public function archiveCard(KanbanCard $card): KanbanCard
    {
        $card->update(['archived_at' => now()]);
        return $card->fresh();
    }

    /**
     * Templates de colunas por categoria operacional. Pura — sem DB.
     *
     * @return list<array{name:string,color:?string}>
     */
    public function columnsTemplateForCategoria(string $categoria): array
    {
        return match ($categoria) {
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL => [
                ['name' => 'Captação',      'color' => '#94a3b8'],
                ['name' => 'Engajado',      'color' => '#3b82f6'],
                ['name' => 'Mobilizador',   'color' => '#8b5cf6'],
                ['name' => 'Confirmado',    'color' => '#10b981'],
            ],
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL => [
                ['name' => 'Contato',       'color' => '#94a3b8'],
                ['name' => 'Em diálogo',    'color' => '#3b82f6'],
                ['name' => 'Apoiando',      'color' => '#10b981'],
                ['name' => 'Liderança',     'color' => '#f59e0b'],
            ],
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL => [
                ['name' => 'Ideia',         'color' => '#94a3b8'],
                ['name' => 'Pré-produção',  'color' => '#3b82f6'],
                ['name' => 'Captação',      'color' => '#f59e0b'],
                ['name' => 'Em execução',   'color' => '#8b5cf6'],
                ['name' => 'Finalizado',    'color' => '#10b981'],
            ],
            TenantOperationalProfile::CATEGORIA_PROJETO_EMPRESARIAL => [
                ['name' => 'Backlog',       'color' => '#94a3b8'],
                ['name' => 'Em andamento',  'color' => '#3b82f6'],
                ['name' => 'Em revisão',    'color' => '#f59e0b'],
                ['name' => 'Concluído',     'color' => '#10b981'],
            ],
            default => [
                ['name' => 'A fazer',       'color' => '#94a3b8'],
                ['name' => 'Em andamento',  'color' => '#3b82f6'],
                ['name' => 'Concluído',     'color' => '#10b981'],
            ],
        };
    }

    /**
     * Cards prontos pra renderizar no board, escopados ao tenant e agrupados
     * por coluna em ordem.
     *
     * @return Collection<int,KanbanColumn>
     */
    public function loadBoardForRender(KanbanBoard $board): Collection
    {
        return $board->columns()
            ->with(['cards' => fn ($q) => $q->orderBy('position')])
            ->get();
    }

    private function ensureSameTenant(KanbanCard $card, KanbanColumn $column): void
    {
        if ($card->tenant_id !== $column->tenant_id) {
            throw new InvalidArgumentException('Operação cross-tenant negada.');
        }
    }
}
