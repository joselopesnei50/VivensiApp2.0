<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\StrategyRoom\FinancialAgentService;
use Illuminate\Console\Command;

/**
 * Sala de Estrategia — Fase 0. Teste manual do Agente Financeiro.
 *
 * Uso:
 *   php artisan strategy:test-financial {tenant_id}
 *
 * Requer STRATEGY_ROOM_ENABLED=true no .env. Sem autenticacao web —
 * roda direto no terminal pra validacao humana da qualidade da fala
 * antes de qualquer UI (Fase 0 do roadmap).
 */
class StrategyTestFinancial extends Command
{
    protected $signature   = 'strategy:test-financial {tenant_id : ID do tenant real}';
    protected $description = 'Testa o Agente Financeiro da Sala de Estrategia contra dado real do tenant.';

    public function handle(FinancialAgentService $agent): int
    {
        if (!config('strategy_room.enabled')) {
            $this->error('Feature flag desativada. Ative STRATEGY_ROOM_ENABLED=true no .env e rode "php artisan config:clear".');
            return self::FAILURE;
        }

        $tenantId = (int) $this->argument('tenant_id');
        if ($tenantId <= 0) {
            $this->error('tenant_id invalido.');
            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant #{$tenantId} nao encontrado.");
            return self::FAILURE;
        }

        $this->line('');
        $this->info("=== Sala de Estrategia — Agente Financeiro ===");
        $this->line("Tenant: <fg=cyan>{$tenant->name}</> (#{$tenant->id}, tipo={$tenant->type})");
        $this->line("Rodada em: " . now()->toDateTimeString());
        $this->line('');

        $t0     = microtime(true);
        $result = $agent->speak($tenantId);
        $ms     = (int) ((microtime(true) - $t0) * 1000);

        if (isset($result['error'])) {
            $this->line('');
            $this->error('ERRO: ' . $result['error']);
            if (!empty($result['session_id'])) {
                $this->line("(session #{$result['session_id']} marcada como concluida sem fala)");
            }
            return self::FAILURE;
        }

        $this->line('');
        $this->line('<fg=yellow>─── Fala ────────────────────────────────────────</>');
        $this->line($result['fala']);
        $this->line('');

        $this->line('<fg=yellow>─── Fatos usados ───────────────────────────────</>');
        if (empty($result['fatos_usados'])) {
            $this->line('  (nenhum fato citado)');
        } else {
            foreach ($result['fatos_usados'] as $f) {
                $this->line("  · <fg=cyan>{$f}</>");
            }
        }
        $this->line('');

        $confColor = match ($result['confianca']) {
            'alta'  => 'green',
            'media' => 'yellow',
            'baixa' => 'red',
            default => 'white',
        };
        $this->line("<fg=yellow>─── Confianca ─────────────────────────────────</>");
        $this->line("  <fg={$confColor}>{$result['confianca']}</>");
        $this->line('');

        $this->line("<fg=gray>session #{$result['session_id']} · message #{$result['message_id']} · {$ms}ms</>");
        return self::SUCCESS;
    }
}
