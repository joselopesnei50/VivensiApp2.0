<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

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
            $projects = Project::where('tenant_id', $user->tenant_id)->get();
            $users = User::where('tenant_id', $user->tenant_id)->get();
        }
        
        return view('tasks.create', compact('projects', 'users'));
    }


    public function kanban($projectId)
    {
        $project = Project::where('id', $projectId)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        $tasks = Task::where('project_id', $projectId)
                     ->where('tenant_id', auth()->user()->tenant_id)
                     ->with('assignee') // Eager loading
                     ->get();

        $kanban = [
            'todo' => $tasks->where('status', 'todo'),
            'doing' => $tasks->where('status', 'doing'),
            'done' => $tasks->where('status', 'done'),
        ];

        // Se precisarmos de usuários para atribuir tarefas
        $users = User::where('tenant_id', auth()->user()->tenant_id)->get();

        return view('projects.kanban', compact('project', 'kanban', 'users'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'status' => 'required|in:todo,doing,done'
        ]);

        $task = Task::where('id', $validated['id'])
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->firstOrFail();

        $task->status = $validated['status'];
        $task->save();

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
         $validated = $request->validate([
            'project_id' => 'nullable',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date'
        ]);

        
        $task = new Task();
        $task->tenant_id = auth()->user()->tenant_id;
        $task->project_id = $validated['project_id'] ?? null;
        $task->title = $validated['title'];
        $task->description = $validated['description'] ?? null;
        $task->status = $validated['status'];
        $task->assigned_to = $validated['assigned_to'] ?? null;
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
