<?php

namespace App\Console\Commands;

use App\Models\LgpdDataRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Executa deletions LGPD agendadas cujo grace period ja expirou.
 *
 * Anonimiza User em vez de deletar fisico para preservar integridade referencial
 * (mensagens WhatsApp, transacoes, auditoria continuam apontando pro user_id).
 *
 * Agendado no Kernel::schedule() para rodar 1x/dia (03:00 UTC).
 */
class PurgeScheduledLgpdDeletions extends Command
{
    protected $signature = 'lgpd:purge-scheduled-deletions
                            {--dry-run : Somente lista os que seriam processados}';

    protected $description = 'Executa exclusoes LGPD agendadas cujo prazo de carencia expirou (art. 15)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $requests = LgpdDataRequest::withoutGlobalScope('tenant')
            ->dueForPurge()
            ->get();

        if ($requests->isEmpty()) {
            $this->info('Nenhuma exclusao agendada vencida.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$requests->count()} exclusoes vencidas.");

        foreach ($requests as $req) {
            $user = User::find($req->user_id);

            if (!$user) {
                $this->warn("Request #{$req->id} — user_id {$req->user_id} nao existe. Marcando completed.");
                if (!$dryRun) {
                    $req->update([
                        'status'       => LgpdDataRequest::STATUS_COMPLETED,
                        'processed_at' => now(),
                        'notes'        => 'User ja nao existia no momento do purge.',
                    ]);
                }
                continue;
            }

            $this->line("→ Purgando user #{$user->id} ({$user->email}) — request #{$req->id}");

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($user, $req) {
                Log::critical('LGPD_USER_PURGED_AUTOMATED', [
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'tenant_id'  => $user->tenant_id,
                    'request_id' => $req->id,
                    'triggered'  => 'scheduler',
                ]);

                // Anonimiza dados pessoais mantendo integridade referencial
                $user->update([
                    'name'   => 'Usuario Excluido',
                    'email'  => 'deleted_' . $user->id . '@excluido.vivensi',
                    'phone'  => null,
                    'status' => 'inactive',
                    'two_factor_secret'         => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at'   => null,
                ]);

                // Revoga tokens de API
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                $req->update([
                    'status'       => LgpdDataRequest::STATUS_COMPLETED,
                    'processed_at' => now(),
                    'notes'        => 'Purge automatico apos grace period.',
                ]);
            });
        }

        $this->info($dryRun ? 'Dry-run concluido — nenhuma alteracao feita.' : 'Purge concluido.');
        return self::SUCCESS;
    }
}
