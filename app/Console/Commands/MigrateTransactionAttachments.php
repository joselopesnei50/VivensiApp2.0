<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Migracao LGPD: move anexos financeiros do disco 'public' (URL-acessiveis)
 * para 'private/tenants/{id}/attachments/' no disco 'local' (fora do
 * storage:link). Idempotente: paths ja em 'private/' sao ignorados.
 *
 * Uso:
 *   php artisan transactions:migrate-attachments --dry-run
 *   php artisan transactions:migrate-attachments
 */
class MigrateTransactionAttachments extends Command
{
    protected $signature = 'transactions:migrate-attachments {--dry-run : Mostra o que seria movido sem alterar nada}';

    protected $description = 'Move anexos financeiros do disco publico para privado (LGPD). Atualiza paths em attachment_path/receipt_path.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $local  = Storage::disk('local');

        $moved = $missing = $skipped = 0;

        $query = Transaction::query()
            ->withoutGlobalScopes() // console — precisamos ver de todos os tenants
            ->where(function ($q) {
                $q->whereNotNull('attachment_path')
                  ->orWhereNotNull('receipt_path');
            });

        $total = $query->count();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "Avaliando {$total} transacoes com anexo...");

        $query->chunkById(200, function ($transactions) use ($public, $local, $dry, &$moved, &$missing, &$skipped) {
            foreach ($transactions as $t) {
                // Dedupe: attachment_path e receipt_path frequentemente apontam
                // pro mesmo arquivo. Processa cada path unico uma vez.
                $paths = collect([$t->attachment_path, $t->receipt_path])
                    ->filter()
                    ->unique()
                    ->values();

                foreach ($paths as $oldPath) {
                    if (str_starts_with($oldPath, 'private/')) {
                        $skipped++;
                        continue;
                    }

                    $newPath = "private/tenants/{$t->tenant_id}/attachments/" . basename($oldPath);

                    $existsPublic = $public->exists($oldPath);
                    $existsLocal  = $local->exists($newPath);

                    if (!$existsPublic && !$existsLocal) {
                        $this->warn("MISSING tx={$t->id} {$oldPath} (arquivo nao encontrado em nenhum disco)");
                        $missing++;
                        continue;
                    }

                    if (!$dry) {
                        if ($existsPublic && !$existsLocal) {
                            $stream = $public->readStream($oldPath);
                            if ($stream === null) {
                                $this->warn("READ FAIL tx={$t->id} {$oldPath}");
                                $missing++;
                                continue;
                            }
                            $local->writeStream($newPath, $stream);
                            if (is_resource($stream)) {
                                fclose($stream);
                            }
                            $public->delete($oldPath);
                        }

                        if ($t->attachment_path === $oldPath) {
                            $t->attachment_path = $newPath;
                        }
                        if ($t->receipt_path === $oldPath) {
                            $t->receipt_path = $newPath;
                        }
                        $t->save();
                    }

                    $this->line(($dry ? '[DRY] ' : '') . "tx={$t->id}: {$oldPath} -> {$newPath}");
                    $moved++;
                }
            }
        });

        $this->newLine();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "Concluido. moved={$moved} missing={$missing} skipped={$skipped}");

        if ($missing > 0) {
            $this->warn("Atencao: {$missing} arquivos faltando no disco — paths em DB ficaram pendurados. Investigar.");
        }

        return self::SUCCESS;
    }
}
