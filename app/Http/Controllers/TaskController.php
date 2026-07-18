<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $userId   = auth()->id();

        $tasks = Task::where('tenant_id', $tenantId)
                     ->where(function ($q) use ($userId) {
                         $q->where('assigned_to', $userId)
                           ->orWhere('created_by', $userId);
                     })
                     ->orderBy('due_date', 'asc')
                     ->paginate(20);

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $user = auth()->user();

        if (!in_array($user->role, ['manager', 'ngo', 'super_admin'])) {
            $projects = collect();
            $users    = collect([$user]);
        } else {
            $projects = Project::where('tenant_id', $user->tenant_id)
                ->orderBy('name')
                ->get();

            $usersQ = User::where('tenant_id', $user->tenant_id);

            if ($user->isManager()) {
                $usersQ->whereIn('role', ['employee', 'manager']);
            } elseif ($user->isNgo() || (($user->tenant?->type ?? null) === 'ngo')) {
                $usersQ->whereNotIn('role', ['super_admin']);
            }

            $users = $usersQ->orderBy('name')->get();
        }

        return view('tasks.create', compact('projects', 'users'));
    }

    public function kanban($projectId, \Illuminate\Http\Request $request = null)
    {
        $request  = $request ?? request();
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        $project = Project::where('id', $projectId)
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        $canManageAll = in_array($user->role, ['manager', 'super_admin', 'ngo'], true);
        if (!$canManageAll) {
            $isMember = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($isMember, 403);
        }

        // Filtro opcional por etapa: `?stage_id=X`. Anti-IDOR: stage precisa
        // pertencer ao MESMO projeto (senao ignora o filtro).
        $stageFilter = null;
        if ($request->filled('stage_id')) {
            $stageFilter = \App\Models\ProjectStage::withoutGlobalScope('tenant')
                ->where('id', (int) $request->input('stage_id'))
                ->where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->first(['id', 'title', 'status']);
        }

        $tasksQuery = Task::where('project_id', $projectId)
                     ->where('tenant_id', $tenantId)
                     ->with('assignee:id,name')
                     ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
                     ->orderBy('due_date')
                     ->orderBy('created_at', 'desc');
        if ($stageFilter) {
            $tasksQuery->where('stage_id', $stageFilter->id);
        }
        $tasks = $tasksQuery->get();

        $kanban = [
            'todo'  => $tasks->whereIn('status', ['todo', 'pending', 'blocked']),
            'doing' => $tasks->whereIn('status', ['doing', 'in_progress']),
            'done'  => $tasks->whereIn('status', ['done', 'completed']),
        ];

        $users = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['employee', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('projects.kanban', compact('project', 'kanban', 'users', 'canManageAll', 'stageFilter'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required',
            'status' => 'required|in:todo,doing,done',
        ]);

        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        $task = Task::where('id', $validated['id'])
                    ->where('tenant_id', $tenantId)
                    ->firstOrFail();

        $canManageAll = in_array($user->role, ['manager', 'super_admin', 'ngo'], true);
        if (!$canManageAll) {
            abort_unless(
                ((int) $task->assigned_to === (int) $user->id) || ((int) $task->created_by === (int) $user->id),
                403
            );
        }

        $wasCompleted = in_array($task->status, ['done', 'completed'], true);

        $task->status = $validated['status'];
        $task->save();

        if (!$wasCompleted && $validated['status'] === 'done') {
            app(WebhookService::class)->fire($tenantId, 'task.completed', [
                'id'       => $task->id,
                'title'    => $task->title,
                'status'   => $task->status,
                'priority' => $task->priority,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateTask(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        $canManageAll = in_array($user->role, ['manager', 'super_admin', 'ngo'], true);

        $validator = Validator::make($request->all(), [
            'id'          => ['required', 'integer'],
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'due_date'    => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validação falhou.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $task = Task::where('id', $validated['id'])
            ->where('tenant_id', $tenantId)
            ->with('assignee:id,name')
            ->firstOrFail();

        if (!$canManageAll) {
            abort_unless(
                ((int) $task->assigned_to === (int) $user->id) || ((int) $task->created_by === (int) $user->id),
                403
            );
        }

        if (!$canManageAll) {
            unset($validated['assigned_to']);
        }

        if (array_key_exists('assigned_to', $validated)) {
            if ($validated['assigned_to'] === null || $validated['assigned_to'] === '') {
                $task->assigned_to = null;
            } else {
                $assigneeOk = User::where('tenant_id', $tenantId)
                    ->whereIn('role', ['employee', 'manager', 'ngo'])
                    ->where('id', (int) $validated['assigned_to'])
                    ->exists();

                if (!$assigneeOk) {
                    return response()->json(['message' => 'Responsável inválido para este tenant.'], 422);
                }
                $task->assigned_to = (int) $validated['assigned_to'];
            }
        }

        if (array_key_exists('title', $validated) && $validated['title'] !== null) {
            $task->title = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $task->description = $validated['description'];
        }
        if (array_key_exists('priority', $validated) && $validated['priority'] !== null) {
            $task->priority = $validated['priority'];
        }
        if (array_key_exists('due_date', $validated)) {
            $task->due_date = $validated['due_date'] ?: null;
        }

        $task->save();
        $task->load('assignee:id,name');

        return response()->json([
            'success' => true,
            'task'    => [
                'id'               => (int) $task->id,
                'title'            => (string) $task->title,
                'description'      => (string) ($task->description ?? ''),
                'priority'         => (string) ($task->priority ?? 'medium'),
                'due_date'         => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'due_label'        => $task->due_date ? $task->due_date->format('d/m') : 'S/P',
                'assigned_to'      => $task->assigned_to ? (int) $task->assigned_to : null,
                'assignee_name'    => $task->assignee?->name,
                'assignee_initial' => $task->assignee?->name ? mb_substr($task->assignee->name, 0, 1) : null,
            ],
        ]);
    }

    public function createApi(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        ['rules' => $rules, 'isPrivileged' => $isPrivileged] = $this->taskValidationRules($user, $tenantId);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validação falhou.', 'errors' => $validator->errors()], 422);
        }

        $task = $this->makeTask($validator->validated(), $isPrivileged, $tenantId, (int) $user->id);
        $task->load('assignee:id,name');

        app(WebhookService::class)->fire($tenantId, 'task.created', [
            'id'         => $task->id,
            'title'      => $task->title,
            'status'     => $task->status,
            'priority'   => $task->priority,
            'project_id' => $task->project_id,
            'due_date'   => $task->due_date?->toDateString(),
        ]);

        $lane = match($task->status) {
            'pending', 'blocked', 'todo' => 'todo',
            'in_progress', 'doing'       => 'doing',
            'completed', 'done'          => 'done',
            default                      => 'todo',
        };

        return response()->json([
            'success' => true,
            'lane'    => $lane,
            'task'    => [
                'id'               => (int) $task->id,
                'title'            => (string) $task->title,
                'description'      => (string) ($task->description ?? ''),
                'status'           => (string) $task->status,
                'priority'         => (string) $task->priority,
                'due_date'         => $task->due_date?->format('Y-m-d'),
                'due_label'        => $task->due_date ? $task->due_date->format('d/m') : 'S/P',
                'assigned_to'      => $task->assigned_to ? (int) $task->assigned_to : null,
                'assignee_name'    => $task->assignee?->name,
                'assignee_initial' => $task->assignee?->name ? mb_substr($task->assignee->name, 0, 1) : null,
            ],
            'html' => view('projects.partials.task_card', ['task' => $task])->render(),
        ], 201);
    }

    public function store(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        ['rules' => $rules, 'isPrivileged' => $isPrivileged] = $this->taskValidationRules($user, $tenantId);

        $validated = $request->validate($rules);

        $this->makeTask($validated, $isPrivileged, $tenantId, (int) $user->id);

        if ($request->has('redirect_to_schedule')) {
            if (!in_array($user->role, ['manager', 'ngo', 'super_admin'])) {
                return redirect('/tasks/calendar')->with('success', 'Evento/Lembrete criado com sucesso!');
            }
            return redirect('/manager/schedule')->with('success', 'Evento/Tarefa criado com sucesso!');
        }

        return back()->with('success', 'Tarefa/Lembrete criado com sucesso!');
    }

    public function calendar(Request $request)
    {
        $request->validate(['date' => 'nullable|date']);

        $date = $request->filled('date')
            ? \Carbon\Carbon::parse($request->date)->locale('pt_BR')
            : \Carbon\Carbon::now()->locale('pt_BR');

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth   = $date->copy()->endOfMonth();
        $userId       = auth()->id();
        $tenantId     = auth()->user()->tenant_id;

        $tasks = Task::where('tenant_id', $tenantId)
                    ->where(function ($q) use ($userId) {
                        $q->where('assigned_to', $userId)
                          ->orWhere('created_by', $userId);
                    })
                    ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
                    ->with(['assignee', 'project'])
                    ->get();

        $overdueTasks = Task::where('tenant_id', $tenantId)
                    ->where(function ($q) use ($userId) {
                        $q->where('assigned_to', $userId)
                          ->orWhere('created_by', $userId);
                    })
                    ->where('due_date', '<', now()->startOfDay())
                    ->whereNotIn('status', ['done', 'completed', 'cancelled'])
                    ->with(['assignee', 'project'])
                    ->orderBy('due_date')
                    ->limit(8)
                    ->get();

        return view('tasks.calendar', compact('date', 'tasks', 'overdueTasks'));
    }

    private function taskValidationRules(User $user, int $tenantId): array
    {
        $projectExistsRule = Rule::exists('projects', 'id')
            ->where(fn ($q) => $q->where('tenant_id', $tenantId));

        $assigneeExistsRule = Rule::exists('users', 'id')
            ->where(fn ($q) => $q->where('tenant_id', $tenantId));

        if ($user->isManager()) {
            $assigneeExistsRule = $assigneeExistsRule->whereIn('role', ['employee', 'manager']);
        } elseif ($user->isNgo() || (($user->tenant?->type ?? null) === 'ngo')) {
            $assigneeExistsRule = $assigneeExistsRule->whereNotIn('role', ['super_admin']);
        }

        $isPrivileged = in_array($user->role, ['manager', 'ngo', 'super_admin'], true);

        // stage_id so vale se veio com project_id valido; validacao de cross-project
        // (stage precisa pertencer ao mesmo projeto) fica no makeTask() pra ter acesso
        // ao valor validado do project_id.
        $stageExistsRule = Rule::exists('project_stages', 'id')
            ->where(fn ($q) => $q->where('tenant_id', $tenantId));

        return [
            'rules' => [
                'project_id'  => $isPrivileged ? ['nullable', 'integer', $projectExistsRule] : ['nullable'],
                'stage_id'    => $isPrivileged ? ['nullable', 'integer', $stageExistsRule] : ['nullable'],
                'title'       => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status'      => ['required', Rule::in(['todo', 'doing', 'done', 'pending', 'in_progress', 'completed', 'blocked'])],
                'assigned_to' => $isPrivileged ? ['nullable', 'integer', $assigneeExistsRule] : ['nullable'],
                'priority'    => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
                'due_date'    => ['nullable', 'date'],
            ],
            'isPrivileged' => $isPrivileged,
        ];
    }

    private function makeTask(array $validated, bool $isPrivileged, int $tenantId, int $userId): Task
    {
        $task              = new Task();
        $task->tenant_id   = $tenantId;
        $task->project_id  = $isPrivileged ? ($validated['project_id'] ?? null) : null;
        // stage_id so persiste quando: (a) usuario privilegiado, (b) veio no
        // payload, (c) pertence ao MESMO projeto validado. Anti-IDOR: bloqueia
        // stage_id de outro projeto do mesmo tenant.
        $stageId = null;
        if ($isPrivileged && !empty($validated['stage_id']) && $task->project_id) {
            $ok = \App\Models\ProjectStage::withoutGlobalScope('tenant')
                ->where('id', (int) $validated['stage_id'])
                ->where('tenant_id', $tenantId)
                ->where('project_id', $task->project_id)
                ->exists();
            if ($ok) {
                $stageId = (int) $validated['stage_id'];
            }
        }
        $task->stage_id    = $stageId;
        $task->title       = $validated['title'];
        $task->description = $validated['description'] ?? null;
        $task->status      = $validated['status'];
        $task->assigned_to = $isPrivileged ? ($validated['assigned_to'] ?? null) : $userId;
        $task->priority    = $validated['priority'] ?? 'medium';
        $task->due_date    = $validated['due_date'] ?? null;
        $task->created_by  = $userId;
        $task->save();

        return $task;
    }
}
