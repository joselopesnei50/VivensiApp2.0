<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Etapas de Projeto (ProjectStage) — Fase 2.
 *
 * Endpoints REST sob /projects/{project}/stages/*. Multi-painel: rotas
 * globais consumidas por NGO/Manager/Common; controlador confia no global
 * scope de tenant + valida cross-project em cada resolvedor.
 *
 * O status STATUS_ATRASADA nao existe — "atrasada" e computado pela regra
 * determinista em Project::getCurrentStageAttribute(). Aqui so guardamos
 * pending / in_progress / completed / cancelled.
 */
class ProjectStageController extends Controller
{
    private const ALLOWED_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

    public function index(Request $request, Project $project)
    {
        $this->assertProjectTenant($project);

        $stagesModels = $project->stages()->get();
        $stages       = $stagesModels->map(fn (ProjectStage $s) => $this->present($s));

        if ($request->wantsJson()) {
            return response()->json([
                'project_id'        => $project->id,
                'stages'            => $stages->all(),
                'calculated_budget' => $project->calculated_budget,
                'current_stage_id'  => $project->current_stage?->id,
            ]);
        }

        return view('projects.stages.index', [
            'project'          => $project,
            'stages'           => $stagesModels,
            'currentStage'    => $project->current_stage,
            'calculatedBudget' => $project->calculated_budget,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->assertProjectTenant($project);
        $validated = $this->validateStage($request);

        $stage = DB::transaction(function () use ($project, $validated) {
            $nextOrder = (int) ($project->stages()->max('order') ?? 0) + 1;

            return ProjectStage::create(array_merge($validated, [
                'tenant_id'  => $project->tenant_id,
                'project_id' => $project->id,
                'order'      => $validated['order'] ?? $nextOrder,
                'status'     => $validated['status'] ?? 'pending',
            ]));
        });

        Log::info('PROJECT_STAGE_CREATED', [
            'stage_id'   => $stage->id,
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
        ]);

        return response()->json(['stage' => $this->present($stage)], 201);
    }

    /**
     * Pagina de espaco de trabalho da etapa: tarefas + financeiro da etapa,
     * overview de valor previsto x realizado, botoes de criar tarefa/lancamento
     * ja com stage_id + project_id pre-selecionados.
     */
    public function show(Request $request, Project $project, int $stageId)
    {
        $stage = $this->resolveStage($project, $stageId);

        // CASE cross-DB (MySQL + SQLite): FIELD() e so MySQL.
        $tasks = $stage->tasks()
            ->with('assignee:id,name')
            ->orderByRaw("CASE status
                WHEN 'in_progress' THEN 1
                WHEN 'doing'       THEN 2
                WHEN 'todo'        THEN 3
                WHEN 'pending'     THEN 4
                WHEN 'blocked'     THEN 5
                WHEN 'done'        THEN 6
                WHEN 'completed'   THEN 7
                WHEN 'cancelled'   THEN 8
                ELSE 9 END")
            ->orderBy('due_date')
            ->get();

        $transactions = $stage->transactions()
            ->with('category:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $categories = \DB::table('financial_categories')
            ->where('tenant_id', $project->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $assignableUsers = \App\Models\User::withoutGlobalScope('tenant')
            ->where('tenant_id', $project->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('projects.stages.show', [
            'project'         => $project,
            'stage'           => $stage,
            'tasks'           => $tasks,
            'transactions'    => $transactions,
            'categories'      => $categories,
            'assignableUsers' => $assignableUsers,
            'financial'       => $stage->financial_summary,
        ]);
    }

    public function update(Request $request, Project $project, int $stageId): JsonResponse
    {
        $stage = $this->resolveStage($project, $stageId);
        $validated = $this->validateStage($request);

        $stage->update($validated);

        return response()->json(['stage' => $this->present($stage->fresh())]);
    }

    public function destroy(Project $project, int $stageId): JsonResponse
    {
        $stage = $this->resolveStage($project, $stageId);
        $stage->delete();

        Log::info('PROJECT_STAGE_DELETED', [
            'stage_id'   => $stage->id,
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
        ]);

        return response()->json(['deleted' => true]);
    }

    /**
     * Reordena stages atomicamente. Aceita [{id, order}, ...]. IDs de outros
     * projetos/tenants sao silenciosamente ignorados (proof IDOR).
     */
    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->assertProjectTenant($project);

        $validated = $request->validate([
            'stages'         => 'required|array|min:1|max:100',
            'stages.*.id'    => 'required|integer',
            'stages.*.order' => 'required|integer|min:0|max:999',
        ]);

        $updated = 0;
        DB::transaction(function () use ($project, $validated, &$updated) {
            foreach ($validated['stages'] as $item) {
                $affected = ProjectStage::where('id', $item['id'])
                    ->where('tenant_id', $project->tenant_id)
                    ->where('project_id', $project->id)
                    ->update(['order' => (int) $item['order']]);
                $updated += $affected;
            }
        });

        return response()->json(['updated' => $updated]);
    }

    /**
     * Marca etapa como completada e, se houver `planned_value > 0`, cria
     * uma Transaction sugerida (approval_status='pending', status='pending').
     * Nao lanca receita — gestor confirma via fluxo de aprovacao.
     */
    public function complete(Project $project, int $stageId): JsonResponse
    {
        $stage = $this->resolveStage($project, $stageId);

        $suggestedTransactionId = null;

        DB::transaction(function () use ($stage, $project, &$suggestedTransactionId) {
            $stage->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            $planned = (float) $stage->planned_value;
            if ($planned > 0) {
                $tx = Transaction::create([
                    'tenant_id'       => $project->tenant_id,
                    'project_id'      => $project->id,
                    'stage_id'        => $stage->id,
                    'description'     => 'Sugestao: recebimento da etapa "' . $stage->title . '"',
                    'amount'          => $planned,
                    'type'            => 'income',
                    'date'            => now()->toDateString(),
                    'status'          => 'pending',
                    'approval_status' => 'pending',
                ]);
                $suggestedTransactionId = $tx->id;
            }
        });

        Log::info('PROJECT_STAGE_COMPLETED', [
            'stage_id'                 => $stage->id,
            'project_id'               => $project->id,
            'user_id'                  => auth()->id(),
            'suggested_transaction_id' => $suggestedTransactionId,
        ]);

        return response()->json([
            'stage' => $this->present($stage->fresh()),
            'suggested_transaction_id' => $suggestedTransactionId,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function assertProjectTenant(Project $project): void
    {
        abort_unless(
            (int) auth()->user()->tenant_id === (int) $project->tenant_id,
            404
        );
    }

    private function resolveStage(Project $project, int $stageId): ProjectStage
    {
        $this->assertProjectTenant($project);

        $stage = ProjectStage::where('id', $stageId)
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->first();

        abort_unless($stage, 404);
        return $stage;
    }

    private function validateStage(Request $request): array
    {
        return $request->validate([
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string|max:5000',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'planned_value' => 'nullable|numeric|min:0|max:99999999.99',
            'order'         => 'nullable|integer|min:0|max:999',
            'status'        => 'nullable|in:' . implode(',', self::ALLOWED_STATUSES),
            'target_date'   => 'nullable|date',
        ]);
    }

    private function present(ProjectStage $stage): array
    {
        return [
            'id'            => $stage->id,
            'title'         => $stage->title,
            'description'   => $stage->description,
            'start_date'    => $stage->start_date?->toDateString(),
            'end_date'      => $stage->end_date?->toDateString(),
            'planned_value' => (float) $stage->planned_value,
            'order'         => (int) $stage->order,
            'status'        => $stage->status,
            'completed_at'  => $stage->completed_at?->toIso8601String(),
            'target_date'   => $stage->target_date?->toDateString(),
            'is_current'    => $stage->is_current,
        ];
    }
}
