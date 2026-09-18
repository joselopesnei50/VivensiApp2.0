<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function __construct()
    {
        // Defense-in-depth: mesmo se alguma rota for adicionada sem
        // middleware('can:manage-team'), o gate barra aqui.
        $this->middleware(function ($request, $next) {
            abort_unless(Gate::allows('manage-team'), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        $users = User::where('tenant_id', $tenant_id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'       => $users->count(),
            'ngo'         => $users->where('role', 'ngo')->count(),
            'manager'     => $users->where('role', 'manager')->count(),
            'employee'    => $users->where('role', 'employee')->count(),
            'credenciado' => $users->where('role', 'credenciado')->count(),
            'active'      => $users->where('status', 'active')->count(),
        ];

        return view('ngo.team.index', compact('users', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:ngo,manager,employee',
            'password' => ['required', 'string', 'min:12', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
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

    public function destroy(Request $request, $id)
    {
        $actor    = auth()->user();
        $tenantId = $actor->tenant_id;

        $user = User::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        // Prevent deleting yourself
        if ($user->id == $actor->id) {
            return redirect()->back()->with('error', 'Você não pode remover a si mesmo.');
        }

        // Defense-in-depth: super_admin nunca deve poder ser removido via
        // painel de tenant (o gate manage-team ja restringe a role=ngo, mas
        // um super_admin com tenant_id do gestor cairia aqui se existir).
        if ($user->role === 'super_admin') {
            return redirect()->back()->with('error', 'Administradores da plataforma não podem ser removidos pelo painel.');
        }

        // Ultimo admin (ngo) do tenant: se este for o unico usuario ngo ativo,
        // bloqueia — evita orfanar o tenant sem gestor.
        if ($user->role === 'ngo') {
            $remainingNgo = User::where('tenant_id', $tenantId)
                ->where('role', 'ngo')
                ->where('id', '!=', $user->id)
                ->count();
            if ($remainingNgo === 0) {
                return redirect()->back()->with('error', 'Este é o último administrador da conta e não pode ser removido.');
            }
        }

        $snapshot = [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ];

        $user->delete();

        try {
            AuditLog::create([
                'tenant_id'      => $tenantId,
                'user_id'        => $actor->id,
                'event'          => 'team.member.deleted',
                'auditable_type' => User::class,
                'auditable_id'   => $snapshot['id'],
                'old_values'     => $snapshot,
                'new_values'     => null,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'url'            => $request->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // Audit e melhor esforco — nao bloqueia a remocao.
        }

        return redirect()->back()->with('success', 'Membro removido com sucesso!');
    }
}
