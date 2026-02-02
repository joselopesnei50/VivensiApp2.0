<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\LandingPage;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    // ... existing methods ...

    public function contracts()
    {
        $contracts = Contract::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('manager.contracts', compact('contracts'));
    }

    public function landingPages()
    {
        $pages = LandingPage::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('manager.landing_pages', compact('pages'));
    }

    public function reconciliation()
    {
        return view('manager.reconciliation');
    }

    public function schedule(Request $request)
    {
        $date = $request->has('date') 
            ? \Carbon\Carbon::parse($request->date) 
            : \Carbon\Carbon::now();
            
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Fetch Tasks (Assignments & Deadlines)
        $tasks = \App\Models\Task::where('tenant_id', auth()->user()->tenant_id)
                    ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
                    ->get();
                    
        // Fetch Projects (Start/End dates if available, currently just mocking tasks for now or using tasks assignments)
        // Ideally we would verify Project start_date / end_date too, but let's stick to tasks first.

        return view('manager.schedule', compact('date', 'tasks'));
    }
    public function team()
    {
        $employees = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                        ->whereIn('role', ['employee', 'manager'])
                        ->withCount('projectMembers')
                        ->get();
        return view('manager.team', compact('employees'));
    }


    public function teamDetail($id)
    {
        $employee = \App\Models\User::where('id', $id)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        
        $projects = \App\Models\ProjectMember::where('user_id', $id)
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:employee,manager',
            'project_id' => 'required|exists:projects,id',
            'access_level' => 'required|in:viewer,editor,admin'
        ]);

        $user = \App\Models\User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active'
        ]);

        \App\Models\ProjectMember::create([
            'tenant_id' => auth()->user()->tenant_id,
            'project_id' => $validated['project_id'],
            'user_id' => $user->id,
            'access_level' => $validated['access_level']
        ]);

        return back()->with('success', 'Colaborador cadastrado e vinculado com sucesso!');
    }

    public function approvals()
    {
        // For now, listing transactions that might need approval (status pending)
        $pendingApprovals = \App\Models\Transaction::where('tenant_id', auth()->user()->tenant_id)
                            ->where('status', 'pending')
                            ->get();
        return view('manager.approvals', compact('pendingApprovals'));
    }

}
