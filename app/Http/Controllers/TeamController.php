<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        $users = User::where('tenant_id', $tenant_id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $users->count(),
            'ngo' => $users->where('role', 'ngo')->count(),
            'manager' => $users->where('role', 'manager')->count(),
            'employee' => $users->where('role', 'employee')->count(),
            'active' => $users->where('status', 'active')->count(),
        ];

        return view('ngo.team.index', compact('users', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:ngo,manager,employee',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->tenant_id = auth()->user()->tenant_id;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->password = Hash::make($request->password);
        $user->status = 'active';
        $user->save();

        return redirect()->back()->with('success', 'Membro adicionado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $user = User::where('id', $id)->where('tenant_id', auth()->user()->tenant_id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:ngo,manager,employee',
            'status' => 'required|in:active,suspended',
        ]);

        $user->update([
            'name' => $request->name,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Dados do membro atualizados!');
    }

    public function destroy($id)
    {
        $user = User::where('id', $id)->where('tenant_id', auth()->user()->tenant_id)->firstOrFail();

        // Prevent deleting yourself
        if ($user->id == auth()->id()) {
            return redirect()->back()->with('error', 'Você não pode remover a si mesmo.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Membro removido com sucesso!');
    }
}
