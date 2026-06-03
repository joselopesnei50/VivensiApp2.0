<?php

namespace App\Jobs;

use App\Models\NgoGrant;
use App\Services\DeepSeekService;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateGrantAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(private int $grantId, private int $tenantId) {}

    public function handle(): void
    {
        $grant = NgoGrant::where('id', $this->grantId)
            ->where('tenant_id', $this->tenantId)
            ->with(['project.transactions', 'project.tasks'])
            ->first();

        if (!$grant) return;

        try {
            $tenant  = \App\Models\Tenant::find($this->tenantId);
            $orgName = $tenant?->corpo_name ?? $tenant?->name ?? 'Nossa Organização';

            $deadline  = $grant->deadline?->format('d/m/Y') ?? 'Não informado';
            $daysLeft  = $grant->deadline ? now()->diffInDays($grant->deadline, false) : null;
            $prazoInfo = $daysLeft !== null
                ? ($daysLeft >= 0 ? "{$deadline} ({$daysLeft} dias restantes)" : "{$deadline} (vencido há " . abs((int)$daysLeft) . " dias)")
                : 'Não informado';

            $valor       = 'R$ ' . number_format((float) $grant->value, 2, ',', '.');
            $statusLabel = ['open' => 'Ativo', 'reporting' => 'Prestação de Contas', 'closed' => 'Encerrado'][$grant->status] ?? $grant->status;

            $project      = $grant->project;
            $transactions = $project?->transactions ?? collect();
            $tasks        = $project?->tasks ?? collect();
            $totalIncome  = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
            $tasksDone    = $tasks->where('status', 'done')->count();
            $tasksTotal   = $tasks->count();
            $budget       = (float) ($project?->budget ?? $grant->value ?? 0);
            $usedPercent  = $budget > 0 ? min(100, round(($totalExpense / $budget) * 100)) : 0;

            $contextFinanceiro = $project
                ? "Captado: {$totalIncome} | Gasto: {$totalExpense} | Orçamento utilizado: {$usedPercent}% | Tarefas: {$tasksDone}/{$tasksTotal} concluídas"
                : 'Nenhum projeto associado ainda.';

            $isReporting = $grant->status === 'reporting';

            $prompt = <<<PROMPT
Você é um especialista em gestão de convênios e editais para o Terceiro Setor brasileiro.
Analise o seguinte edital/convênio e gere uma análise técnica detalhada.

## DADOS DO EDITAL
- **Organização:** {$orgName}
- **Título:** {$grant->title}
- **Concedente:** {$grant->agency}
- **Valor:** {$valor}
- **Prazo:** {$prazoInfo}
- **Status atual:** {$statusLabel}
- **Requisitos/Observações:** {$grant->notes}
- **Progresso financeiro e operacional:** {$contextFinanceiro}

## ANÁLISE SOLICITADA
Gere uma análise estruturada em Markdown com EXATAMENTE estas seções:

### 🎯 Viabilidade
Avalie a viabilidade de executar/captar este edital com uma pontuação de 1 a 10 e justificativa objetiva em 2-3 linhas.

### ✅ Checklist de Requisitos
Liste em checkboxes (`- [ ]`) os principais requisitos que precisam ser atendidos ou verificados.

### ⚠️ Riscos Identificados
Liste os 3-5 principais riscos com nível (🔴 Alto / 🟡 Médio / 🟢 Baixo) e breve descrição.

### 📋 Próximas Ações
Liste 3-5 ações concretas e prioritárias com prazo sugerido.

### 📅 Cronograma Sugerido
Sugira 4-6 marcos/milestones com estimativa de prazo relativo ao deadline do edital.
PROMPT;

            if ($isReporting) {
                $prompt .= <<<REPORTING

### 📊 Análise de Prestação de Contas
Com base no progresso financeiro ({$usedPercent}% do orçamento utilizado, {$tasksDone}/{$tasksTotal} tarefas concluídas), avalie:
- Conformidade com o planejado
- Pontos de atenção para o relatório final
- Documentos essenciais a providenciar
REPORTING;
            }

            $prompt .= "\n\nResponda APENAS em Português brasileiro. Seja direto, objetivo e prático.";

            $ds       = new DeepSeekService();
            $result   = $ds->chat([
                ['role' => 'system', 'content' => 'Você é um especialista em captação de recursos e gestão de convênios para ONGs brasileiras.'],
                ['role' => 'user',   'content' => $prompt],
            ]);

            $analysis = trim($result['choices'][0]['message']['content'] ?? '');

            if (empty($analysis)) {
                $gemini   = new GeminiService();
                $analysis = trim($gemini->generateText($prompt) ?? '');
            }

            if (empty($analysis)) {
                throw new \RuntimeException('Ambos DeepSeek e Gemini retornaram vazio para análise do edital #' . $this->grantId);
            }

            $grant->update([
                'ai_analysis'        => $analysis,
                'ai_analysis_status' => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::error("GenerateGrantAnalysisJob grant #{$this->grantId}: {$e->getMessage()}");
            $grant->update(['ai_analysis_status' => 'failed']);
            throw $e;
        }
    }
}
