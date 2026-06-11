<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Reescreve o campo `content` de todas as páginas institucionais aplicando
 * sanitize_user_html(). Necessário uma vez, após o deploy da Tarefa 1.1 da
 * auditoria (PROMPT_CORRECAO_VIVENSI.md), para neutralizar payloads XSS
 * que possam ter sido gravados antes da sanitização na escrita.
 *
 * Uso:
 *   php artisan pages:sanitize-legacy           # mostra o que mudaria (dry run)
 *   php artisan pages:sanitize-legacy --apply   # aplica as mudanças no banco
 */
class SanitizeLegacyPagesCommand extends Command
{
    protected $signature = 'pages:sanitize-legacy
                            {--apply : Aplica as mudanças no banco (sem essa flag o command é dry run)}';

    protected $description = 'Sanitiza o campo content de todas as páginas institucionais (Tarefa 1.1 da auditoria)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? '== Modo APLICAR — mudanças vão ao banco ==' : '== Modo DRY-RUN — nada é gravado ==');

        $total    = Page::count();
        $changed  = 0;
        $unchanged = 0;

        if ($total === 0) {
            $this->warn('Nenhuma página encontrada.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Page::query()->chunkById(50, function ($pages) use (&$changed, &$unchanged, $apply, $bar) {
            foreach ($pages as $page) {
                $original = (string) ($page->content ?? '');
                $clean    = sanitize_user_html($original);

                if ($clean === $original) {
                    $unchanged++;
                    $bar->advance();
                    continue;
                }

                if ($apply) {
                    $page->update(['content' => $clean]);
                }

                $changed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Total', 'Sem alteração', 'Sanitizadas'],
            [[$total, $unchanged, $changed]]
        );

        if (!$apply && $changed > 0) {
            $this->warn("Re-execute com --apply para gravar as {$changed} mudança(s).");
        }

        if ($apply && $changed > 0) {
            $this->info("Concluído. {$changed} página(s) atualizada(s).");
        }

        return self::SUCCESS;
    }
}
