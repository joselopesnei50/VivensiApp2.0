<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectGoal;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fase 1 do modulo de Planejamento estrategico de projetos.
 * Toda a superficie esta atras de config('planning.enabled') — quando
 * false, retorna 404 em vez de expor a feature em desenvolvimento.
 */
class ProjectPlanningController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('planning.enabled'), 404);
    }

    private function loadProject(int $id): Project
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $project = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Employees so podem ver se forem membros do projeto — mesmo
        // padrao de ProjectController::show.
        $user = auth()->user();
        if (!in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            abort_unless(
                ProjectMember::where('tenant_id', $tenantId)
                    ->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->exists(),
                403
            );
        }

        return $project;
    }

    private function abortIfCannotWrite(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true),
            403
        );
    }

    public function show(int $id)
    {
        $this->ensureEnabled();
        $project = $this->loadProject($id);

        $goals = $project->goals()->get();
        $milestones = $project->milestones()->get();

        return view('projects.planning', compact('project', 'goals', 'milestones'));
    }

    // ── Apresentacao Institucional + Objetivos ────────────────────────────

    public function updateOverview(Request $request, int $id)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $validated = $request->validate([
            'presentation'        => ['nullable', 'string', 'max:5000'],
            'justification'       => ['nullable', 'string', 'max:5000'],
            'context'             => ['nullable', 'string', 'max:5000'],
            'target_audience'     => ['nullable', 'string', 'max:5000'],
            'general_objective'   => ['nullable', 'string', 'max:5000'],
            'specific_objectives' => ['nullable', 'array', 'max:50'],
            'specific_objectives.*' => ['nullable', 'string', 'max:500'],
        ]);

        // Filtra vazios da lista de objetivos especificos para nao guardar lixo
        if (!empty($validated['specific_objectives'])) {
            $validated['specific_objectives'] = array_values(array_filter(
                array_map('trim', $validated['specific_objectives']),
                fn($v) => $v !== ''
            ));
            if (empty($validated['specific_objectives'])) {
                $validated['specific_objectives'] = null;
            }
        }

        $project->update($validated);

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return redirect()->route('projects.planning.show', $project->id)
            ->with('success', 'Apresentacao e objetivos atualizados.');
    }

    // ── Metas ─────────────────────────────────────────────────────────────

    public function storeGoal(Request $request, int $id)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'indicator'   => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'due_date'    => ['nullable', 'date'],
        ]);

        ProjectGoal::create(array_merge($validated, [
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
            'status'     => $validated['status'] ?? 'not_started',
        ]));

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Meta cadastrada.');
    }

    public function updateGoal(Request $request, int $id, int $goalId)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $goal = ProjectGoal::where('id', $goalId)
            ->where('project_id', $project->id)
            ->where('tenant_id', $project->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'indicator'   => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'due_date'    => ['nullable', 'date'],
        ]);

        $goal->update($validated);

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Meta atualizada.');
    }

    public function destroyGoal(int $id, int $goalId)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $goal = ProjectGoal::where('id', $goalId)
            ->where('project_id', $project->id)
            ->where('tenant_id', $project->tenant_id)
            ->firstOrFail();

        $goal->delete();

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Meta removida.');
    }

    // ── Marcos ────────────────────────────────────────────────────────────

    public function storeMilestone(Request $request, int $id)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_date' => ['required', 'date'],
            'status'      => ['nullable', Rule::in(['pending', 'reached', 'missed'])],
        ]);

        $data = array_merge($validated, [
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
            'status'     => $validated['status'] ?? 'pending',
        ]);
        if (($data['status'] ?? 'pending') === 'reached') {
            $data['completed_at'] = now();
        }

        ProjectMilestone::create($data);

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Marco planejado.');
    }

    public function updateMilestone(Request $request, int $id, int $milestoneId)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $milestone = ProjectMilestone::where('id', $milestoneId)
            ->where('project_id', $project->id)
            ->where('tenant_id', $project->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_date' => ['sometimes', 'required', 'date'],
            'status'      => ['nullable', Rule::in(['pending', 'reached', 'missed'])],
        ]);

        // Quando o usuario marca como 'reached', registra o timestamp
        // automaticamente. Volta a null se mudar para 'pending' (revisao).
        if (array_key_exists('status', $validated)) {
            if ($validated['status'] === 'reached' && !$milestone->completed_at) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] === 'pending') {
                $validated['completed_at'] = null;
            }
        }

        $milestone->update($validated);

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Marco atualizado.');
    }

    public function destroyMilestone(int $id, int $milestoneId)
    {
        $this->ensureEnabled();
        $this->abortIfCannotWrite();
        $project = $this->loadProject($id);

        $milestone = ProjectMilestone::where('id', $milestoneId)
            ->where('project_id', $project->id)
            ->where('tenant_id', $project->tenant_id)
            ->firstOrFail();

        $milestone->delete();

        $this->projectService->flushCache((int) $project->tenant_id, (int) $project->id);

        return back()->with('success', 'Marco removido.');
    }
}
