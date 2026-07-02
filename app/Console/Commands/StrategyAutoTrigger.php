<?php

namespace App\Console\Commands;

use App\Jobs\RunStrategyDebateJob;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\StrategySession;
use App\Models\User;
use App\Services\ProjectHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sala de Estrategia — Fase 3.1. Trigger automatico por queda de health score.
 *
 * Agendado diario (07:30). Pra cada tenant com projeto ativo:
 *   1. Grava snapshot de health de cada projeto (alimenta ProjectHealthHistory,
 *      que antes nao tinha produtor automatico).
 *   2. Se o overall_score de algum projeto caiu >= threshold vs o snapshot
 *      anterior, convoca reuniao (trigger_type=auto_health_drop) e notifica
 *      os usuarios do tenant.
 *
 * Protecoes de custo: cooldown por tenant, cota diaria do tenant e cap
 * global diario de reunioes automaticas. Flag propria (auto_trigger) OFF
 * por default — independente da flag geral do modulo.
 */
class StrategyAutoTrigger extends Command
{
    public const TRIGGER_TYPE = 'auto_health_drop';

    protected $signature   = 'strategy:auto-trigger {--dry-run : Avalia e loga sem convocar reuniao nem notificar}';
    protected $description = 'Grava snapshots de health dos projetos ativos e convoca reuniao estrategica quando detecta queda de score.';

    public function handle(ProjectHealthService $health): int
    {
        if (!config('strategy_room.enabled') || !config('strategy_room.auto_trigger')) {
            $this->line('Trigger automatico desativado (strategy_room.enabled + strategy_room.auto_trigger precisam estar true).');
            return self::SUCCESS;
        }

        $dryRun    = (bool) $this->option('dry-run');
        $threshold = max(1, (int) config('strategy_room.auto_trigger_drop_threshold', 10));
        $cooldown  = max(1, (int) config('strategy_room.auto_trigger_cooldown_days', 7));
        $globalCap = (int) config('strategy_room.auto_trigger_global_daily_cap', 20);
        $quota     = (int) config('strategy_room.daily_quota', 10);

        $dispatchedToday = StrategySession::withoutGlobalScopes()
            ->where('trigger_type', self::TRIGGER_TYPE)
            ->whereDate('created_at', today())
            ->count();

        $tenantIds = Project::withoutGlobalScopes()
            ->where('status', 'active')
            ->distinct()
            ->pluck('tenant_id')
            ->filter()
            ->values();

        $this->info(sprintf(
            'Varredura: %d tenant(s) com projeto ativo · threshold=%d pts · cooldown=%dd · cap global=%d (%d ja usadas hoje)%s',
            $tenantIds->count(), $threshold, $cooldown, $globalCap, $dispatchedToday, $dryRun ? ' · DRY-RUN' : ''
        ));

        $convocadas = 0;

        foreach ($tenantIds as $tenantId) {
            $tenantId = (int) $tenantId;

            $drops = $this->snapshotAndDetectDrops($health, $tenantId, $threshold);
            if (empty($drops)) {
                continue;
            }

            $worst = collect($drops)->sortByDesc('drop')->first();
            $this->line(sprintf(
                'Tenant #%d: queda detectada — "%s" %d → %d (-%d pts)',
                $tenantId, $worst['name'], $worst['from'], $worst['to'], $worst['drop']
            ));

            // ── Protecoes de custo ────────────────────────────────────────
            if ($globalCap > 0 && $dispatchedToday >= $globalCap) {
                $this->warn("Cap global diario atingido ({$globalCap}) — tenants restantes ficam pra amanha.");
                break;
            }

            $emCooldown = StrategySession::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('trigger_type', self::TRIGGER_TYPE)
                ->where('created_at', '>=', now()->subDays($cooldown))
                ->exists();
            if ($emCooldown) {
                $this->line("  → pulado: reuniao automatica nos ultimos {$cooldown} dias (cooldown).");
                continue;
            }

            if ($quota > 0) {
                $hoje = StrategySession::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('created_at', today())
                    ->count();
                if ($hoje >= $quota) {
                    $this->line("  → pulado: cota diaria do tenant atingida ({$quota}).");
                    continue;
                }
            }

            if ($dryRun) {
                $this->line('  → DRY-RUN: convocaria reuniao + notificaria usuarios.');
                continue;
            }

            // ── Convoca ───────────────────────────────────────────────────
            $session = StrategySession::create([
                'tenant_id'    => $tenantId,
                'trigger_type' => self::TRIGGER_TYPE,
                'status'       => 'em_andamento',
            ]);

            RunStrategyDebateJob::dispatch($tenantId, $session->id);
            $dispatchedToday++;
            $convocadas++;

            $this->notifyTenantUsers($tenantId, $session->id, $worst);
            $this->info("  → reuniao #{$session->id} convocada.");

            Log::info('StrategyRoom/AutoTrigger: reuniao convocada', [
                'tenant'  => $tenantId,
                'session' => $session->id,
                'project' => $worst['project_id'],
                'drop'    => $worst['drop'],
            ]);
        }

        $this->info("Concluido: {$convocadas} reuniao(oes) convocada(s).");
        return self::SUCCESS;
    }

    /**
     * Grava o snapshot de hoje (se ainda nao existe) pra cada projeto ativo
     * do tenant e devolve as quedas >= threshold vs o snapshot anterior.
     *
     * @return list<array{project_id:int,name:string,from:int,to:int,drop:int}>
     */
    private function snapshotAndDetectDrops(ProjectHealthService $health, int $tenantId, int $threshold): array
    {
        $drops = [];

        $projects = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        foreach ($projects as $project) {
            try {
                $jaHoje = ProjectHealthHistory::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->whereDate('recorded_at', today())
                    ->exists();

                if (!$jaHoje) {
                    $health->recordSnapshot($project);
                }

                $snaps = ProjectHealthHistory::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->orderByDesc('recorded_at')
                    ->limit(2)
                    ->get(['overall_score', 'recorded_at']);

                if ($snaps->count() < 2 || !$snaps[0]->recorded_at->isToday()) {
                    continue;
                }

                $drop = (int) $snaps[1]->overall_score - (int) $snaps[0]->overall_score;
                if ($drop >= $threshold) {
                    $drops[] = [
                        'project_id' => (int) $project->id,
                        'name'       => (string) $project->name,
                        'from'       => (int) $snaps[1]->overall_score,
                        'to'         => (int) $snaps[0]->overall_score,
                        'drop'       => $drop,
                    ];
                }
            } catch (Throwable $e) {
                // Snapshot de um projeto nao pode derrubar a varredura inteira
                // (ex: falha no mail de alerta do ProjectHealthService).
                Log::warning('StrategyRoom/AutoTrigger: snapshot falhou', [
                    'tenant' => $tenantId, 'project' => $project->id, 'err' => $e->getMessage(),
                ]);
            }
        }

        return $drops;
    }

    /** @param array{project_id:int,name:string,from:int,to:int,drop:int} $worst */
    private function notifyTenantUsers(int $tenantId, int $sessionId, array $worst): void
    {
        $users = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'ngo', 'common'])
            ->get(['id']);

        foreach ($users as $user) {
            try {
                Notification::create([
                    'tenant_id' => $tenantId,
                    'user_id'   => $user->id,
                    'title'     => 'Diretoria convocada automaticamente',
                    'message'   => sprintf(
                        'O score do projeto “%s” caiu de %d pra %d pontos. A Sala de Estratégia se reuniu pra analisar a situação.',
                        $worst['name'], $worst['from'], $worst['to']
                    ),
                    'type'      => 'warning',
                    'link'      => route('strategy-room.show', $sessionId),
                ]);
            } catch (Throwable $e) {
                Log::warning('StrategyRoom/AutoTrigger: notificacao falhou', [
                    'tenant' => $tenantId, 'user' => $user->id, 'err' => $e->getMessage(),
                ]);
            }
        }
    }
}
