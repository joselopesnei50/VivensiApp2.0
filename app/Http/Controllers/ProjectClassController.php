<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectClassEnrollment;
use App\Models\ProjectPerson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Turmas dentro de Projetos NGO. Camada intermediaria entre Project e
 * ClassSession — professor cria a turma UMA vez com defaults + matriculados
 * fixos, gera sessoes (chamadas dos dias) automaticamente.
 *
 * Todas rotas sob /projects/{project}/classes/* com auth+subscription.
 * Global scope de tenant garante isolamento; resolveClass() filtra manual
 * por (project_id, tenant_id) e retorna 404 pra cross-tenant.
 */
class ProjectClassController extends Controller
{
    private const ROLES_WRITE = ['manager', 'super_admin', 'ngo', 'common'];

    public function index(Project $project): View
    {
        $this->assertCanView($project);
        $classes = ProjectClass::where('project_id', $project->id)
            ->with('teacher:id,name')
            ->withCount(['activeEnrollments as students_count', 'sessions as sessions_count'])
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();

        return view('projects.classes.index', compact('project', 'classes'));
    }

    public function create(Project $project): View
    {
        $this->assertCanWrite($project);
        $teachers = $this->teachersFor($project);
        return view('projects.classes.create', compact('project', 'teachers'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->assertCanWrite($project);
        $validated = $this->validateClass($request);

        $class = ProjectClass::create(array_merge($validated, [
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
        ]));

        Log::info('PROJECT_CLASS_CREATED', [
            'class_id'   => $class->id,
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
        ]);

        return redirect()
            ->route('projects.classes.show', [$project->id, $class->id])
            ->with('success', 'Turma criada. Agora matricule os alunos e gere as chamadas.');
    }

    public function show(Project $project, int $classId): View
    {
        $this->assertCanView($project);
        $class = $this->resolveClass($project, $classId);

        $class->load(['teacher:id,name', 'enrollments.person:id,name']);

        $enrolledIds = $class->enrollments->pluck('project_person_id')->all();
        $availablePeople = ProjectPerson::withoutGlobalScope('tenant')
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->where('enrollment_status', 'ativo')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sessions = ClassSession::withoutGlobalScope('tenant')
            ->where('tenant_id', $project->tenant_id)
            ->where('project_class_id', $class->id)
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return view('projects.classes.show', compact('project', 'class', 'availablePeople', 'sessions'));
    }

    public function edit(Project $project, int $classId): View
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);
        $teachers = $this->teachersFor($project);
        return view('projects.classes.edit', compact('project', 'class', 'teachers'));
    }

    public function update(Request $request, Project $project, int $classId): RedirectResponse
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);
        $validated = $this->validateClass($request);
        $class->update($validated);

        return redirect()
            ->route('projects.classes.show', [$project->id, $class->id])
            ->with('success', 'Turma atualizada.');
    }

    public function destroy(Project $project, int $classId): RedirectResponse
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);
        $class->delete();

        return redirect()
            ->route('projects.classes.index', $project->id)
            ->with('success', 'Turma excluída. Chamadas existentes ficaram como avulsas.');
    }

    // ── Matricula bulk ───────────────────────────────────────────────────────

    public function enrollBulk(Request $request, Project $project, int $classId): RedirectResponse
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);

        $validated = $request->validate([
            'person_ids'   => 'required|array|min:1|max:200',
            'person_ids.*' => 'integer|distinct|exists:project_people,id',
        ]);

        $enrolled = 0;
        DB::transaction(function () use ($class, $project, $validated, &$enrolled) {
            foreach ($validated['person_ids'] as $personId) {
                $person = ProjectPerson::withoutGlobalScope('tenant')
                    ->where('id', $personId)
                    ->where('tenant_id', $project->tenant_id)
                    ->where('project_id', $project->id)
                    ->first();

                if (!$person) {
                    continue; // Ignora IDs de outro projeto/tenant (proof IDOR)
                }

                $exists = ProjectClassEnrollment::withoutGlobalScope('tenant')
                    ->where('project_class_id', $class->id)
                    ->where('project_person_id', $personId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                ProjectClassEnrollment::create([
                    'tenant_id'         => $project->tenant_id,
                    'project_class_id'  => $class->id,
                    'project_person_id' => $personId,
                    'enrolled_at'       => now(),
                    'status'            => ProjectClassEnrollment::STATUS_ATIVO,
                ]);
                $enrolled++;
            }
        });

        return back()->with('success', "{$enrolled} aluno(s) matriculado(s).");
    }

    public function unenroll(Project $project, int $classId, int $enrollmentId): RedirectResponse
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);

        $enrollment = ProjectClassEnrollment::withoutGlobalScope('tenant')
            ->where('id', $enrollmentId)
            ->where('tenant_id', $project->tenant_id)
            ->where('project_class_id', $class->id)
            ->first();

        abort_unless($enrollment, 404);

        $enrollment->update([
            'status'        => ProjectClassEnrollment::STATUS_SAIU,
            'unenrolled_at' => now(),
        ]);

        return back()->with('success', 'Aluno desmatriculado.');
    }

    // ── Geracao de sessoes (chamadas) ────────────────────────────────────────

    public function generateSessions(Request $request, Project $project, int $classId): RedirectResponse
    {
        $this->assertCanWrite($project);
        $class = $this->resolveClass($project, $classId);

        $validated = $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $from = Carbon::parse($validated['from']);
        $to   = Carbon::parse($validated['to']);

        $dates = $class->computeSessionDates($from, $to);

        if ($dates->isEmpty()) {
            return back()->with('warning', 'Nenhuma data válida — configure os dias da semana da turma primeiro.');
        }

        $created = 0;
        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');

            $exists = ClassSession::withoutGlobalScope('tenant')
                ->where('tenant_id', $project->tenant_id)
                ->where('project_class_id', $class->id)
                ->whereDate('date', $dateStr)
                ->exists();

            if ($exists) {
                continue;
            }

            ClassSession::create([
                'tenant_id'         => $project->tenant_id,
                'project_id'        => $project->id,
                'project_class_id'  => $class->id,
                'title'             => $class->name,
                'date'              => $dateStr,
                'start_time'        => $class->default_start_time,
                'end_time'          => $class->default_end_time,
                'teacher_user_id'   => $class->default_teacher_user_id,
                'mode'              => $class->default_mode ?? 'fechada',
            ]);
            $created++;
        }

        Log::info('PROJECT_CLASS_SESSIONS_GENERATED', [
            'class_id'   => $class->id,
            'project_id' => $project->id,
            'created'    => $created,
            'user_id'    => auth()->id(),
        ]);

        return back()->with('success', "{$created} chamada(s) gerada(s) para o período.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve manualmente ProjectClass filtrando por tenant+project. Retorna
     * 404 se turma nao existir ou for de outro projeto/tenant. Substitui o
     * Route Model Binding padrao (que teve issue com {projectClass} nomeado).
     *
     * Tambem faz defensive check de tenant_id do project vs user autenticado
     * — em contexto teste (runningInConsole) o global scope de BelongsToTenant
     * e bypassado, entao Project::find nao filtra cross-tenant.
     */
    /**
     * Leitura: gestores veem qualquer projeto do tenant; demais roles só se
     * forem ProjectMember — mesmo padrão de ProjectController::show.
     */
    private function assertCanView(Project $project): void
    {
        abort_unless((int) auth()->user()->tenant_id === (int) $project->tenant_id, 404);

        $user = auth()->user();
        if (in_array($user->role, self::ROLES_WRITE, true)) {
            return;
        }

        abort_unless(
            \App\Models\ProjectMember::where('tenant_id', $project->tenant_id)
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists(),
            403
        );
    }

    private function assertCanWrite(Project $project): void
    {
        abort_unless((int) auth()->user()->tenant_id === (int) $project->tenant_id, 404);
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);
    }

    private function resolveClass(Project $project, int $classId): ProjectClass
    {
        // Defense: user autenticado nao pode ver project de outro tenant.
        abort_unless((int) auth()->user()->tenant_id === (int) $project->tenant_id, 404);

        $class = ProjectClass::withoutGlobalScope('tenant')
            ->where('id', $classId)
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->first();

        abort_unless($class, 404);
        return $class;
    }

    private function teachersFor(Project $project): \Illuminate\Support\Collection
    {
        return User::where('tenant_id', $project->tenant_id)
            ->whereIn('role', ['ngo', 'manager', 'common'])
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function validateClass(Request $request): array
    {
        return $request->validate([
            'name'                    => 'required|string|max:200',
            'description'             => 'nullable|string|max:2000',
            'default_teacher_user_id' => [
                'nullable',
                'integer',
                // Sem o filtro de tenant dava pra apontar user de outro tenant
                // como professor (e vazar o nome dele via teacher:id,name).
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where(fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
            ],
            'default_mode'            => 'nullable|in:fechada,aberta',
            'default_start_time'      => 'nullable|date_format:H:i',
            'default_end_time'        => 'nullable|date_format:H:i|after:default_start_time',
            'weekdays'                => 'nullable|array|max:7',
            'weekdays.*'              => 'integer|between:1,7|distinct',
            'start_date'              => 'nullable|date',
            'end_date'                => 'nullable|date|after_or_equal:start_date',
            'max_students'            => 'nullable|integer|min:1|max:1000',
            'status'                  => 'nullable|in:ativo,encerrado',
            'notes'                   => 'nullable|string|max:5000',
        ]);
    }
}
