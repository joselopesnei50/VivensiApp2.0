<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TaskResource;
use App\Models\Task;
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
            ->orderBy('created_at', 'desc');

        $paginated = $query->paginate(min((int) ($request->per_page ?? 20), 100));

        return TaskResource::collection($paginated);
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

        $validated['tenant_id'] = $tenantId;
        $validated['status']    = $validated['status']   ?? 'todo';
        $validated['priority']  = $validated['priority'] ?? 'medium';

        $task = Task::create($validated);

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
}
