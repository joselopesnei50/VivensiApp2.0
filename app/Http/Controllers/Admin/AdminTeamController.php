<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminTeamController extends Controller
{
    /**
     * Display Vivensi Internal Team
     */
    public function index()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        // Isolamento: Apenas membros marcados explicitamente como Time da Plataforma
        $team = User::where('is_platform_team', true)
                    ->with('supervisor')
                    ->orderBy('department')
                    ->get();

        $supervisors = User::where('is_platform_team', true)->get();

        return view('admin.team.index', compact('team', 'supervisors'));
    }

    /**
     * Store a new team member
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string',
            'department' => 'required|string',
            'supervisor_id' => 'nullable|exists:users,id',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'tenant_id' => 1, // Vivensi Platform Tenant
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => $request->department,
            'is_platform_team' => true, // Marcação crucial para isolamento
            'supervisor_id' => $request->supervisor_id,
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Membro do time cadastrado com sucesso!');
    }

    /**
     * Update team member
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string',
            'department' => 'required|string',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);

        $user->update([
            'name' => $request->name,
            'role' => $request->role,
            'department' => $request->department,
            'supervisor_id' => $request->supervisor_id,
        ]);

        return redirect()->back()->with('success', 'Membro atualizado!');
    }

    /**
     * Remove from team
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Você não pode remover a si mesmo.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Membro removido do time.');
    }

    /**
     * Display member profile and daily dashboard
     */
    public function profile($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $user = User::where('id', $id)->where('is_platform_team', true)->firstOrFail();

        // Daily Tasks (Internal platform tasks have tenant_id = 1)
        $tasks = \App\Models\Task::where('assigned_to', $user->id)
                                 ->orderBy('status', 'asc')
                                 ->orderBy('due_date', 'asc')
                                 ->get();

        // Support Tickets (if applicable)
        $tickets = [];
        if ($user->department === 'suporte') {
            $tickets = \App\Models\SupportTicket::where('assigned_to', $user->id)
                                               ->orWhere(function($q) use ($user) {
                                                   $q->whereNull('assigned_to')->where('category', 'technical'); // Technical support fallback
                                               })
                                               ->orderBy('status')
                                               ->get();
        }

        return view('admin.team.profile', compact('user', 'tasks', 'tickets'));
    }
}
