<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManagerController extends Controller
{
    // ... existing methods ...

    private function guardManagerOnly(): void
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);
    }

    public function contracts()
    {
        $this->guardManagerOnly();
        // Single source of truth: contracts UI lives in NGO module.
        // Keep the manager endpoint for navigation consistency.
        $basePath = rtrim(request()->getBaseUrl(), '/');
        return redirect($basePath . '/ngo/contracts');
    }

    public function landingPages()
    {
        $this->guardManagerOnly();
        // Backward-compat: keep endpoint working if referenced somewhere.
        // The canonical Landing Pages UI is the NGO flow.
        $basePath = rtrim(request()->getBaseUrl(), '/');
        return redirect($basePath . '/ngo/landing-pages');
    }

    public function reconciliation()
    {
        $this->guardManagerOnly();
        return view('manager.reconciliation');
    }

    public function schedule(Request $request)
    {
        $this->guardManagerOnly();
        $date = $request->has('date') 
            ? \Carbon\Carbon::parse($request->date) 
            : \Carbon\Carbon::now();
            
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $tenantId = auth()->user()->tenant_id;

        // Agenda corporativa: restringe a tarefas de usuários do gestor (evita "mistura" com outros perfis)
        $allowedUsersSub = function ($q) use ($tenantId) {
            $q->select('id')
                ->from('users')
                ->where('tenant_id', $tenantId)
                ->whereIn('role', ['employee', 'manager']);
        };

        // Fetch Tasks (Assignments & Deadlines)
        $tasks = \App\Models\Task::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->where(function ($q) use ($allowedUsersSub) {
                $q->whereNull('assigned_to')
                    ->orWhereIn('assigned_to', $allowedUsersSub);
            })
            ->where(function ($q) use ($allowedUsersSub) {
                $q->whereNull('created_by')
                    ->orWhereIn('created_by', $allowedUsersSub);
            })
            ->with([
                'assignee:id,name',
                'project:id,name',
                'creator:id,name',
            ])
            ->get();
                    
        // Fetch Projects (Start/End dates if available, currently just mocking tasks for now or using tasks assignments)
        // Ideally we would verify Project start_date / end_date too, but let's stick to tasks first.

        return view('manager.schedule', compact('date', 'tasks'));
    }
    public function team()
    {
        $this->guardManagerOnly();
        $tenantId = auth()->user()->tenant_id;

        $employees = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('role', ['employee', 'manager'])
            ->withCount('projectMembers')
            ->orderBy('name')
            ->get();

        $projects = \App\Models\Project::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Premium insights: tasks load per collaborator (open / overdue / due soon)
        $userIds = $employees->pluck('id')->values();
        $openCounts = collect();
        $overdueCounts = collect();
        $dueSoonCounts = collect();

        if ($userIds->isNotEmpty()) {
            $today = now()->toDateString();
            $soon = now()->addDays(7)->toDateString();

            $openCounts = DB::table('tasks')
                ->where('tenant_id', $tenantId)
                ->whereIn('assigned_to', $userIds)
                ->whereNotIn('status', ['done', 'completed'])
                ->groupBy('assigned_to')
                ->selectRaw('assigned_to, COUNT(*) as cnt')
                ->pluck('cnt', 'assigned_to');

            $overdueCounts = DB::table('tasks')
                ->where('tenant_id', $tenantId)
                ->whereIn('assigned_to', $userIds)
                ->whereNotIn('status', ['done', 'completed'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->groupBy('assigned_to')
                ->selectRaw('assigned_to, COUNT(*) as cnt')
                ->pluck('cnt', 'assigned_to');

            $dueSoonCounts = DB::table('tasks')
                ->where('tenant_id', $tenantId)
                ->whereIn('assigned_to', $userIds)
                ->whereNotIn('status', ['done', 'completed'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $soon])
                ->groupBy('assigned_to')
                ->selectRaw('assigned_to, COUNT(*) as cnt')
                ->pluck('cnt', 'assigned_to');
        }

        foreach ($employees as $e) {
            $e->tasks_open_count = (int) ($openCounts[$e->id] ?? 0);
            $e->tasks_overdue_count = (int) ($overdueCounts[$e->id] ?? 0);
            $e->tasks_due_soon_count = (int) ($dueSoonCounts[$e->id] ?? 0);
        }

        return view('manager.team', compact('employees', 'projects'));
    }


    public function teamDetail($id)
    {
        $this->guardManagerOnly();
        $employee = \App\Models\User::where('id', $id)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        
        $projects = \App\Models\ProjectMember::where('user_id', $id)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->with('project')
                        ->get();
        
        // Reminders are essentially tasks created by the manager for this employee
        $reminders = \App\Models\Task::where('assigned_to', $id)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->with('project')
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();

        return view('manager.team_detail', compact('employee', 'projects', 'reminders'));
    }

    public function storeQuick(Request $request)
    {
        $this->guardManagerOnly();

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:employee,manager',
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'access_level' => 'nullable|in:viewer,editor,admin'
        ]);

        $user = \App\Models\User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active'
        ]);

        if (!empty($validated['project_id'])) {
            \App\Models\ProjectMember::create([
                'tenant_id' => $tenantId,
                'project_id' => $validated['project_id'],
                'user_id' => $user->id,
                'access_level' => $validated['access_level'] ?? 'viewer'
            ]);
        }

        return back()->with('success', !empty($validated['project_id'])
            ? 'Colaborador cadastrado e vinculado com sucesso!'
            : 'Colaborador cadastrado com sucesso!'
        );
    }

    public function approvals()
    {
        $this->guardManagerOnly();
        // For now, listing transactions that might need approval (status pending)
        $pendingApprovals = \App\Models\Transaction::where('tenant_id', auth()->user()->tenant_id)
                            ->where('status', 'pending')
                            ->with('project')
                            ->get();
        return view('manager.approvals', compact('pendingApprovals'));
    }

}
