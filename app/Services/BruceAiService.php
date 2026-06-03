<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
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

    public function chat(string $userMessage, int $tenantId, string $role = 'common', int $userId = 0): array
    {
        $history = $this->getHistory($tenantId, $userId);

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($tenantId, $role)]],
            $history,
            [['role' => 'user', 'content' => $userMessage]]
        );

        $response = $this->deepSeek->chat($messages);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        $reply = data_get($response, 'choices.0.message.content', 'Não consegui processar sua mensagem.');

        // Persistir histórico (user + assistant)
        $newHistory = array_merge($history, [
            ['role' => 'user',      'content' => $userMessage],
            ['role' => 'assistant', 'content' => $reply],
        ]);

        // Manter apenas as últimas N mensagens
        if (count($newHistory) > self::MAX_HISTORY) {
            $newHistory = array_slice($newHistory, -self::MAX_HISTORY);
        }

        $this->saveHistory($tenantId, $userId, $newHistory);

        return [
            'reply'     => $reply,
            'tokens'    => data_get($response, 'usage.total_tokens', 0),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function clearHistory(int $tenantId, int $userId = 0): void
    {
        Cache::forget($this->historyKey($tenantId, $userId));
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

    private function buildSystemPrompt(int $tenantId, string $role): string
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
PROMPT;
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

    private function historyKey(int $tenantId, int $userId): string
    {
        return "bruce.history.{$tenantId}.{$userId}";
    }

    private function getHistory(int $tenantId, int $userId): array
    {
        return Cache::get($this->historyKey($tenantId, $userId), []);
    }

    private function saveHistory(int $tenantId, int $userId, array $messages): void
    {
        Cache::put($this->historyKey($tenantId, $userId), $messages, self::HISTORY_TTL);
    }
}
