<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExecutiveAgendaController extends Controller
{
    private const PLATFORM_TENANT = 1;

    public function index(Request $request)
    {
        $week   = $request->get('week', 0); // 0 = semana atual, -1 = anterior, +1 = próxima
        $today  = now()->startOfDay();
        $weekStart = now()->startOfWeek()->addWeeks($week);
        $weekEnd   = now()->endOfWeek()->addWeeks($week);

        // Equipe interna da Vivensi
        $team = User::where('is_platform_team', true)
            ->where('status', 'active')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        // Todas as tarefas internas
        $tasks = Task::where('tenant_id', self::PLATFORM_TENANT)
            ->with(['assignee', 'creator'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->get();

        // Distribuição por membro
        $workload = $team->mapWithKeys(function ($member) use ($tasks) {
            $memberTasks = $tasks->where('assigned_to', $member->id);
            return [$member->id => [
                'user'      => $member,
                'total'     => $memberTasks->count(),
                'pending'   => $memberTasks->whereIn('status', ['todo', 'pending'])->count(),
                'doing'     => $memberTasks->whereIn('status', ['doing', 'in_progress'])->count(),
                'done'      => $memberTasks->whereIn('status', ['done', 'completed'])->count(),
                'overdue'   => $memberTasks->whereIn('status', ['todo','doing','in_progress','pending'])
                                  ->filter(fn($t) => $t->due_date && $t->due_date->isPast())->count(),
                'tasks'     => $memberTasks->whereNotIn('status', ['done', 'completed'])->values(),
            ]];
        });

        // Métricas executivas
        $metrics = [
            'total_open'     => $tasks->whereNotIn('status', ['done','completed'])->count(),
            'done_this_week' => $tasks->whereIn('status', ['done','completed'])
                                    ->filter(fn($t) => $t->updated_at >= now()->startOfWeek())->count(),
            'overdue'        => $tasks->whereIn('status', ['todo','doing','in_progress','pending'])
                                    ->filter(fn($t) => $t->due_date && $t->due_date->isPast())->count(),
            'critical'       => $tasks->whereNotIn('status', ['done','completed'])
                                    ->where('priority', 'critical')->count(),
            'unassigned'     => $tasks->whereNotIn('status', ['done','completed'])
                                    ->whereNull('assigned_to')->count(),
        ];

        // Tarefas desta semana (para view de calendário semanal)
        $weekTasks = $tasks->filter(fn($t) =>
            $t->due_date && $t->due_date->between($weekStart, $weekEnd)
        )->groupBy(fn($t) => $t->due_date->format('Y-m-d'));

        // Próximos vencimentos (7 dias)
        $upcoming = $tasks->whereNotIn('status', ['done','completed'])
            ->filter(fn($t) => $t->due_date && $t->due_date->between(now(), now()->addDays(7)))
            ->sortBy('due_date');

        return view('admin.executive_agenda.index', compact(
            'team', 'tasks', 'workload', 'metrics',
            'weekTasks', 'weekStart', 'weekEnd', 'week', 'upcoming', 'today'
        ));
    }

    public function storeTask(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority'    => ['required', 'in:low,medium,high,critical'],
            'due_date'    => ['nullable', 'date'],
            'status'      => ['required', 'in:todo,doing,in_progress'],
        ]);

        Task::create(array_merge($validated, [
            'tenant_id'  => self::PLATFORM_TENANT,
            'created_by' => auth()->id(),
            'status'     => $validated['status'] ?? 'todo',
        ]));

        return back()->with('success', 'Tarefa criada com sucesso!');
    }

    public function updateTask(Request $request, Task $task)
    {
        abort_if($task->tenant_id !== self::PLATFORM_TENANT, 403);

        $validated = $request->validate([
            'status'      => ['sometimes', 'in:todo,doing,in_progress,done,completed,blocked'],
            'priority'    => ['sometimes', 'in:low,medium,high,critical'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'title'       => ['sometimes', 'string', 'max:255'],
        ]);

        $task->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroyTask(Task $task)
    {
        abort_if($task->tenant_id !== self::PLATFORM_TENANT, 403);
        $task->delete();
        return back()->with('success', 'Tarefa removida.');
    }
}
