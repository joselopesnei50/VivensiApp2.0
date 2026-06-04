<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\BruceAiService;
use Illuminate\Http\Request;

class BruceAiController extends Controller
{
    public function __construct(private BruceAiService $bruce) {}

    public function chat(Request $request)
    {
        $request->validate([
            'message'      => 'required|string|max:2000',
            'context_type' => 'nullable|in:project',
            'context_id'   => 'nullable|integer',
        ]);

        $user        = auth()->user();
        $contextType = null;
        $contextId   = null;

        // Bruce contextual por projeto — exige feature flag E ownership + permissão
        if (config('bruce.context_project_enabled', false)
            && $request->input('context_type') === 'project'
            && $request->filled('context_id')
        ) {
            $projectId = (int) $request->input('context_id');

            $project = Project::where('tenant_id', $user->tenant_id)
                ->where('id', $projectId)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Projeto não encontrado nesta organização.'], 404);
            }

            // Quem pode iniciar Bruce contextual: papel de gestão OU membro do projeto
            $canAccessProject = in_array($user->role, ['manager', 'ngo', 'super_admin'], true)
                || ProjectMember::where('tenant_id', $user->tenant_id)
                    ->where('project_id', $projectId)
                    ->where('user_id', $user->id)
                    ->exists();

            if (!$canAccessProject) {
                return response()->json(['error' => 'Você não tem acesso a este projeto.'], 403);
            }

            $contextType = 'project';
            $contextId   = $projectId;
        }

        $response = $this->bruce->chat(
            $request->input('message'),
            $user->tenant_id,
            $user->role,
            $user->id,
            $contextType,
            $contextId
        );

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 503);
        }

        return response()->json($response);
    }

    public function clearHistory(Request $request)
    {
        $user        = auth()->user();
        $contextType = $request->input('context_type');
        $contextId   = $request->input('context_id');

        // Só limpa histórico contextual se a flag estiver ligada
        if (!config('bruce.context_project_enabled', false) || $contextType !== 'project') {
            $contextType = null;
            $contextId   = null;
        }

        $this->bruce->clearHistory($user->tenant_id, $user->id, $contextType, $contextId ? (int) $contextId : null);

        return response()->json(['success' => true]);
    }

    public function insight()
    {
        $user    = auth()->user();
        $insight = $this->bruce->dailyInsight($user->tenant_id, $user->role);
        return response()->json(['insight' => $insight]);
    }
}
