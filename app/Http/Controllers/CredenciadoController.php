<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

/**
 * Painel do Credenciado.
 *
 * User com role=credenciado interage exclusivamente com o(s) projeto(s) onde
 * a entidade o vinculou via ProjectMember. Este controller e o unico ponto
 * de entrada — o middleware EnsureCredenciadoScope garante que ele nao
 * navega para o dashboard principal, financeiro, doadores, etc.
 *
 * Fluxo padrao:
 *  - 0 projetos    -> tela vazia orientando contatar a entidade
 *  - 1 projeto     -> redirect automatico ao workspace
 *  - N projetos    -> seletor (lista com cards)
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
            // Defesa em profundidade: garante que o projeto tambem pertence
            // ao mesmo tenant (protege contra membership orfao apontando pra
            // projeto de outro tenant caso alguem insira via SQL manual).
            // Cast pra int pois Eloquent sem cast explicito retorna string em PDO.
            ->filter(fn ($m) => $m->project
                && (int) $m->project->tenant_id === (int) $user->tenant_id
                && !$m->project->archived_at)
            ->values();

        // 1 projeto = redirect direto ao workspace (default do login).
        if ($memberships->count() === 1) {
            return redirect()->route('credenciado.project', $memberships->first()->project_id);
        }

        return view('credenciado.index', [
            'memberships' => $memberships,
        ]);
    }

    /**
     * Workspace do projeto na visao do credenciado. Reusa a view show do
     * projeto (que ja respeita access_level via ProjectMember) mas com um
     * flag pra ocultar acoes destrutivas na UI.
     */
    public function showProject(int $projectId)
    {
        $user = auth()->user();
        abort_unless($user && $user->isCredenciado(), 403);

        $membership = ProjectMember::where('user_id', $user->id)
            ->where('project_id', $projectId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$membership) {
            abort(403, 'Voce nao esta vinculado a este projeto.');
        }

        $project = Project::where('tenant_id', $user->tenant_id)->find($projectId);
        if (!$project || $project->archived_at) {
            return redirect()->route('credenciado.index')
                ->with('warning', 'Este projeto nao esta mais ativo.');
        }

        // Encaminha para a view de detalhe do projeto — o access_level do
        // ProjectMember ja controla o que ele pode fazer (viewer/editor/admin).
        // O layout renderizara a sidebar do credenciado (bloco proprio).
        return redirect()->route('projects.show', $project->id);
    }
}
