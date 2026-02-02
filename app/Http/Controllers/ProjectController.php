<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        // No sistema legado: $_SESSION['tenant_id']
        // No Laravel: auth()->user()->tenant_id
        
        $projects = Project::where('tenant_id', auth()->user()->tenant_id)
                           ->orderBy('created_at', 'desc')
                           ->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'budget' => 'required|numeric',
            'start_date' => 'required|date',
            'status' => 'required',
        ]);

        $project = new Project($request->all());
        $project->tenant_id = auth()->user()->tenant_id;
        $project->save();

        return redirect('/projects')->with('success', 'Projeto criado com sucesso (Via Laravel)!');
    }

    public function show($id)
    {
        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        // Financials
        $totalSpent = \App\Models\Transaction::where('project_id', $project->id)
                        ->where('type', 'expense')
                        ->sum('amount');
        
        $percentUsed = ($project->budget > 0) ? ($totalSpent / $project->budget) * 100 : 0;

        // Recent Transactions
        $transactions = \App\Models\Transaction::where('project_id', $project->id)
                        ->orderBy('date', 'desc')
                        ->limit(10)
                        ->get();

        // Project Members
        $members = \App\Models\ProjectMember::where('project_id', $project->id)
                        ->with('user')
                        ->get();

        // Available Users to Add (Users in tenant not already in project)
        $memberIds = $members->pluck('user_id')->toArray();
        $availableUsers = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                        ->whereNotIn('id', $memberIds)
                        ->get();

        return view('projects.show', compact('project', 'totalSpent', 'transactions', 'percentUsed', 'members', 'availableUsers'));
    }

    public function addMember(Request $request, $id)
    {
        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:viewer,editor,admin'
        ]);

        \App\Models\ProjectMember::create([
            'tenant_id' => auth()->user()->tenant_id,
            'project_id' => $id,
            'user_id' => $validated['user_id'],
            'access_level' => $validated['access_level']
        ]);

        return back()->with('success', 'Membro adicionado ao projeto!');
    }

    public function removeMember($projectId, $memberId)
    {
        $member = \App\Models\ProjectMember::where('id', $memberId)
                        ->where('project_id', $projectId)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        
        $member->delete();

        return back()->with('success', 'Membro removido do projeto.');
    }

    public function edit($id)
    {
        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, $id)
    {
        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|max:255',
            'budget' => 'required|numeric',
            'start_date' => 'required|date',
            'status' => 'required',
        ]);

        $project->update($request->all());

        return redirect('/projects/details/'.$id)->with('success', 'Projeto atualizado com sucesso!');
    }
}
