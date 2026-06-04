<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateProjectLogSummaryJob;
use App\Models\Project;
use App\Models\ProjectLog;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectLogController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function store(Request $request, int $projectId)
    {
        $tenantId = auth()->user()->tenant_id;
        $user     = auth()->user();

        $project = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        // Employees só podem escrever logs se forem membros do projeto
        if (!in_array($user->role, ['manager', 'super_admin', 'ngo'], true)) {
            abort_unless(
                \App\Models\ProjectMember::where('tenant_id', $tenantId)
                    ->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->exists(),
                403
            );
        }

        $request->validate(['body' => 'required|string|max:3000']);

        ProjectLog::create([
            'project_id' => $project->id,
            'tenant_id'  => $tenantId,
            'user_id'    => auth()->id(),
            'body'       => $request->input('body'),
            'created_at' => now(),
        ]);

        // Invalida o cache da tela do projeto para que a nova entrada apareça imediatamente
        $this->projectService->flushCache($tenantId, $project->id);

        return redirect()->back()->with('log_success', 'Entrada registrada.');
    }

    public function destroy(int $projectId, int $logId)
    {
        $tenantId = auth()->user()->tenant_id;
        $user     = auth()->user();

        $log = ProjectLog::where('id', $logId)
            ->where('project_id', $projectId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Só manager/super_admin ou o próprio autor pode excluir
        abort_unless(
            in_array($user->role, ['manager', 'super_admin'], true) || $log->user_id === $user->id,
            403
        );

        $log->delete();

        $this->projectService->flushCache($tenantId, $projectId);

        return redirect()->back()->with('log_success', 'Entrada removida.');
    }

    public function generateSummary(int $projectId)
    {
        $tenantId = auth()->user()->tenant_id;
        $user     = auth()->user();

        // Disparar um job de IA tem custo. Manter na mesma regra das outras ações
        // operacionais do projeto (manager/super_admin/ngo).
        abort_unless(in_array($user->role, ['manager', 'super_admin', 'ngo'], true), 403);

        $project  = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        $logCount = ProjectLog::where('project_id', $project->id)->where('tenant_id', $tenantId)->count();
        if ($logCount < 3) {
            return response()->json(['error' => 'Mínimo de 3 entradas para gerar o relatório.'], 422);
        }

        $project->update(['ai_summary_status' => 'processing', 'ai_summary' => null, 'ai_summary_at' => null]);
        GenerateProjectLogSummaryJob::dispatch($project->id, $tenantId)->onQueue('ai');

        return response()->json(['status' => 'processing']);
    }

    public function summaryStatus(int $projectId)
    {
        $tenantId = auth()->user()->tenant_id;
        $project  = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        return response()->json([
            'status'     => $project->ai_summary_status,
            'summary'    => $project->ai_summary,
            'summary_at' => $project->ai_summary_at?->format('d/m/Y H:i'),
        ]);
    }
}
