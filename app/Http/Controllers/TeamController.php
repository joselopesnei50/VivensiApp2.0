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
        $invite = $request->input('access_mode', 'invite') === 'invite';

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'role'     => 'required|in:ngo,manager,employee',
            'password' => [$invite ? 'nullable' : 'required', 'string', 'min:12', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'email.unique'    => 'Já existe uma conta com este e-mail.',
            'password.required' => 'Defina a senha inicial ou escolha enviar convite por e-mail.',
            'password.min'    => 'A senha precisa ter pelo menos 12 caracteres.',
            'password.regex'  => 'A senha precisa ter letra maiúscula, letra minúscula e número.',
        ]);

        $user = new User();
        $user->tenant_id = auth()->user()->tenant_id;
        $user->name      = $request->name;
        $user->email     = $request->email;
        $user->role      = $request->role;
        $user->status    = 'active';
        // Convite: senha aleatória que ninguém conhece até a pessoa criar a dela pelo link.
        // Senha definida pelo administrador: a pessoa troca no primeiro acesso.
        $user->password  = Hash::make($invite ? \Illuminate\Support\Str::random(40) : $request->password);
        $user->must_change_password = !$invite;
        $user->save();

        if ($invite) {
            $sent = $this->sendInvite($user);
            return redirect()->back()->with($sent ? 'success' : 'error', $sent
                ? "Convite enviado para {$user->email}. O link vale por 72 horas."
                : 'Membro cadastrado, mas o e-mail de convite não foi enviado. Use "Reenviar convite".');
        }

        return redirect()->back()->with('success', 'Membro adicionado! No primeiro acesso, a pessoa vai criar uma senha só dela.');
    }

    /** Reenvia o convite (link novo de 72 h). */
    public function resendInvite($id)
    {
        $user = User::where('id', $id)->where('tenant_id', auth()->user()->tenant_id)->firstOrFail();
        abort_if($user->role === 'super_admin', 403);

        return $this->sendInvite($user)
            ? redirect()->back()->with('success', "Convite reenviado para {$user->email}.")
            : redirect()->back()->with('error', 'Não foi possível enviar o convite agora. Tente novamente em instantes.');
    }

    private function sendInvite(User $user): bool
    {
        try {
            $token = \Illuminate\Support\Facades\Password::broker('invites')->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email, 'convite' => 1]);
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TeamInviteMail($user, auth()->user(), $url));
            return true;
        } catch (\Throwable $e) {
            \Log::error('Convite de equipe falhou: ' . $e->getMessage(), ['user_id' => $user->id]);
            return false;
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::where('id', $id)->where('tenant_id', auth()->user()->tenant_id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:ngo,manager,employee',
            'status' => 'required|in:active,suspended',
        ]);

        if ($user->role === 'super_admin') {
            return redirect()->back()->with('error', 'Administradores da plataforma não podem ser alterados pelo painel.');
        }

        // Ninguém suspende a si mesmo (perderia o acesso na hora).
        if ($user->id === auth()->id() && $request->status === 'suspended') {
            return redirect()->back()->with('error', 'Você não pode suspender o seu próprio acesso.');
        }

        // A organização nunca fica sem administrador ativo.
        $losesAdmin = $user->role === 'ngo' && ($request->role !== 'ngo' || $request->status === 'suspended');
        if ($losesAdmin) {
            $otherAdmins = User::where('tenant_id', $user->tenant_id)->where('role', 'ngo')
                ->where('id', '!=', $user->id)->where(fn ($q) => $q->where('status', '!=', 'suspended')->orWhereNull('status'))
                ->count();
            if ($otherAdmins === 0) {
                return redirect()->back()->with('error', 'Este é o único administrador ativo da organização. Defina outro administrador antes de mudar a função ou suspender.');
            }
        }

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
                ->where(fn ($q) => $q->where('status', '!=', 'suspended')->orWhereNull('status'))
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
