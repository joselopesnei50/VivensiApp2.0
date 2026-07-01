<?php

namespace App\Http\Controllers;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassSessionController extends Controller
{
    private const ROLES_READ  = ['manager', 'employee', 'super_admin', 'ngo'];
    private const ROLES_WRITE = ['manager', 'super_admin', 'ngo'];

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function findProject(int $projectId): Project
    {
        return Project::where('id', $projectId)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function findSession(int $sessionId): ClassSession
    {
        return ClassSession::where('id', $sessionId)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // Listagem geral (link do sidebar) — cruza projetos do tenant.
    public function indexAll(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $sessions = ClassSession::with('project:id,name')
            ->where('tenant_id', $this->tenantId())
            ->orderBy('date', 'desc')
            ->paginate(30);

        return view('class-sessions.index-all', compact('sessions'));
    }

    // Listagem contextual (dentro do projeto).
    public function index(int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);

        $sessions = ClassSession::where('project_id', $project->id)
            ->orderBy('date', 'desc')
            ->paginate(30);

        return view('class-sessions.index', compact('project', 'sessions'));
    }

    public function create(int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);

        return view('class-sessions.form', [
            'project' => $project,
            'session' => new ClassSession(['mode' => 'fechada']),
        ]);
    }

    public function store(Request $request, int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);
        $validated = $this->validateSession($request);

        $validated['tenant_id']  = $this->tenantId();
        $validated['project_id'] = $project->id;

        ClassSession::create($validated);

        return redirect("/projects/{$project->id}/class-sessions")
            ->with('success', 'Sessão criada.');
    }

    public function edit(int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);
        $session = $this->findSession($sessionId);
        abort_if($session->project_id !== $project->id, 404);

        return view('class-sessions.form', compact('project', 'session'));
    }

    public function update(Request $request, int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);
        $session = $this->findSession($sessionId);
        abort_if($session->project_id !== $project->id, 404);

        $session->update($this->validateSession($request));

        return redirect("/projects/{$project->id}/class-sessions")
            ->with('success', 'Sessão atualizada.');
    }

    public function destroy(int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);
        $session = $this->findSession($sessionId);
        abort_if($session->project_id !== $project->id, 404);

        // Hard delete — cascade nas presencas via FK.
        $session->delete();

        return redirect("/projects/{$project->id}/class-sessions")
            ->with('success', 'Sessão removida.');
    }

    // Tela de chamada — pre-popula matriculados ativos como 'presente' e
    // mescla com presencas ja registradas (edicao posterior).
    public function show(int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);
        $session = $this->findSession($sessionId);
        abort_if($session->project_id !== $project->id, 404);

        $students = ProjectPerson::where('project_id', $project->id)
            ->where('tenant_id', $this->tenantId())
            ->where('enrollment_status', 'ativo')
            ->orderBy('name')
            ->get();

        $existing = ClassAttendance::where('class_session_id', $session->id)
            ->get()
            ->keyBy('project_person_id');

        // Se ha alunos inativos com presenca registrada, mostra tambem (nao remove historico).
        $historicIds = $existing->keys()->diff($students->pluck('id'))->all();
        if (!empty($historicIds)) {
            $historic = ProjectPerson::whereIn('id', $historicIds)
                ->where('tenant_id', $this->tenantId())
                ->orderBy('name')
                ->get();
            $students = $students->concat($historic);
        }

        return view('class-sessions.show', compact('project', 'session', 'students', 'existing'));
    }

    public function saveAttendance(Request $request, int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_WRITE, true), 403);

        $project = $this->findProject($projectId);
        $session = $this->findSession($sessionId);
        abort_if($session->project_id !== $project->id, 404);

        $validated = $request->validate([
            'attendance'                   => 'required|array',
            'attendance.*.status'          => ['required', Rule::in(['presente', 'falta', 'falta_justificada'])],
            'attendance.*.justification'   => 'nullable|string|max:500',
        ]);

        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $now    = now();

        DB::transaction(function () use ($validated, $session, $project, $ipHash, $now) {
            foreach ($validated['attendance'] as $personId => $row) {
                $personId = (int) $personId;

                // Confirma que o aluno pertence ao projeto (belt-and-suspenders anti-tamper).
                $belongs = ProjectPerson::where('id', $personId)
                    ->where('project_id', $project->id)
                    ->where('tenant_id', $this->tenantId())
                    ->exists();

                if (!$belongs) {
                    continue;
                }

                $justification = $row['status'] === 'falta_justificada'
                    ? ($row['justification'] ?? null)
                    : null;

                ClassAttendance::updateOrCreate(
                    [
                        'class_session_id'  => $session->id,
                        'project_person_id' => $personId,
                    ],
                    [
                        'tenant_id'       => $this->tenantId(),
                        'status'          => $row['status'],
                        'checked_in_via'  => 'professor',
                        'justification'   => $justification,
                        'checked_in_at'   => $now,
                        'ip_hash'         => $ipHash,
                    ]
                );
            }
        });

        return redirect("/projects/{$project->id}/class-sessions/{$session->id}")
            ->with('success', 'Chamada salva.');
    }

    private function validateSession(Request $request): array
    {
        return $request->validate([
            'title'                => 'required|string|max:255',
            'date'                 => 'required|date',
            'start_time'           => 'nullable|date_format:H:i',
            'end_time'             => 'nullable|date_format:H:i|after_or_equal:start_time',
            'teacher_user_id'      => 'nullable|integer',
            'mode'                 => ['required', Rule::in(['fechada', 'aberta'])],
            'notes'                => 'nullable|string|max:2000',
        ]);
    }
}
