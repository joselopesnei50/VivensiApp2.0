<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * Painel do Credenciado.
 *
 * User com role=credenciado NUNCA acessa o workspace normal do projeto
 * (/projects/{id}), pois essa view expoe financeiro, doadores, transacoes,
 * membros da equipe interna e outros dados da entidade. Este controller e
 * o UNICO ponto de acesso, e renderiza views proprias, minimais, com apenas
 * o essencial pro credenciado atuar no projeto:
 *
 *   - Info basica do projeto (nome, descricao, periodo, status)
 *   - Tarefas atribuidas a ele (que pode atualizar status)
 *   - Aulas/chamadas do projeto (pode marcar presenca)
 *
 * ZERO acesso a: financeiro, transacoes, doadores, membros da equipe
 * interna, orcamento, DRE, relatorios, radar de editais, WhatsApp, etc.
 */
class CredenciadoController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user && $user->isCredenciado(), 403);

        $memberships = ProjectMember::with('project')
            ->where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->get()
            ->filter(fn ($m) => $m->project
                && (int) $m->project->tenant_id === (int) $user->tenant_id
                && !$m->project->archived_at)
            ->values();

        if ($memberships->count() === 1) {
            return redirect()->route('credenciado.project', $memberships->first()->project_id);
        }

        return view('credenciado.index', ['memberships' => $memberships]);
    }

    /**
     * Workspace minimal do credenciado no projeto. NAO reutiliza a view
     * projects/show.blade.php justamente por essa expor dados sensiveis da
     * entidade. Aqui so mostramos o essencial.
     */
    public function showProject(int $projectId)
    {
        $user    = auth()->user();
        $project = $this->authorizeProject($user, $projectId);

        // Tarefas atribuidas ao credenciado neste projeto (nao mostra tarefas
        // de outros membros — mesmo do mesmo projeto — pra manter foco).
        // Ordena por status priority (in_progress > todo > done) via PHP —
        // evita FIELD() que nao funciona em SQLite (nos testes).
        $statusOrder = ['in_progress' => 1, 'todo' => 2, 'completed' => 3, 'done' => 3, 'cancelled' => 4];
        $tasks = Task::where('project_id', $project->id)
            ->where('tenant_id', $user->tenant_id)
            ->where('assigned_to', $user->id)
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->sortBy(fn ($t) => $statusOrder[$t->status] ?? 99)
            ->values();

        // Aulas/chamadas do projeto — proximas 20. Credenciado pode marcar
        // presenca em qualquer aula do projeto onde e membro. Se o negocio
        // exigir restringir so as aulas onde ele e teacher_user_id, ajuste
        // aqui.
        $sessions = ClassSession::where('project_id', $project->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(20)
            ->get();

        return view('credenciado.workspace', [
            'project'  => $project,
            'tasks'    => $tasks,
            'sessions' => $sessions,
            'membership' => ProjectMember::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->where('tenant_id', $user->tenant_id)
                ->first(),
        ]);
    }

    /**
     * Atualiza status de uma tarefa atribuida ao credenciado.
     * So aceita tarefas onde assigned_to == user->id (nao pode mexer em
     * tarefas de outros).
     */
    public function updateTaskStatus(Request $request, int $projectId, int $taskId)
    {
        $user = auth()->user();
        $this->authorizeProject($user, $projectId);

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,completed,done,cancelled'],
        ]);

        $task = Task::where('id', $taskId)
            ->where('project_id', $projectId)
            ->where('tenant_id', $user->tenant_id)
            ->where('assigned_to', $user->id)
            ->firstOrFail();

        $task->update(['status' => $validated['status']]);

        return back()->with('success', 'Status da tarefa atualizado.');
    }

    /**
     * Garante que o user e credenciado, e que tem ProjectMember do tenant
     * correto para o projeto informado. Retorna o Project (ativo).
     */
    private function authorizeProject($user, int $projectId): Project
    {
        abort_unless($user && $user->isCredenciado(), 403);

        $isMember = ProjectMember::where('user_id', $user->id)
            ->where('project_id', $projectId)
            ->where('tenant_id', $user->tenant_id)
            ->exists();

        abort_unless($isMember, 403, 'Voce nao esta vinculado a este projeto.');

        $project = Project::where('id', $projectId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        abort_unless($project, 404);

        if ($project->archived_at) {
            abort(410, 'Este projeto foi arquivado e nao esta mais ativo.');
        }

        return $project;
    }
}
