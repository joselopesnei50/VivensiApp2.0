<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Fonte unica do "contexto agregado do tenant" (saldo/receita/despesa do mes,
 * projetos ativos, tarefas em aberto/vencidas, dados de marca e treinamento
 * de IA).
 *
 * Extraido do BruceAiService::tenantContext() pra ser reutilizado por outros
 * consumidores de dado real do painel (ex: Sala de Estrategia — Fase 0 do
 * Agente Financeiro). Mantem a mesma chave/TTL do Redis pra preservar cache
 * quente ja existente em producao.
 *
 * Nunca inclui PII individual — so agregados. Alinhado com a disciplina
 * discutida em md/vivensi-sala-estrategia-arquitetura.md §5.
 */
class TenantContextService
{
    public const CACHE_TTL = 300; // 5 min

    /**
     * Contexto agregado do tenant. Cache por 5 min.
     *
     * Shape:
     * - income, expense, balance  (float, mes corrente, status=paid)
     * - active_projects, open_tasks, overdue_tasks  (int)
     * - org_name (string|null), org_type (string|null)
     * - ai_training (string|null) — instrucoes do chatbot WhatsApp
     */
    public function for(int $tenantId): array
    {
        return Cache::remember($this->cacheKey($tenantId), self::CACHE_TTL, function () use ($tenantId) {
            $income  = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')->where('status', 'paid')
                ->whereMonth('date', now()->month)->sum('amount');

            $expense = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')->where('status', 'paid')
                ->whereMonth('date', now()->month)->sum('amount');

            $tenant   = Tenant::find($tenantId);
            $waConfig = WhatsappConfig::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)->first();

            return [
                'income'          => $income,
                'expense'         => $expense,
                'balance'         => $income - $expense,
                'active_projects' => Project::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'open_tasks'      => Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->count(),
                'overdue_tasks'   => Task::where('tenant_id', $tenantId)
                    ->whereNotIn('status', ['done', 'completed'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()->toDateString())
                    ->count(),
                'org_name'        => $tenant?->brand_name ?: $tenant?->name,
                'org_type'        => $tenant?->type,
                'ai_training'            => $waConfig?->ai_training,
                'ai_training_structured' => $waConfig?->ai_training_structured,
            ];
        });
    }

    /**
     * Chave Redis identica a antiga do BruceAiService — preserva cache
     * quente em producao (bruce.ctx.v2.{tenantId}).
     */
    public function cacheKey(int $tenantId): string
    {
        return "bruce.ctx.v2.{$tenantId}";
    }

    public function forget(int $tenantId): void
    {
        Cache::forget($this->cacheKey($tenantId));
    }
}
