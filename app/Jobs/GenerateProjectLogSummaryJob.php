<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectLog;
use App\Services\DeepSeekService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProjectLogSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(private int $projectId, private int $tenantId) {}

    public function handle(): void
    {
        $project = Project::where('id', $this->projectId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$project) return;

        try {
            $logs = ProjectLog::where('project_id', $project->id)
                ->where('tenant_id', $this->tenantId)
                ->orderBy('created_at', 'asc')
                ->with('user:id,name')
                ->get();

            if ($logs->count() < 3) {
                $project->update(['ai_summary_status' => 'failed']);
                return;
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

            $ds      = new DeepSeekService();
            $result  = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $summary = trim($result['choices'][0]['message']['content'] ?? '');

            if (!$summary) {
                throw new \RuntimeException('DeepSeek retornou vazio para resumo do projeto #' . $this->projectId);
            }

            $project->update([
                'ai_summary'        => $summary,
                'ai_summary_at'     => now(),
                'ai_summary_status' => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::error("GenerateProjectLogSummaryJob project #{$this->projectId}: {$e->getMessage()}");
            $project->update(['ai_summary_status' => 'failed']);
            throw $e;
        }
    }
}
