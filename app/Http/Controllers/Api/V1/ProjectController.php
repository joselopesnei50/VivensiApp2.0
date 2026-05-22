<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProjectResource;
use App\Http\Resources\Api\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Project::where('tenant_id', $tenantId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc');

        $paginated = $query->paginate(min((int) ($request->per_page ?? 20), 100));

        return ProjectResource::collection($paginated);
    }

    public function show(Request $request, int $id)
    {
        $project = Project::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return new ProjectResource($project);
    }

    public function tasks(Request $request, int $id)
    {
        $tenantId = $request->user()->tenant_id;

        Project::where('tenant_id', $tenantId)->findOrFail($id);

        $tasks = Task::where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) ($request->per_page ?? 20), 100));

        return TaskResource::collection($tasks);
    }
}
