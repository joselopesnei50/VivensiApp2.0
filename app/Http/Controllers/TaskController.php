<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $tasks = Task::where('tenant_id', $tenantId)
                     ->where('assigned_to', auth()->id())
                     ->orderBy('due_date', 'asc')
                     ->paginate(20);

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $user = auth()->user();
        
        // Se for usuário comum (Pessoal), não vê projetos de empresa nem outros usuários
        if (!in_array($user->role, ['manager', 'ngo', 'super_admin'])) {
            $projects = collect(); // Coleção vazia
            $users = collect([$user]); // Apenas ele mesmo
        } else {
            $projects = Project::where('tenant_id', $user->tenant_id)
                ->orderBy('name')
                ->get();

            // Evita "vazamento" de perfis de outros módulos no seletor de responsável
            $usersQ = User::where('tenant_id', $user->tenant_id);

            if ($user->role === 'manager') {
                $usersQ->whereIn('role', ['employee', 'manager']);
            } elseif ($user->role === 'ngo' || (($user->tenant?->type ?? null) === 'ngo')) {
                $usersQ->whereNotIn('role', ['super_admin']);
            }

            $users = $usersQ->orderBy('name')->get();
        }
        
        return view('tasks.create', compact('projects', 'users'));
    }


    public function kanban($projectId)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $project = Project::where('id', $projectId)
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        // Security: employee can only access projects they belong to.
        $canManageAll = in_array($user->role, ['manager', 'super_admin'], true);
        if (!$canManageAll) {
            $isMember = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($isMember, 403);
        }

        $tasks = Task::where('project_id', $projectId)
                     ->where('tenant_id', $tenantId)
                     ->with('assignee:id,name') // Eager loading
                     // Premium ordering: critical/high first, then medium, then low
                     ->orderByRaw("FIELD(priority,'critical','high','medium','low')")
                     ->orderBy('due_date')
                     ->orderBy('created_at', 'desc')
                     ->get();

        $kanban = [
            // Normalize other statuses into the classic kanban lanes
            'todo' => $tasks->whereIn('status', ['todo', 'pending', 'blocked']),
            'doing' => $tasks->whereIn('status', ['doing', 'in_progress']),
            'done' => $tasks->whereIn('status', ['done', 'completed']),
        ];

        // Se precisarmos de usuários para atribuir tarefas
        $users = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['employee', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('projects.kanban', compact('project', 'kanban', 'users', 'canManageAll'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'status' => 'required|in:todo,doing,done'
        ]);

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $task = Task::where('id', $validated['id'])
                    ->where('tenant_id', $tenantId)
                    ->firstOrFail();

        // Hardening: colaboradores só podem alterar suas próprias tarefas (ou as que criaram).
        $canManageAll = in_array($user->role, ['manager', 'super_admin'], true);
        if (!$canManageAll) {
            abort_unless(
                ((int) $task->assigned_to === (int) $user->id) || ((int) $task->created_by === (int) $user->id),
                403
            );
        }

        $task->status = $validated['status'];
        $task->save();

        return response()->json(['success' => true]);
    }

    public function updateTask(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $canManageAll = in_array($user->role, ['manager', 'super_admin'], true);

        $data = $request->all();

        $validator = Validator::make($data, [
            'id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'due_date' => ['nullable', 'date'],
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

        // Employee: cannot reassign tasks; only managers can.
        if (!$canManageAll) {
            unset($validated['assigned_to']);
        }

        if (array_key_exists('assigned_to', $validated)) {
            if ($validated['assigned_to'] === null || $validated['assigned_to'] === '') {
                $task->assigned_to = null;
            } else {
                $assigneeOk = User::where('tenant_id', $tenantId)
                    ->whereIn('role', ['employee', 'manager'])
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
            'task' => [
                'id' => (int) $task->id,
                'title' => (string) $task->title,
                'description' => (string) ($task->description ?? ''),
                'priority' => (string) ($task->priority ?? 'medium'),
                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'due_label' => $task->due_date ? $task->due_date->format('d/m') : 'S/P',
                'assigned_to' => $task->assigned_to ? (int) $task->assigned_to : null,
                'assignee_name' => $task->assignee?->name,
                'assignee_initial' => $task->assignee?->name ? mb_substr($task->assignee->name, 0, 1) : null,
            ],
        ]);
    }

    public function createApi(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $statusAllowed = ['todo', 'doing', 'done', 'pending', 'in_progress', 'completed', 'blocked'];
        $priorityAllowed = ['low', 'medium', 'high', 'critical'];

        $projectExistsRule = Rule::exists('projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId));
        $assigneeExistsRule = Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId));

        if ($user->role === 'manager') {
            $assigneeExistsRule = $assigneeExistsRule->whereIn('role', ['employee', 'manager']);
        }
        if ($user->role === 'ngo' || (($user->tenant?->type ?? null) === 'ngo')) {
            $assigneeExistsRule = $assigneeExistsRule->whereNotIn('role', ['super_admin']);
        }

        $isPrivileged = in_array($user->role, ['manager', 'ngo', 'super_admin'], true);

        $validator = Validator::make($request->all(), [
            'project_id' => $isPrivileged ? ['nullable', 'integer', $projectExistsRule] : ['nullable'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in($statusAllowed)],
            'assigned_to' => $isPrivileged ? ['nullable', 'integer', $assigneeExistsRule] : ['nullable'],
            'priority' => ['nullable', Rule::in($priorityAllowed)],
            'due_date' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validação falhou.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $task = new Task();
        $task->tenant_id = $tenantId;
        $task->project_id = $isPrivileged ? ($validated['project_id'] ?? null) : null;
        $task->title = $validated['title'];
        $task->description = $validated['description'] ?? null;
        $task->status = $validated['status'];
        $task->assigned_to = $isPrivileged ? ($validated['assigned_to'] ?? null) : (int) $user->id;
        $task->priority = $validated['priority'] ?? 'medium';
        $task->due_date = $validated['due_date'] ?? null;
        $task->created_by = (int) $user->id;
        $task->save();

        $task->load('assignee:id,name');

        $lane = match($task->status ?? 'todo') {
            'pending', 'blocked', 'todo' => 'todo',
            'in_progress', 'doing' => 'doing',
            'completed', 'done' => 'done',
            default => 'todo',
        };

        return response()->json([
            'success' => true,
            'lane' => (string) $lane,
            'task' => [
                'id' => (int) $task->id,
                'title' => (string) $task->title,
                'description' => (string) ($task->description ?? ''),
                'status' => (string) ($task->status ?? 'todo'),
                'priority' => (string) ($task->priority ?? 'medium'),
                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'due_label' => $task->due_date ? $task->due_date->format('d/m') : 'S/P',
                'assigned_to' => $task->assigned_to ? (int) $task->assigned_to : null,
                'assignee_name' => $task->assignee?->name,
                'assignee_initial' => $task->assignee?->name ? mb_substr($task->assignee->name, 0, 1) : null,
            ],
            'html' => view('projects.partials.task_card', ['task' => $task])->render(),
        ], 201);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $statusAllowed = ['todo', 'doing', 'done', 'pending', 'in_progress', 'completed', 'blocked'];
        $priorityAllowed = ['low', 'medium', 'high', 'critical'];

        $projectExistsRule = Rule::exists('projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId));
        $assigneeExistsRule = Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId));

        // Manager: pode atribuir a funcionários/gestores do mesmo tenant
        if ($user->role === 'manager') {
            $assigneeExistsRule = $assigneeExistsRule->whereIn('role', ['employee', 'manager']);
        }

        // NGO: pode atribuir a usuários do tenant (exceto super_admin)
        if ($user->role === 'ngo' || (($user->tenant?->type ?? null) === 'ngo')) {
            $assigneeExistsRule = $assigneeExistsRule->whereNotIn('role', ['super_admin']);
        }

        // Usuário comum: nunca pode atribuir para terceiros nem vincular a projetos
        $isPrivileged = in_array($user->role, ['manager', 'ngo', 'super_admin'], true);

        $validated = $request->validate([
            'project_id' => $isPrivileged ? ['nullable', 'integer', $projectExistsRule] : ['nullable'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in($statusAllowed)],
            'assigned_to' => $isPrivileged ? ['nullable', 'integer', $assigneeExistsRule] : ['nullable'],
            'priority' => ['nullable', Rule::in($priorityAllowed)],
            'due_date' => ['nullable', 'date'],
        ]);

        
        $task = new Task();
        $task->tenant_id = $tenantId;
        $task->project_id = $isPrivileged ? ($validated['project_id'] ?? null) : null;
        $task->title = $validated['title'];
        $task->description = $validated['description'] ?? null;
        $task->status = $validated['status'];
        $task->assigned_to = $isPrivileged ? ($validated['assigned_to'] ?? null) : auth()->id();
        $task->priority = $validated['priority'] ?? 'medium';
        $task->due_date = $validated['due_date'] ?? null;
        $task->created_by = auth()->id();
        $task->save();
        
        if ($request->has('redirect_to_schedule')) {
            // Se for usuário comum, vai para o seu calendário pessoal
            $user = auth()->user();
            if (!in_array($user->role, ['manager', 'ngo', 'super_admin'])) {
                return redirect('/tasks/calendar')->with('success', 'Evento/Lembrete criado com sucesso!');
            }
            return redirect('/manager/schedule')->with('success', 'Evento/Tarefa criado com sucesso!');
        }

        return back()->with('success', 'Tarefa/Lembrete criado com sucesso!');
    }

    public function calendar(Request $request)
    {
        $date = $request->has('date') 
            ? \Carbon\Carbon::parse($request->date) 
            : \Carbon\Carbon::now();
            
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Fetch Tasks (Assignments & Deadlines)
        $tasks = Task::where('tenant_id', auth()->user()->tenant_id)
                    ->where(function($q) {
                         $q->where('assigned_to', auth()->id())
                           ->orWhere('created_by', auth()->id());
                    })
                    ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
                    ->get();

        return view('tasks.calendar', compact('date', 'tasks'));
    }
}
