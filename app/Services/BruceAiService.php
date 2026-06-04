<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\ProjectGoal;
use App\Models\ProjectLog;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTimelineRecord;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BruceAiService — Assistente contextual DeepSeek com memória por tenant.
 *
 * - Mantém histórico de conversa (Redis, TTL 30 min, max 10 mensagens)
 * - Injeta métricas do tenant no system prompt automaticamente
 * - Usa DeepSeek como provider principal, sem dependência de Copilot
 */
class BruceAiService
{
    const HISTORY_TTL = 3600; // 1 hora
    const MAX_HISTORY = 20;   // mensagens mantidas por tenant

    public function __construct(
        private DeepSeekService $deepSeek
    ) {}

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function chat(
        string $userMessage,
        int $tenantId,
        string $role = 'common',
        int $userId = 0,
        ?string $contextType = null,
        ?int $contextId = null
    ): array {
        $history = $this->getHistory($tenantId, $userId, $contextType, $contextId);

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($tenantId, $role, $contextType, $contextId)]],
            $history,
            [['role' => 'user', 'content' => $userMessage]]
        );

        $response = $this->deepSeek->chat($messages);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        $reply = data_get($response, 'choices.0.message.content', 'Não consegui processar sua mensagem.');

        // Persistir histórico (user + assistant) — chave isolada por contexto
        $newHistory = array_merge($history, [
            ['role' => 'user',      'content' => $userMessage],
            ['role' => 'assistant', 'content' => $reply],
        ]);

        // Manter apenas as últimas N mensagens
        if (count($newHistory) > self::MAX_HISTORY) {
            $newHistory = array_slice($newHistory, -self::MAX_HISTORY);
        }

        $this->saveHistory($tenantId, $userId, $newHistory, $contextType, $contextId);

        return [
            'reply'     => $reply,
            'tokens'    => data_get($response, 'usage.total_tokens', 0),
            'timestamp' => now()->toIso8601String(),
            'context'   => $contextType ? ['type' => $contextType, 'id' => $contextId] : null,
        ];
    }

    public function clearHistory(int $tenantId, int $userId = 0, ?string $contextType = null, ?int $contextId = null): void
    {
        Cache::forget($this->historyKey($tenantId, $userId, $contextType, $contextId));
    }

    // ── Insight proativo (usado no dashboard, cache 6h) ───────────────────────

    public function dailyInsight(int $tenantId, string $role = 'common'): string
    {
        $cacheKey = "bruce.insight.{$tenantId}." . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 21600, function () use ($tenantId, $role) {
            $ctx = $this->tenantContext($tenantId);

            $prompt = "Em no máximo 2 frases diretas e profissionais, dê um insight acionável sobre a situação financeira/operacional atual. "
                . "Sem saudação, sem emojis, sem menções a animais. "
                . "Dados: saldo R$ {$this->fmt($ctx['balance'])}, receitas R$ {$this->fmt($ctx['income'])}, "
                . "despesas R$ {$this->fmt($ctx['expense'])}, {$ctx['active_projects']} projetos ativos, "
                . "{$ctx['overdue_tasks']} tarefas vencidas.";

            $response = $this->deepSeek->chat([
                ['role' => 'system', 'content' => $this->buildSystemPrompt($tenantId, $role)],
                ['role' => 'user',   'content' => $prompt],
            ]);

            return data_get($response, 'choices.0.message.content', '')
                ?: 'Adicione mais transações para gerar insights precisos.';
        });
    }

    // ── System prompt contextual ──────────────────────────────────────────────

    private function buildSystemPrompt(int $tenantId, string $role, ?string $contextType = null, ?int $contextId = null): string
    {
        $ctx = $this->tenantContext($tenantId);

        $roleContext = match ($role) {
            'ngo'         => "O usuário gerencia uma ONG/OSC. Use termos do terceiro setor: doadores, editais, captação, beneficiários, voluntários, prestação de contas, transparência. Jamais use termos de SaaS, startup, MRR ou ARR.",
            'manager'     => "O usuário é gestor de projetos e equipes. Foque em: projetos, tarefas, produtividade, aprovações de despesas, fluxo de caixa e desempenho da equipe.",
            'super_admin' => "O usuário é o administrador da plataforma Vivensi. Ele gerencia todos os clientes (tenants), planos de assinatura, saúde do sistema e pipeline comercial. Forneça visão estratégica de plataforma: MRR, churn, conversão de leads, crescimento de clientes ativos, saúde da infraestrutura.",
            default       => "O usuário gerencia suas finanças e tarefas pessoais/empresariais. Foque em: saldo, receitas, despesas, fluxo de caixa, tarefas pendentes e metas financeiras.",
        };

        $systemCapabilities = <<<CAP
## Funcionalidades do sistema Vivensi que você pode explicar:
- **Finanças**: lançar receitas e despesas, aprovar/rejeitar transações, conciliação bancária, relatórios financeiros por período, fluxo de caixa semestral.
- **Projetos**: criar e acompanhar projetos com orçamento, progresso de tarefas, equipe alocada, relatório de gastos por projeto.
- **Tarefas**: criar tarefas, atribuir a membros, definir prazo e prioridade (crítica/alta/média/baixa), acompanhar status.
- **WhatsApp CRM**: atender clientes/contatos via chat, disparar mensagens em massa, configurar bot com IA, automações.
- **Equipe/RH**: cadastrar membros, definir hierarquia, acompanhar supervisor e departamento.
- **Clientes/Prospecção**: gestão de clientes, funil de prospecção com análise por IA.
- **Marketing**: estratégias de marketing geradas por IA, hub de conteúdo para redes sociais.
- **Landing Pages**: criar páginas de captura vinculadas à conta.
- **Relatórios**: exportar dados em CSV, relatórios de auditoria, log de atividades.
- **Configurações**: integrações (WhatsApp, pagamentos), dados da organização, personalização de marca.
CAP;

        $orgBlock      = $ctx['org_name']
            ? "## ORGANIZAÇÃO\nVocê está respondendo para **{$ctx['org_name']}**" . ($ctx['org_type'] ? " ({$ctx['org_type']})" : '') . ".\n"
            : '';

        $trainingBlock = $ctx['ai_training']
            ? "## INSTRUÇÕES ESPECÍFICAS DA ORGANIZAÇÃO\n{$ctx['ai_training']}\n"
            : '';

        $contextBlock = '';
        if ($contextType === 'project' && $contextId && config('bruce.context_project_enabled', false)) {
            $contextBlock = $this->projectContext($contextId, $tenantId);
        }

        return <<<PROMPT
Você é Bruce, assistente de inteligência artificial do sistema Vivensi.

## IDENTIDADE E TOM (OBRIGATÓRIO — nunca ignore estas regras)
- Você é um assistente profissional, direto e inteligente.
- Tom: formal mas acessível. Nunca use linguagem infantil, piadas ou metáforas de animais.
- PROIBIDO absolutamente: referências a cachorro, Golden Retriever, latir, 🐾, ou qualquer linguagem de mascote/animal. Isso é inadequado e ofensivo.
- NÃO use emojis excessivos. No máximo 1 emoji por resposta, apenas quando realmente agrega valor.
- Não se apresente repetidamente. Se o usuário já iniciou uma conversa, responda diretamente ao que foi perguntado.
- Respostas curtas para perguntas simples. Use Markdown (negrito, listas) quando organiza melhor a informação.
- Idioma: português do Brasil.

{$orgBlock}
## PAPEL
{$roleContext}

{$trainingBlock}
{$systemCapabilities}

## DADOS ATUAIS DA CONTA (em tempo real)
- Receitas do mês: R$ {$this->fmt($ctx['income'])}
- Despesas do mês: R$ {$this->fmt($ctx['expense'])}
- Saldo do mês: R$ {$this->fmt($ctx['balance'])}
- Projetos ativos: {$ctx['active_projects']}
- Tarefas em aberto: {$ctx['open_tasks']}
- Tarefas vencidas: {$ctx['overdue_tasks']}
- Data: {$this->today()}

Use esses dados para responder perguntas sobre finanças, projetos e tarefas sem pedir que o usuário os forneça novamente.

{$contextBlock}
PROMPT;
    }

    /**
     * Bloco de contexto específico de um projeto. Injetado no system prompt
     * quando context_type=project. Cacheado 5 min por projeto.
     * Quando o feature flag bruce.context_project_enabled está off, retorna ''.
     */
    private function projectContext(int $projectId, int $tenantId): string
    {
        $cacheKey = "bruce.proj_ctx.{$tenantId}.{$projectId}";

        return Cache::remember($cacheKey, 300, function () use ($projectId, $tenantId) {
            $project = Project::where('tenant_id', $tenantId)
                ->where('id', $projectId)
                ->first();

            if (!$project) {
                return '';
            }

            // KPIs operacionais (sem expor valores brutos — agregados)
            $openTasks    = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereNotIn('status', ['done', 'completed'])->count();
            $doneTasks    = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereIn('status', ['done', 'completed'])->count();
            $overdueTasks = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereNotIn('status', ['done', 'completed'])
                ->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count();

            $totalSpent = (float) Transaction::where('tenant_id', $tenantId)
                ->where('project_id', $projectId)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->sum('amount');

            $memberCount = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $projectId)
                ->count();

            // Últimos 3 logs do diário (resumo curto)
            $lastLogs = ProjectLog::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['created_at', 'body'])
                ->map(fn ($l) => '- ' . $l->created_at->format('d/m') . ': ' . \Illuminate\Support\Str::limit((string) $l->body, 140))
                ->implode("\n");

            // Próximo marco da timeline (registros historicos com data futura)
            $nextMilestone = ProjectTimelineRecord::where('project_id', $projectId)
                ->whereDate('date', '>=', now())
                ->orderBy('date', 'asc')
                ->first(['title', 'date', 'type']);

            // Planejamento (atras de feature flag — so injeta se a feature
            // estiver ligada para este ambiente)
            $planningBlock = '';
            if (config('planning.enabled')) {
                $goalCounts = ProjectGoal::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray();
                $goalsTotal = array_sum($goalCounts);

                $nextPlannedMilestone = ProjectMilestone::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->where('status', 'pending')
                    ->whereDate('target_date', '>=', now())
                    ->orderBy('target_date', 'asc')
                    ->first(['title', 'target_date']);
                $overdueMilestones = ProjectMilestone::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->where('status', 'pending')
                    ->whereDate('target_date', '<', now())
                    ->count();

                if ($goalsTotal > 0 || $nextPlannedMilestone || $overdueMilestones > 0) {
                    $goalsLine = $goalsTotal > 0
                        ? "Metas: {$goalsTotal} total (" .
                          ($goalCounts['completed'] ?? 0) . " concluidas, " .
                          ($goalCounts['in_progress'] ?? 0) . " em andamento, " .
                          ($goalCounts['not_started'] ?? 0) . " nao iniciadas)"
                        : 'Metas: nenhuma cadastrada';

                    $plannedMilestoneLine = $nextPlannedMilestone
                        ? "Proximo marco planejado: \"{$nextPlannedMilestone->title}\" em " . $nextPlannedMilestone->target_date->format('d/m/Y')
                        : 'Nenhum marco pendente futuro';

                    $overdueLine = $overdueMilestones > 0
                        ? "Marcos em atraso: {$overdueMilestones}"
                        : 'Marcos em atraso: 0';

                    $planningBlock = "\n### Planejamento\n- {$goalsLine}\n- {$plannedMilestoneLine}\n- {$overdueLine}";
                }
            }

            $statusLabel = match ($project->status) {
                'active'      => 'em execução',
                'paused'      => 'pausado',
                'completed'   => 'concluído',
                'canceled'    => 'cancelado',
                'in_progress' => 'em execução',
                default       => $project->status ?? 'sem status',
            };

            $budget = $project->budget ? 'R$ ' . $this->fmt((float) $project->budget) : 'sem orçamento definido';
            $usedPct = ($project->budget && $project->budget > 0) ? round(($totalSpent / $project->budget) * 100) : null;
            $budgetLine = $usedPct !== null
                ? "Orçamento: {$budget} (usado: {$usedPct}%)"
                : "Orçamento: {$budget}";

            $milestoneLine = $nextMilestone
                ? "Próximo marco: \"{$nextMilestone->title}\" em " . \Carbon\Carbon::parse($nextMilestone->date)->format('d/m/Y')
                : 'Próximo marco: nenhum agendado';

            $logsBlock = $lastLogs ? "\n## Últimas entradas no diário\n{$lastLogs}" : '';

            return <<<PCTX

## CONTEXTO DO PROJETO ATUAL
Você está respondendo perguntas sobre o projeto **{$project->name}** (ID #{$project->id}).
O usuário quer falar sobre ESTE projeto especificamente.

### Estado atual
- Status: {$statusLabel}
- {$budgetLine}
- Tarefas: {$doneTasks} concluídas / {$openTasks} abertas / {$overdueTasks} vencidas
- Equipe: {$memberCount} membros vinculados
- {$milestoneLine}
{$planningBlock}
{$logsBlock}

### Regras para este contexto
- Foque suas respostas neste projeto, salvo quando o usuário pedir comparação.
- Quando sugerir ações, prefira coisas que podem ser feitas dentro da tela do projeto (criar tarefa, registrar marco, escrever no diário, lançar transação, contatar membro).
- Não invente dados que não estão no estado acima. Se faltar info, diga que precisa ser registrada.
PCTX;
        });
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function today(): string
    {
        return now()->translatedFormat('d \d\e F \d\e Y');
    }

    // ── Métricas do tenant (cacheadas 5 min) ──────────────────────────────────

    private function tenantContext(int $tenantId): array
    {
        return Cache::remember("bruce.ctx.{$tenantId}", 300, function () use ($tenantId) {
            $income  = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');
            $expense = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');

            $tenant    = Tenant::find($tenantId);
            $waConfig  = WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();

            return [
                'income'          => $income,
                'expense'         => $expense,
                'balance'         => $income - $expense,
                'active_projects' => Project::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'open_tasks'      => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->count(),
                'overdue_tasks'   => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
                'org_name'        => $tenant?->brand_name ?: $tenant?->name,
                'org_type'        => $tenant?->type,
                'ai_training'     => $waConfig?->ai_training,
            ];
        });
    }

    // ── Redis history helpers ─────────────────────────────────────────────────

    private function historyKey(int $tenantId, int $userId, ?string $contextType = null, ?int $contextId = null): string
    {
        $base = "bruce.history.{$tenantId}.{$userId}";
        if ($contextType && $contextId) {
            return "{$base}.{$contextType}.{$contextId}";
        }
        return $base;
    }

    private function getHistory(int $tenantId, int $userId, ?string $contextType = null, ?int $contextId = null): array
    {
        return Cache::get($this->historyKey($tenantId, $userId, $contextType, $contextId), []);
    }

    private function saveHistory(int $tenantId, int $userId, array $messages, ?string $contextType = null, ?int $contextId = null): void
    {
        Cache::put($this->historyKey($tenantId, $userId, $contextType, $contextId), $messages, self::HISTORY_TTL);
    }
}
