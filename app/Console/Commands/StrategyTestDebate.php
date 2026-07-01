<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\StrategyRoom\StrategyDebateOrchestrator;
use Illuminate\Console\Command;

/**
 * Sala de Estrategia — Fase 1. Roda o debate completo (Financeiro +
 * Inteligencia + Estrategista-Chefe) contra um tenant real.
 *
 * Uso:
 *   php artisan strategy:test-debate {tenant_id}
 *
 * Requer STRATEGY_ROOM_ENABLED=true. Sem UI — validacao humana no terminal
 * antes de qualquer tela (Fase 2 do roadmap).
 */
class StrategyTestDebate extends Command
{
    protected $signature   = 'strategy:test-debate {tenant_id : ID do tenant real}';
    protected $description = 'Roda o debate completo da Sala de Estrategia (Financeiro + Inteligencia + Estrategista-Chefe).';

    public function handle(StrategyDebateOrchestrator $orchestrator): int
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
        $this->info('=== Sala de Estrategia — Debate Completo ===');
        $this->line("Tenant: <fg=cyan>{$tenant->name}</> (#{$tenant->id}, tipo={$tenant->type})");
        $this->line('Rodada em: ' . now()->toDateTimeString());
        $this->line('');

        $t0     = microtime(true);
        $result = $orchestrator->run($tenantId);
        $ms     = (int) ((microtime(true) - $t0) * 1000);

        $this->line("<fg=gray>session #{$result['session_id']} · {$ms}ms totais</>");
        $this->line('');

        $this->renderAgent('FINANCEIRO',    $result['financeiro']);
        $this->renderAgent('INTELIGENCIA',  $result['inteligencia']);
        $this->renderAgent('ESTRATEGISTA-CHEFE', $result['sintese'], sintese: true);

        if (!empty($result['erros'])) {
            $this->line('');
            $this->line('<fg=red>─── Erros parciais ───────────────────────────</>');
            foreach ($result['erros'] as $e) {
                $this->line("  · <fg=red>[{$e['agente']}]</> {$e['msg']}");
            }
        }

        return self::SUCCESS;
    }

    private function renderAgent(string $label, ?array $data, bool $sintese = false): void
    {
        $this->line('');
        $accent = $sintese ? 'magenta' : 'yellow';
        $this->line("<fg={$accent}>═══ {$label} ═══════════════════════════════════</>");

        if ($data === null) {
            $this->line('  <fg=red>(agente nao falou nesta sessao)</>');
            return;
        }

        $this->line('');
        $this->line($data['fala']);
        $this->line('');

        $this->line('<fg=gray>Fatos usados:</>');
        if (empty($data['fatos_usados'])) {
            $this->line('  <fg=gray>(nenhum)</>');
        } else {
            foreach ($data['fatos_usados'] as $f) {
                $this->line("  · <fg=cyan>{$f}</>");
            }
        }

        $confColor = match ($data['confianca']) {
            'alta'  => 'green',
            'media' => 'yellow',
            'baixa' => 'red',
            default => 'white',
        };
        $this->line("<fg=gray>Confianca:</> <fg={$confColor}>{$data['confianca']}</>");
        $this->line("<fg=gray>message #{$data['message_id']}</>");
    }
}
