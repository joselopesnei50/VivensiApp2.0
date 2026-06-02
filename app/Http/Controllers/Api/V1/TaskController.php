<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TaskResource;
use App\Models\Task;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Task::where('tenant_id', $tenantId)
            ->when($request->status,     fn ($q) => $q->where('status', $request->status))
            ->when($request->priority,   fn ($q) => $q->where('priority', $request->priority))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        $perPage = min((int) ($request->per_page ?? 20), 100);

        if ($request->boolean('cursor')) {
            return TaskResource::collection($query->cursorPaginate($perPage));
        }

        return TaskResource::collection($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['nullable', Rule::in(['todo', 'doing', 'done', 'pending', 'in_progress', 'completed', 'blocked'])],
            'priority'    => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'project_id'  => ['nullable', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'due_date'    => 'nullable|date',
        ]);

        $validated['tenant_id']  = $tenantId;
        $validated['created_by'] = $request->user()->id;
        $validated['status']     = $validated['status']   ?? 'todo';
        $validated['priority']   = $validated['priority'] ?? 'medium';

        $task = Task::create($validated);

        app(WebhookService::class)->fire($tenantId, 'task.created', [
            'id'         => $task->id,
            'title'      => $task->title,
            'status'     => $task->status,
            'priority'   => $task->priority,
            'project_id' => $task->project_id,
            'due_date'   => $task->due_date?->toDateString(),
        ]);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        $task = Task::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return new TaskResource($task);
    }

    public function update(Request $request, int $id)
    {
        $task = Task::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $wasCompleted = in_array($task->status, ['done', 'completed'], true);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['sometimes', Rule::in(['todo', 'doing', 'done', 'pending', 'in_progress', 'completed', 'blocked'])],
            'priority'    => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'due_date'    => 'nullable|date',
        ]);

        $task->update($validated);

        // Fire webhook if task just became completed
        $nowCompleted = in_array($validated['status'] ?? '', ['done', 'completed'], true);
        if (!$wasCompleted && $nowCompleted) {
            app(WebhookService::class)->fire($task->tenant_id, 'task.completed', [
                'id'       => $task->id,
                'title'    => $task->title,
                'status'   => $task->status,
                'priority' => $task->priority,
            ]);
        }

        return new TaskResource($task->fresh());
    }

    public function destroy(Request $request, int $id)
    {
        Task::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
