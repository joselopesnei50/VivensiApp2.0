<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Task;
use App\Models\Project;
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
    const HISTORY_TTL = 1800; // 30 minutos
    const MAX_HISTORY = 10;   // mensagens mantidas por tenant

    public function __construct(
        private DeepSeekService $deepSeek
    ) {}

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function chat(string $userMessage, int $tenantId, string $role = 'common'): array
    {
        $history = $this->getHistory($tenantId);

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

        $this->saveHistory($tenantId, $newHistory);

        return [
            'reply'     => $reply,
            'tokens'    => data_get($response, 'usage.total_tokens', 0),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function clearHistory(int $tenantId): void
    {
        Cache::forget($this->historyKey($tenantId));
    }

    // ── System prompt contextual ──────────────────────────────────────────────

    private function buildSystemPrompt(int $tenantId, string $role): string
    {
        $ctx = $this->tenantContext($tenantId);

        $persona = match ($role) {
            'ngo'     => 'consultor(a) especializado(a) em Terceiro Setor (ONG/OSC)',
            'manager' => 'consultor(a) de gestão de projetos e finanças corporativas',
            default   => 'assistente financeiro e operacional',
        };

        $ngoExtra = $role === 'ngo'
            ? "\n- Linguagem: use termos de ONG (doadores, editais, captação, beneficiários). Evite SaaS, MRR, startup."
            : "";

        return "Você é Bruce, {$persona} integrado ao sistema Vivensi.\n"
            . "Responda de forma direta, profissional e em português do Brasil.\n"
            . "Não use saudações excessivas. Use Markdown quando útil.{$ngoExtra}\n\n"
            . "## Contexto atual do tenant\n"
            . "- Saldo do mês: R$ " . number_format($ctx['balance'], 2, ',', '.') . "\n"
            . "- Receitas (mês atual): R$ " . number_format($ctx['income'], 2, ',', '.') . "\n"
            . "- Despesas (mês atual): R$ " . number_format($ctx['expense'], 2, ',', '.') . "\n"
            . "- Projetos ativos: {$ctx['active_projects']}\n"
            . "- Tarefas em aberto: {$ctx['open_tasks']}\n"
            . "- Tarefas vencidas: {$ctx['overdue_tasks']}\n"
            . "Data atual: " . now()->translatedFormat('d \d\e F \d\e Y') . ".";
    }

    // ── Métricas do tenant (cacheadas 5 min) ──────────────────────────────────

    private function tenantContext(int $tenantId): array
    {
        return Cache::remember("bruce.ctx.{$tenantId}", 300, function () use ($tenantId) {
            $income  = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');
            $expense = (float) Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');

            return [
                'income'          => $income,
                'expense'         => $expense,
                'balance'         => $income - $expense,
                'active_projects' => Project::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'open_tasks'      => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->count(),
                'overdue_tasks'   => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
            ];
        });
    }

    // ── Redis history helpers ─────────────────────────────────────────────────

    private function historyKey(int $tenantId): string
    {
        return "bruce.history.{$tenantId}";
    }

    private function getHistory(int $tenantId): array
    {
        return Cache::get($this->historyKey($tenantId), []);
    }

    private function saveHistory(int $tenantId, array $messages): void
    {
        Cache::put($this->historyKey($tenantId), $messages, self::HISTORY_TTL);
    }
}
