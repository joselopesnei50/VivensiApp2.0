<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLog;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectLogController extends Controller
{
    public function store(Request $request, int $projectId)
    {
        $tenantId = auth()->user()->tenant_id;

        $project = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        $request->validate(['body' => 'required|string|max:3000']);

        ProjectLog::create([
            'project_id' => $project->id,
            'tenant_id'  => $tenantId,
            'user_id'    => auth()->id(),
            'body'       => $request->input('body'),
            'created_at' => now(),
        ]);

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

        return redirect()->back()->with('log_success', 'Entrada removida.');
    }

    public function generateSummary(int $projectId)
    {
        $tenantId = auth()->user()->tenant_id;

        $project = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        $logs = ProjectLog::where('project_id', $project->id)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'asc')
            ->with('user:id,name')
            ->get();

        if ($logs->count() < 3) {
            return response()->json(['error' => 'Mínimo de 3 entradas para gerar o relatório.'], 422);
        }

        $logText = $logs->map(function ($l) {
            $author = $l->user?->name ?? 'Equipe';
            $date   = $l->created_at->format('d/m/Y H:i');
            return "[{$date} — {$author}]\n{$l->body}";
        })->implode("\n\n---\n\n");

        $prompt = "Você é um assistente de gestão de projetos. Com base nas entradas de diário abaixo, produza um **Relatório de Evolução** estruturado e profissional em português.

PROJETO: {$project->name}
DESCRIÇÃO: {$project->description}

ENTRADAS DO DIÁRIO:
{$logText}

INSTRUÇÕES:
- Use markdown com headers (##)
- Inclua: Resumo Executivo, Principais Avanços, Pontos de Atenção, Próximos Passos sugeridos
- Seja objetivo, direto e profissional
- Máximo 500 palavras";

        $summary = $this->callDeepSeek($prompt);

        if (!$summary) {
            return response()->json(['error' => 'Não foi possível gerar o relatório. Tente novamente.'], 500);
        }

        $project->update([
            'ai_summary'    => $summary,
            'ai_summary_at' => now(),
        ]);

        return response()->json([
            'summary'    => $summary,
            'summary_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function callDeepSeek(string $prompt): ?string
    {
        try {
            $ds     = new DeepSeekService();
            $result = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $text   = $result['choices'][0]['message']['content'] ?? null;
            return $text ? trim($text) : null;
        } catch (\Exception $e) {
            Log::warning("ProjectLog DeepSeek: " . $e->getMessage());
            return null;
        }
    }
}
