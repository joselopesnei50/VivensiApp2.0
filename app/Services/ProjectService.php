<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Transaction;
use App\Models\ProjectLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectService
{
    public function create(array $data, int $tenantId): Project
    {
        $data = $this->sanitizeBudget($data);

        $validated = Validator::make($data, $this->rules())->validate();

        $project = new Project($validated);
        $project->tenant_id = $tenantId;
        $project->save();

        $this->flushCache($tenantId);

        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        $data = $this->sanitizeBudget($data);

        $validated = Validator::make($data, $this->rules())->validate();

        $project->update($validated);

        $this->flushCache($project->tenant_id, $project->id);

        return $project;
    }

    public function getWithDetails(int $id, int $tenantId): array
    {
        $project = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $cacheKey = "project.details.{$tenantId}.{$id}";

        $details = Cache::remember($cacheKey, 300, function () use ($project, $tenantId) {
            $totalSpent = Transaction::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->sum('amount');

            $transactions = Transaction::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            $members = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->with('user')
                ->get();

            $memberIds = $members->pluck('user_id')->toArray();

            $availableUsers = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['employee', 'manager', 'ngo'])
                ->whereNotIn('id', $memberIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            $logs = ProjectLog::where('project_id', $project->id)
                ->where('tenant_id', $tenantId)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();

            $percentUsed = ($project->budget > 0)
                ? ($totalSpent / $project->budget) * 100
                : 0;

            return compact('totalSpent', 'transactions', 'members', 'availableUsers', 'logs', 'percentUsed');
        });

        return array_merge(['project' => $project], $details);
    }

    public function listForTenant(int $tenantId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Project::where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate(12);
    }

    public function flushCache(int $tenantId, ?int $projectId = null): void
    {
        Cache::forget("dashboard.stats.{$tenantId}");
        Cache::forget("projects.list.{$tenantId}");
        if ($projectId !== null) {
            Cache::forget("project.details.{$tenantId}.{$projectId}");
        }
    }

    private function sanitizeBudget(array $data): array
    {
        if (isset($data['budget'])) {
            $data['budget'] = str_replace('.', '', (string) $data['budget']);
            $data['budget'] = str_replace(',', '.', $data['budget']);
        }
        return $data;
    }

    private function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget'      => ['required', 'numeric', 'min:0'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'      => ['required', Rule::in(['active', 'paused', 'completed', 'canceled'])],
        ];
    }
}
