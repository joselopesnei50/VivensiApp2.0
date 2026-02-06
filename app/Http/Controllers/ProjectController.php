<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\ProjectMember;

class ProjectController extends Controller
{
    public function index()
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'employee', 'super_admin'], true), 403);

        // No sistema legado: $_SESSION['tenant_id']
        // No Laravel: auth()->user()->tenant_id
        
        $tenantId = auth()->user()->tenant_id;

        $user = auth()->user();

        $projectsQ = Project::where('tenant_id', $tenantId)->withCount('members');

        // Usuário operacional: só vê projetos onde é membro
        if (!in_array($user->role, ['manager', 'super_admin'], true)) {
            $projectIds = ProjectMember::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->pluck('project_id');

            $projectsQ->whereIn('id', $projectIds);
        }

        $projects = $projectsQ->orderBy('created_at', 'desc')->get();

        // Aggregate spend per project (paid expenses only)
        $spentByProject = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->selectRaw('project_id, SUM(amount) as total_spent')
            ->pluck('total_spent', 'project_id');

        return view('projects.index', compact('projects', 'spentByProject'));
    }

    public function create()
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);
        return view('projects.create');
    }

    public function store(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        // Sanitização de Moeda Brasileira (R$ 1.000,00 -> 1000.00)
        $data = $request->all();
        if (isset($data['budget'])) {
            $data['budget'] = str_replace('.', '', (string) $data['budget']);
            $data['budget'] = str_replace(',', '.', (string) $data['budget']);
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'paused', 'completed', 'canceled'])],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $project = new Project($validated);
        $project->tenant_id = auth()->user()->tenant_id;
        $project->save();

        return redirect('/projects')->with('success', 'Projeto criado com sucesso (Via Laravel)!');
    }

    public function show($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'employee', 'super_admin'], true), 403);

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $project = Project::where('id', $id)
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        if (!in_array($user->role, ['manager', 'super_admin'], true)) {
            $isMember = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($isMember, 403);
        }

        // Financials
        $totalSpent = \App\Models\Transaction::where('tenant_id', $tenantId)
                        ->where('project_id', $project->id)
                        ->where('type', 'expense')
                        ->where('status', 'paid')
                        ->sum('amount');
        
        $percentUsed = ($project->budget > 0) ? ($totalSpent / $project->budget) * 100 : 0;

        // Recent Transactions
        $transactions = \App\Models\Transaction::where('tenant_id', $tenantId)
                        ->where('project_id', $project->id)
                        ->orderBy('date', 'desc')
                        ->limit(10)
                        ->get();

        // Project Members
        $members = \App\Models\ProjectMember::where('tenant_id', $tenantId)
                        ->where('project_id', $project->id)
                        ->with('user')
                        ->get();

        // Available Users to Add (Users in tenant not already in project)
        $memberIds = $members->pluck('user_id')->toArray();
        $availableUsers = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['employee', 'manager'])
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('projects.show', compact('project', 'totalSpent', 'transactions', 'percentUsed', 'members', 'availableUsers'));
    }

    public function addMember(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
            ],
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

    public function addMemberCredential(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $tenantId = auth()->user()->tenant_id;

        $project = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'access_level' => ['required', Rule::in(['viewer', 'editor', 'admin'])],
        ]);

        // Create user inside current tenant with safe defaults
        $tempPassword = bin2hex(random_bytes(8)); // 16 chars

        $newUser = new User();
        $newUser->tenant_id = $tenantId;
        $newUser->name = $validated['name'];
        $newUser->email = $validated['email'];
        $newUser->phone = $validated['phone'] ?? null;
        $newUser->role = 'employee';
        $newUser->status = 'active';
        $newUser->password = Hash::make($tempPassword);
        $newUser->save();

        ProjectMember::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'user_id' => (int) $newUser->id,
            'access_level' => $validated['access_level'],
        ]);

        // Send password definition link (best-effort) and also return a one-time invite link to the manager.
        // This avoids getting stuck when email delivery is not available in local/dev environments.
        $inviteUrl = null;
        try {
            $token = Password::broker()->createToken($newUser);
            $basePath = rtrim($request->getBaseUrl(), '/');
            $inviteUrl = $request->getSchemeAndHttpHost()
                . $basePath
                . '/reset-password/' . $token
                . '?email=' . urlencode((string) $newUser->email);
            $newUser->sendPasswordResetNotification($token);
        } catch (\Throwable $e) {
            // ignore; fail-safe
        }

        $resp = back()->with('success', 'Credencial criada e membro vinculado ao projeto. Um link para definir a senha foi enviado ao e-mail informado.');
        if ($inviteUrl) {
            $resp->with('invite_link', $inviteUrl)->with('invite_email', $newUser->email);
        }
        return $resp;
    }

    public function removeMember($projectId, $memberId)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $member = \App\Models\ProjectMember::where('id', $memberId)
                        ->where('project_id', $projectId)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        
        $member->delete();

        return back()->with('success', 'Membro removido do projeto.');
    }

    public function edit($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        // Sanitização de Moeda Brasileira (R$ 1.000,00 -> 1000.00)
        $data = $request->all();
        if (isset($data['budget'])) {
            $data['budget'] = str_replace('.', '', (string) $data['budget']);
            $data['budget'] = str_replace(',', '.', (string) $data['budget']);
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'paused', 'completed', 'canceled'])],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $project->update($validated);

        return redirect('/projects/details/'.$id)->with('success', 'Projeto atualizado com sucesso!');
    }
}
