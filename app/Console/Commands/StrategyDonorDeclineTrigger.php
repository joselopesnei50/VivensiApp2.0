<?php

namespace App\Console\Commands;

use App\Jobs\RunStrategyDebateJob;
use App\Models\Notification;
use App\Models\StrategySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sala de Estrategia — trigger automatico por doador recorrente em declinio.
 *
 * Agendado diario (07:45, apos o trigger de health). Pra cada tenant com
 * doacao registrada nos ultimos 6 meses:
 *   - Doador recorrente = doou em >= N meses distintos nos ultimos 6 meses.
 *   - Em declinio = ultima doacao ha mais de X dias.
 *   - >= 1 doador nessa condicao convoca reuniao (auto_donor_decline).
 *
 * LGPD: so contagens agregadas em log/notificacao — nunca nome de doador.
 * Protecoes de custo: cooldown por tenant (so este trigger), cota diaria do
 * tenant e cap global diario COMPARTILHADO com o trigger de health. Flag
 * propria (auto_trigger_donor) OFF por default.
 */
class StrategyDonorDeclineTrigger extends Command
{
    public const TRIGGER_TYPE = 'auto_donor_decline';

    protected $signature   = 'strategy:donor-decline-trigger {--dry-run : Avalia e loga sem convocar reuniao nem notificar}';
    protected $description = 'Detecta doadores recorrentes em declinio e convoca reuniao estrategica.';

    public function handle(): int
    {
        if (!config('strategy_room.enabled') || !config('strategy_room.auto_trigger_donor')) {
            $this->line('Trigger de doadores desativado (strategy_room.enabled + strategy_room.auto_trigger_donor precisam estar true).');
            return self::SUCCESS;
        }

        $dryRun      = (bool) $this->option('dry-run');
        $minMonths   = max(2, (int) config('strategy_room.donor_recurring_min_months', 3));
        $declineDays = max(15, (int) config('strategy_room.donor_decline_days', 45));
        $cooldown    = max(1, (int) config('strategy_room.auto_trigger_cooldown_days', 7));
        $globalCap   = (int) config('strategy_room.auto_trigger_global_daily_cap', 20);
        $quota       = (int) config('strategy_room.daily_quota', 10);

        // Cap global compartilhado entre os triggers automaticos: soma as
        // reunioes de health + doadores de hoje pra limitar o custo total.
        $dispatchedToday = StrategySession::withoutGlobalScopes()
            ->whereIn('trigger_type', [StrategyAutoTrigger::TRIGGER_TYPE, self::TRIGGER_TYPE])
            ->whereDate('created_at', today())
            ->count();

        $tenantIds = Transaction::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('type', 'income')
            ->whereNotNull('ngo_donor_id')
            ->where('date', '>=', now()->subMonths(6)->startOfMonth()->toDateString())
            ->distinct()
            ->pluck('tenant_id')
            ->filter()
            ->values();

        $this->info(sprintf(
            'Varredura: %d tenant(s) com doacao em 6 meses · recorrente=%d+ meses · declinio=%dd · cooldown=%dd · cap global=%d (%d ja usadas hoje)%s',
            $tenantIds->count(), $minMonths, $declineDays, $cooldown, $globalCap, $dispatchedToday, $dryRun ? ' · DRY-RUN' : ''
        ));

        $convocadas = 0;

        foreach ($tenantIds as $tenantId) {
            $tenantId = (int) $tenantId;

            try {
                $emDeclinio = $this->countDecliningRecurringDonors($tenantId, $minMonths, $declineDays);
            } catch (Throwable $e) {
                Log::warning('StrategyRoom/DonorTrigger: deteccao falhou', [
                    'tenant' => $tenantId, 'err' => $e->getMessage(),
                ]);
                continue;
            }

            if ($emDeclinio < 1) {
                continue;
            }

            $this->line("Tenant #{$tenantId}: {$emDeclinio} doador(es) recorrente(s) em declinio.");

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
                $this->line("  → pulado: reuniao de doadores nos ultimos {$cooldown} dias (cooldown).");
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

            $this->notifyTenantUsers($tenantId, $session->id, $emDeclinio, $declineDays);
            $this->info("  → reuniao #{$session->id} convocada.");

            Log::info('StrategyRoom/DonorTrigger: reuniao convocada', [
                'tenant'      => $tenantId,
                'session'     => $session->id,
                'em_declinio' => $emDeclinio,
            ]);
        }

        $this->info("Concluido: {$convocadas} reuniao(oes) convocada(s).");
        return self::SUCCESS;
    }

    /**
     * Conta doadores recorrentes (>= $minMonths meses distintos com doacao
     * nos ultimos 6 meses) cuja ultima doacao foi ha mais de $declineDays.
     *
     * Agrega em PHP pra ficar portavel sqlite/mysql (extracao de mes em SQL
     * difere entre os drivers).
     */
    private function countDecliningRecurringDonors(int $tenantId, int $minMonths, int $declineDays): int
    {
        $doacoes = Transaction::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->whereNotNull('ngo_donor_id')
            ->where('date', '>=', now()->subMonths(6)->startOfMonth()->toDateString())
            ->get(['ngo_donor_id', 'date']);

        $limiteDeclinio = now()->subDays($declineDays);

        return $doacoes
            ->groupBy('ngo_donor_id')
            ->filter(function ($txs) use ($minMonths, $limiteDeclinio) {
                $mesesDistintos = $txs->map(fn ($t) => $t->date->format('Y-m'))->unique()->count();
                $ultimaDoacao   = $txs->max('date');

                return $mesesDistintos >= $minMonths && $ultimaDoacao->lt($limiteDeclinio);
            })
            ->count();
    }

    private function notifyTenantUsers(int $tenantId, int $sessionId, int $emDeclinio, int $declineDays): void
    {
        $users = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'ngo', 'common'])
            ->get(['id']);

        $mensagem = $emDeclinio === 1
            ? "1 doador recorrente está há mais de {$declineDays} dias sem doar. A Sala de Estratégia se reuniu pra analisar a retenção."
            : "{$emDeclinio} doadores recorrentes estão há mais de {$declineDays} dias sem doar. A Sala de Estratégia se reuniu pra analisar a retenção.";

        foreach ($users as $user) {
            try {
                Notification::create([
                    'tenant_id' => $tenantId,
                    'user_id'   => $user->id,
                    'title'     => 'Diretoria convocada: doadores em declínio',
                    'message'   => $mensagem,
                    'type'      => 'warning',
                    'link'      => route('strategy-room.show', $sessionId),
                ]);
            } catch (Throwable $e) {
                Log::warning('StrategyRoom/DonorTrigger: notificacao falhou', [
                    'tenant' => $tenantId, 'user' => $user->id, 'err' => $e->getMessage(),
                ]);
            }
        }
    }
}
