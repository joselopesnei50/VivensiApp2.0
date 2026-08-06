<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfJob;
use App\Models\AuditLog;
use App\Models\GeneratedReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use App\Models\ProjectMember;
use App\Services\ProjectService;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function index(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'employee', 'super_admin', 'ngo'], true), 403);

        // No sistema legado: $_SESSION['tenant_id']
        // No Laravel: auth()->user()->tenant_id
        
        $tenantId = auth()->user()->tenant_id;

        $user = auth()->user();

        $status = (string) ($request->query('status', 'all'));
        $q = trim((string) $request->query('q', ''));
        $flag = (string) ($request->query('flag', ''));
        $sort = (string) ($request->query('sort', 'recent'));
        $memberId = $request->query('member');

        $allowedStatus = ['all', 'active', 'paused', 'completed', 'canceled'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'all';
        }

        $allowedSort = ['recent', 'deadline', 'budget', 'risk'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'recent';
        }

        $projectsQ = Project::query()
            ->where('projects.tenant_id', $tenantId)
            ->active() // arquivados ficam fora da listagem padrão; ver /projects/archived
            ->withCount('members');

        // Usuário operacional: só vê projetos onde é membro
        if (!in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            $projectIds = ProjectMember::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->pluck('project_id');

            $projectsQ->whereIn('id', $projectIds);
        }

        if ($q !== '') {
            $projectsQ->where(function ($qq) use ($q) {
                $qq->where('projects.name', 'like', '%' . $q . '%')
                    ->orWhere('projects.description', 'like', '%' . $q . '%');
            });
        }

        if ($status !== 'all') {
            $projectsQ->where('projects.status', $status);
        }

        if (in_array($user->role, ['manager', 'super_admin', 'ngo'], true) && $memberId !== null && $memberId !== '') {
            $projectsQ->whereExists(function ($sq) use ($tenantId, $memberId) {
                $sq->selectRaw('1')
                    ->from('project_members')
                    ->whereColumn('project_members.project_id', 'projects.id')
                    ->where('project_members.tenant_id', $tenantId)
                    ->where('project_members.user_id', (int) $memberId);
            });
        }

        $spentSub = DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->selectRaw('project_id, SUM(amount) as total_spent');

        $tasksSub = DB::table('tasks')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->selectRaw('project_id')
            ->selectRaw("COUNT(*) as total_tasks")
            ->selectRaw("SUM(CASE WHEN status IN ('done','completed') THEN 1 ELSE 0 END) as done_tasks")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('done','completed') THEN 1 ELSE 0 END) as open_tasks")
            ->selectRaw("SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() AND status NOT IN ('done','completed') THEN 1 ELSE 0 END) as overdue_tasks")
            ->selectRaw("SUM(CASE WHEN priority = 'critical' AND status NOT IN ('done','completed') THEN 1 ELSE 0 END) as critical_open_tasks");

        $projectsQ
            ->leftJoinSub($spentSub, 'spent', function ($j) {
                $j->on('projects.id', '=', 'spent.project_id');
            })
            ->leftJoinSub($tasksSub, 'tsk', function ($j) {
                $j->on('projects.id', '=', 'tsk.project_id');
            })
            ->addSelect('projects.*')
            ->addSelect(DB::raw('COALESCE(spent.total_spent, 0) as total_spent'))
            ->addSelect(DB::raw('COALESCE(tsk.total_tasks, 0) as total_tasks'))
            ->addSelect(DB::raw('COALESCE(tsk.done_tasks, 0) as done_tasks'))
            ->addSelect(DB::raw('COALESCE(tsk.open_tasks, 0) as open_tasks'))
            ->addSelect(DB::raw('COALESCE(tsk.overdue_tasks, 0) as overdue_tasks'))
            ->addSelect(DB::raw('COALESCE(tsk.critical_open_tasks, 0) as critical_open_tasks'));

        if ($flag === 'over_budget') {
            $projectsQ->whereRaw('projects.budget > 0 AND COALESCE(spent.total_spent, 0) > projects.budget');
        }

        if ($sort === 'deadline') {
            $projectsQ
                ->orderByRaw('CASE WHEN projects.end_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('projects.end_date', 'asc')
                ->orderBy('projects.created_at', 'desc');
        } elseif ($sort === 'budget') {
            $projectsQ
                ->orderByRaw('CASE WHEN projects.budget IS NULL OR projects.budget = 0 THEN 1 ELSE 0 END')
                ->orderByRaw('(COALESCE(spent.total_spent, 0) / NULLIF(projects.budget, 0)) DESC')
                ->orderBy('projects.created_at', 'desc');
        } elseif ($sort === 'risk') {
            $projectsQ->orderByRaw("
                (
                    (COALESCE(tsk.overdue_tasks,0) * 200) +
                    (COALESCE(tsk.critical_open_tasks,0) * 80) +
                    (CASE
                        WHEN projects.end_date IS NULL THEN 0
                        WHEN projects.end_date < CURDATE() THEN 250
                        ELSE GREATEST(0, 30 - DATEDIFF(projects.end_date, CURDATE()))
                    END) +
                    (CASE
                        WHEN projects.budget > 0 AND COALESCE(spent.total_spent,0) > projects.budget THEN 180
                        ELSE 0
                    END)
                ) DESC
            ");
        } else {
            $projectsQ->orderBy('projects.created_at', 'desc');
        }

        $projects = $projectsQ->paginate(18)->appends($request->query());

        $teamUsers = null;
        if (in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            $teamUsers = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['employee', 'manager', 'ngo'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        }

        return view('projects.index', compact('projects', 'teamUsers', 'status', 'q', 'flag', 'sort', 'memberId'));
    }

    public function create()
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);
        return view('projects.create');
    }

    public function store(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $this->projectService->create($request->all(), (int) auth()->user()->tenant_id);

        return redirect('/projects')->with('success', 'Projeto criado com sucesso!');
    }

    public function show($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'employee', 'credenciado', 'super_admin', 'ngo'], true), 403);

        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        $data = $this->projectService->getWithDetails((int) $id, $tenantId);

        // Roles operacionais (employee, credenciado) so acessam projetos onde
        // ha ProjectMember. Manager/ngo/super_admin veem qualquer projeto do
        // tenant.
        if (!in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            abort_unless(
                ProjectMember::where('tenant_id', $tenantId)
                    ->where('project_id', (int) $id)
                    ->where('user_id', $user->id)
                    ->exists(),
                403
            );
        }

        // Lista compacta de beneficiarios do tenant pro seletor no modal
        // "Cadastrar Pessoa". Ate 500 beneficiarios cabe em memoria; acima
        // disso o dropdown ja fica ruim de usar mesmo — tratar com AJAX
        // quando aparecer o primeiro tenant nessa faixa.
        $data['beneficiariesForLink'] = \App\Models\Beneficiary::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        // Eager load do beneficiario ligado a cada ProjectPerson (opt-in via
        // form). Evita N+1 quando a tabela "Pessoas & Contatos" renderiza o
        // badge/link pro perfil. Beneficiario deletado -> beneficiary_id fica
        // null (booted hook do Beneficiary + FK nullOnDelete).
        $data['project']->load(['people.beneficiary:id,name,status']);

        // Contador de beneficiarios DISTINTOS vinculados ao projeto (uma mesma
        // familia pode aparecer varias vezes em people com o mesmo beneficiary_id
        // se cadastrada em multiplos contextos). So faz sentido mostrar quando
        // houver pelo menos um vinculo.
        $data['linkedBeneficiariesCount'] = $data['project']->people
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id')
            ->unique()
            ->count();

        // Opção C — landing pages públicas vinculadas a este projeto e o total
        // de submissões (leads) capturadas em cada uma.
        $data['registrationLandings'] = \App\Models\LandingPage::where('tenant_id', $tenantId)
            ->where('target_project_id', (int) $id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'slug', 'status', 'target_creates_person', 'target_link_beneficiary']);

        $data['registrationLandingsSubmissions'] = [];
        if ($data['registrationLandings']->isNotEmpty()) {
            $data['registrationLandingsSubmissions'] = \DB::table('landing_page_leads')
                ->whereIn('landing_page_id', $data['registrationLandings']->pluck('id'))
                ->select('landing_page_id', \DB::raw('COUNT(*) as total'))
                ->groupBy('landing_page_id')
                ->pluck('total', 'landing_page_id')
                ->toArray();
        }

        return view('projects.show', $data);
    }

    public function exportPdf($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = auth()->user()->tenant_id;

        Project::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'project_report',
            'params'    => ['tenant_id' => $tenantId, 'project_id' => (int) $id],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function addMember(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

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

        $this->projectService->flushCache((int) auth()->user()->tenant_id, (int) $id);

        return back()->with('success', 'Membro adicionado ao projeto!');
    }

    public function addMemberCredential(Request $request, $id)
    {
        return $this->createNewMember($request, $id, role: 'employee');
    }

    /**
     * Cria conta de "Credenciado": user restrito ao painel /credenciado, com
     * acesso APENAS a este projeto (e a outros que a entidade lhe atribuir
     * depois). Reusa createNewMember internamente.
     */
    public function addMemberCredenciado(Request $request, $id)
    {
        return $this->createNewMember($request, $id, role: 'credenciado');
    }

    /**
     * Fluxo comum de cadastro de novo user + ProjectMember + envio de link
     * de definicao de senha. O role decide se o user tera acesso ao painel
     * padrao (employee) ou fica restrito ao painel do Credenciado.
     */
    private function createNewMember(Request $request, $id, string $role)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);
        abort_unless(in_array($role, ['employee', 'credenciado'], true), 400);

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
        $newUser->role = $role;
        $newUser->status = 'active';
        $newUser->password = Hash::make($tempPassword);
        // Credenciado tem projeto default pra login redirect direto ao workspace
        // quando so ha 1 vinculo. Se depois for atribuido a mais projetos, o
        // seletor /credenciado assume o controle.
        if ($role === 'credenciado') {
            $newUser->default_project_id = $project->id;
        }
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

        $this->projectService->flushCache((int) $tenantId, (int) $project->id);

        $resp = back()->with('success', 'Credencial criada e membro vinculado ao projeto. Um link para definir a senha foi enviado ao e-mail informado.');
        if ($inviteUrl) {
            $resp->with('invite_link', $inviteUrl)->with('invite_email', $newUser->email);
        }
        return $resp;
    }

    public function removeMember($projectId, $memberId)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $member = \App\Models\ProjectMember::where('id', $memberId)
                        ->where('project_id', $projectId)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->firstOrFail();
        
        $member->delete();

        $this->projectService->flushCache((int) auth()->user()->tenant_id, (int) $projectId);

        return back()->with('success', 'Membro removido do projeto.');
    }

    public function edit($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $project = Project::where('id', $id)
                          ->where('tenant_id', auth()->user()->tenant_id)
                          ->firstOrFail();

        $this->projectService->update($project, $request->all());

        return redirect('/projects/details/'.$id)->with('success', 'Projeto atualizado com sucesso!');
    }

    public function storePerson(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'employee', 'super_admin', 'ngo'], true), 403);

        $tenantId = (int) auth()->user()->tenant_id;
        $project = Project::where('id', $id)
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        // Employee só cadastra pessoas (dados pessoais) em projeto onde é
        // membro — mesmo padrão do show/planning.
        $user = auth()->user();
        if (!in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            abort_unless(
                ProjectMember::where('tenant_id', $tenantId)
                    ->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->exists(),
                403
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'beneficiary_id' => ['nullable', 'integer', Rule::exists('beneficiaries', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
        ]);

        // Se veio beneficiary_id: Rule::exists ja garante ownership; evitamos
        // duplicar o vinculo do mesmo beneficiario no mesmo projeto.
        if (!empty($validated['beneficiary_id'])) {
            $exists = \App\Models\ProjectPerson::where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('beneficiary_id', (int) $validated['beneficiary_id'])
                ->exists();
            if ($exists) {
                return back()
                    ->with('error', 'Este beneficiário já está vinculado a este projeto.')
                    ->withInput();
            }
        }

        $validated['tenant_id'] = $tenantId;
        $validated['project_id'] = $project->id;

        \App\Models\ProjectPerson::create($validated);

        $this->projectService->flushCache($tenantId, (int) $project->id);

        return back()->with('success', 'Pessoa adicionada ao projeto!');
    }

    public function storeGlobalPerson(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = (int) auth()->user()->tenant_id;

        $validated = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'beneficiary_id' => ['nullable', 'integer', Rule::exists('beneficiaries', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
        ]);

        if (!empty($validated['beneficiary_id'])) {
            $duplicated = \App\Models\ProjectPerson::where('tenant_id', $tenantId)
                ->where('project_id', (int) $validated['project_id'])
                ->where('beneficiary_id', (int) $validated['beneficiary_id'])
                ->exists();
            if ($duplicated) {
                return back()
                    ->with('error', 'Este beneficiário já está vinculado a este projeto.')
                    ->withInput();
            }
        }

        // Belt-and-suspenders: a regra de validacao acima ja garante tenant, mas
        // mantemos o firstOrFail por seguranca (race conditions, mudancas futuras).
        $project = Project::where('id', $validated['project_id'])
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        $validated['tenant_id'] = auth()->user()->tenant_id;

        \App\Models\ProjectPerson::create($validated);

        $this->projectService->flushCache((int) auth()->user()->tenant_id, (int) $project->id);

        return back()->with('success', 'Pessoa cadastrada e vinculada ao projeto com sucesso!');
    }

    public function importPeople(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = (int) auth()->user()->tenant_id;

        $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $project = Project::where('id', $request->project_id)
                          ->where('tenant_id', $tenantId)
                          ->firstOrFail();

        $data = array_map('str_getcsv', file($request->file('csv_file')->getRealPath()));

        // check if header exists
        if (count($data) > 0 && strtolower(trim($data[0][0])) === 'nome') {
            array_shift($data);
        }

        if (count($data) > 500) {
            return back()->withErrors(['csv_file' => 'O CSV pode ter no máximo 500 linhas por importação.']);
        }

        // Trunca nos limites das colunas — campo estourado dava SQL error no
        // meio do loop e deixava importação parcial. Transação fecha o resto.
        $cap = fn (?string $v, int $max) => $v === null || $v === ''
            ? null
            : mb_substr($v, 0, $max);

        $imported = 0;
        DB::transaction(function () use ($data, $project, $cap, &$imported) {
            foreach ($data as $row) {
                if (count($row) >= 1) {
                    $name = trim($row[0] ?? '');
                    if (!$name) continue;

                    \App\Models\ProjectPerson::create([
                        'tenant_id'  => auth()->user()->tenant_id,
                        'project_id' => $project->id,
                        'name'       => $cap($name, 255),
                        'phone'      => $cap(isset($row[1]) ? trim($row[1]) : null, 30),
                        'address'    => $cap(isset($row[2]) ? trim($row[2]) : null, 255),
                        'city'       => $cap(isset($row[3]) ? trim($row[3]) : null, 255),
                    ]);
                    $imported++;
                }
            }
        });

        return back()->with('success', "{$imported} contatos importados com sucesso para o projeto!");
    }

    public function destroyPerson($projectId, $personId)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $person = \App\Models\ProjectPerson::where('id', $personId)
            ->where('project_id', $projectId)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $person->delete();

        $this->projectService->flushCache((int) auth()->user()->tenant_id, (int) $projectId);

        return back()->with('success', 'Pessoa removida.');
    }

    /**
     * Detalhe completo do inscrito (2026-08-06) — usado no modal AJAX
     * "Ver detalhes" na tabela de inscritos de /projects/{id}. Endpoint
     * proprio pra Manager (que nao tem modulo /ngo/beneficiaries).
     *
     * Tenta enriquecer com custom_fields do landing lead: casa por phone
     * normalizado (mais confiavel que email — ProjectPerson nao tem email).
     */
    public function showPerson($projectId, $personId)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = auth()->user()->tenant_id;

        $person = \App\Models\ProjectPerson::where('id', $personId)
            ->where('project_id', $projectId)
            ->where('tenant_id', $tenantId)
            ->with(['beneficiary:id,name', 'project:id,name'])
            ->firstOrFail();

        // Match com landing_page_leads por phone normalizado (so digitos) —
        // pra recuperar email + custom fields que foram preenchidos na landing.
        $lead = null;
        if (!empty($person->phone)) {
            $normalizedPhone = preg_replace('/\D+/', '', (string) $person->phone);
            if ($normalizedPhone !== '') {
                $lead = \Illuminate\Support\Facades\DB::table('landing_page_leads as l')
                    ->join('landing_pages as p', 'p.id', '=', 'l.landing_page_id')
                    ->where('p.tenant_id', $tenantId)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(l.phone,'(',''),')',''),'-',''),' ','') LIKE ?", ['%' . $normalizedPhone])
                    ->orderByDesc('l.created_at')
                    ->select('l.email', 'l.extra_data', 'l.created_at')
                    ->first();
            }
        }

        $customFields = [];
        $leadEmail    = null;
        if ($lead) {
            $leadEmail = $lead->email;
            $extra     = json_decode($lead->extra_data ?? '[]', true) ?: [];
            $customFields = is_array($extra['custom'] ?? null) ? $extra['custom'] : [];
        }

        return response()->json([
            'id'                => (int) $person->id,
            'name'              => $person->name,
            'phone'             => $person->phone,
            'email'             => $leadEmail,
            'address'           => $person->address,
            'city'              => $person->city,
            'birth_date'        => optional($person->birth_date)->format('d/m/Y'),
            'guardian_name'     => $person->guardian_name,
            'guardian_phone'    => $person->guardian_phone,
            'enrollment_status' => $person->enrollment_status,
            'beneficiary'       => $person->beneficiary?->only(['id', 'name']),
            'project_name'      => $person->project?->name,
            'custom_fields'     => $customFields,
        ]);
    }

    public function createBroadcastList($id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $project = Project::with('people')
            ->where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $phones = $project->people->filter(function($p) {
            return !empty($p->phone);
        })->map(function($p) {
            return preg_replace('/\D+/', '', $p->phone);
        })->filter()->implode(',');

        if (empty($phones)) {
            return back()->with('error', 'Nenhum telefone válido encontrado nas pessoas do projeto.');
        }

        return redirect()->route('whatsapp.broadcast.index')->with('prefilled_phones', $phones);
    }

    // ── Arquivamento de projetos ────────────────────────────────────────────

    public function archived(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->query('q', ''));

        $projects = Project::query()
            ->where('tenant_id', $tenantId)
            ->archived()
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                      ->orWhere('description', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('archived_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('projects.archived', compact('projects', 'q'));
    }

    public function archive(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = auth()->user()->tenant_id;

        $project = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('info', 'Este projeto já está arquivado.');
        }

        $project->archive((int) auth()->id());

        try {
            AuditLog::create([
                'tenant_id'      => $tenantId,
                'user_id'        => auth()->id(),
                'event'          => 'project.archived',
                'auditable_type' => Project::class,
                'auditable_id'   => $project->id,
                'old_values'     => ['archived_at' => null],
                'new_values'     => ['archived_at' => $project->archived_at, 'archived_by' => $project->archived_by],
            ]);
        } catch (\Throwable $e) {
            // Audit log é melhor esforço — não bloqueia a ação
        }

        return redirect('/projects')->with('success', 'Projeto arquivado. Acesse "Arquivados" para reativá-lo.');
    }

    public function unarchive(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $tenantId = auth()->user()->tenant_id;

        $project = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (! $project->isArchived()) {
            return back()->with('info', 'Este projeto já está ativo.');
        }

        $oldArchivedAt = $project->archived_at;
        $oldArchivedBy = $project->archived_by;

        $project->unarchive();

        try {
            AuditLog::create([
                'tenant_id'      => $tenantId,
                'user_id'        => auth()->id(),
                'event'          => 'project.unarchived',
                'auditable_type' => Project::class,
                'auditable_id'   => $project->id,
                'old_values'     => ['archived_at' => $oldArchivedAt, 'archived_by' => $oldArchivedBy],
                'new_values'     => ['archived_at' => null, 'archived_by' => null],
            ]);
        } catch (\Throwable $e) {
            // não bloqueia
        }

        return redirect('/projects/details/' . $project->id)->with('success', 'Projeto reativado com sucesso.');
    }
}
