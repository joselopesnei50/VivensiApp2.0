<?php

namespace App\Console\Commands;

use App\Models\WhatsappInstance;
use Illuminate\Console\Command;

/**
 * Tarefa 1.5 da auditoria. Recomputa o blind index (instance_token_bidx) de
 * todas as instâncias WhatsApp usando a chave atualmente resolvida por
 * whatsapp_bidx_key() — geralmente WHATSAPP_BIDX_KEY após a migração.
 *
 * Idempotente: rodar duas vezes com a mesma config resulta no mesmo bidx.
 *
 * Uso:
 *   php artisan whatsapp:rebuild-bidx              # dry-run (não grava)
 *   php artisan whatsapp:rebuild-bidx --apply      # grava no banco
 *
 * Quando rodar:
 *   - Depois de setar WHATSAPP_BIDX_KEY em .env pela primeira vez
 *   - Depois de rotacionar WHATSAPP_BIDX_KEY (cenário raro mas suportado)
 *   - Em produção: rodar IMEDIATAMENTE após mudar a env, senão webhooks
 *     de Evolution param de resolver instâncias até o rebuild rodar.
 */
class RebuildWhatsappInstanceBidx extends Command
{
    protected $signature = 'whatsapp:rebuild-bidx
                            {--apply : Grava os bidx atualizados (sem essa flag o command é dry-run)}';

    protected $description = 'Recomputa instance_token_bidx das instâncias WhatsApp usando whatsapp_bidx_key() atual';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? '== Modo APLICAR — bidx serão gravados ==' : '== Modo DRY-RUN — nada gravado ==');
        $this->info('Chave atual (prefixo): ' . substr(whatsapp_bidx_key(), 0, 12) . '...');

        // withoutGlobalScopes: bypass intencional, comando administrativo
        // que precisa varrer todas as instâncias independente de tenant.
        $instances = WhatsappInstance::withoutGlobalScopes()->get();

        $total      = $instances->count();
        $rebuilt    = 0;
        $unchanged  = 0;
        $skipped    = 0;
        $errors     = 0;

        if ($total === 0) {
            $this->warn('Nenhuma instância encontrada.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($instances as $instance) {
            try {
                // Accessor decifra o token (linha 90 do model)
                $token = $instance->instance_token;

                if (empty($token)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $oldBidx = $instance->getAttributes()['instance_token_bidx'] ?? null;
                $newBidx = hash_hmac('sha256', $token, whatsapp_bidx_key());

                if ($oldBidx === $newBidx) {
                    $unchanged++;
                    $bar->advance();
                    continue;
                }

                if ($apply) {
                    // updateQuietly para evitar disparar observers/events durante migração
                    $instance->forceFill(['instance_token_bidx' => $newBidx])->saveQuietly();
                }

                $rebuilt++;
                $bar->advance();
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("Erro na instância {$instance->id}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Total', 'Inalterados', 'Sem token', 'Recomputados', 'Erros'],
            [[$total, $unchanged, $skipped, $rebuilt, $errors]]
        );

        if (!$apply && $rebuilt > 0) {
            $this->warn("Re-execute com --apply para gravar {$rebuilt} bidx.");
        }

        if ($apply && $rebuilt > 0) {
            $this->info("Concluído. {$rebuilt} instância(s) com bidx atualizado.");
        }

        if ($errors > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
