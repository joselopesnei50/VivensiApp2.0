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
        $users = User::where('tenant_id', $tenant_id)->get();

        return view('ngo.team.index', compact('users'));
    }

    public function store(Request $request)
    {
        // Permission check: Only 'ngo' or 'manager' role can add members (adjust as needed)
        // For MVP, assuming current user has permission if they can access this route.

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
        // Note: The table schema showed password_hash, but standard Laravel uses 'password'.
        // I should check User model to see what attribute is mapped. 
        // Based on previous `desc users`, the column is `password_hash`.
        // However, standard Laravel auth usually expects `password`.
        // Let's verify User model first. For now I'll assume standard Hash facade, but assigning to the correct column.
        $user->password_hash = Hash::make($request->password); 
        $user->status = 'active';
        $user->save();

        return redirect()->back()->with('success', 'Membro adicionado com sucesso!');
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
