<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Remove imagens de campanhas de email que ninguem mais referencia no
 * html_content de nenhuma campanha. Safety: so remove arquivos com mais
 * de N dias (default 7) — protege contra deletar arquivo recem-uploaded
 * enquanto o operador ainda esta editando o rascunho.
 *
 * Uso:
 *   php artisan email:cleanup-unused-images                # dry-run (mostra sem apagar)
 *   php artisan email:cleanup-unused-images --confirm      # apaga de fato
 *   php artisan email:cleanup-unused-images --min-days=14  # muda safety window
 *
 * Agendavel via Kernel — semanal recomendado (baixo custo, log claro).
 */
class EmailCleanupUnusedImages extends Command
{
    protected $signature = 'email:cleanup-unused-images
                            {--confirm : Apaga de verdade (default eh dry-run)}
                            {--min-days=7 : So apaga arquivos com mais que N dias}';

    protected $description = 'Remove imagens uploaded pra campanhas que nao aparecem em nenhum html_content.';

    public function handle(): int
    {
        $confirm = (bool) $this->option('confirm');
        $minDays = (int) $this->option('min-days');
        $folder  = 'email_campaigns_uploads';

        $files = Storage::disk('public')->files($folder);
        if (empty($files)) {
            $this->info('Nada em ' . $folder . ' — nada pra limpar.');
            return self::SUCCESS;
        }

        // Extrai todos os html_content pra checar referencias.
        // Feito em memoria porque emailcampaigns em geral eh baixa cardinalidade.
        $allHtml = EmailCampaign::whereNotNull('html_content')
            ->pluck('html_content')
            ->implode("\n");

        $cutoffTs = now()->subDays($minDays)->getTimestamp();

        $toDelete = [];
        $skipRecent = 0;
        $skipUsed   = 0;

        foreach ($files as $path) {
            $filename = basename($path);

            // Referenciado em algum html_content? Contains eh suficiente e barato.
            if (str_contains($allHtml, $filename)) {
                $skipUsed++;
                continue;
            }

            $mtime = Storage::disk('public')->lastModified($path);
            if ($mtime > $cutoffTs) {
                $skipRecent++;
                continue;
            }

            $toDelete[] = $path;
        }

        $this->line('Total no folder     : ' . count($files));
        $this->line('Referenciados       : ' . $skipUsed);
        $this->line('Recentes (< ' . $minDays . 'd) : ' . $skipRecent);
        $this->line('A remover           : ' . count($toDelete));

        if (empty($toDelete)) {
            $this->info('Nada pra remover.');
            return self::SUCCESS;
        }

        if (!$confirm) {
            $this->warn('DRY-RUN — arquivos NAO removidos. Passe --confirm pra apagar.');
            foreach (array_slice($toDelete, 0, 10) as $p) {
                $this->line('  ' . $p);
            }
            if (count($toDelete) > 10) $this->line('  ... e mais ' . (count($toDelete) - 10));
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($toDelete as $path) {
            if (Storage::disk('public')->delete($path)) {
                $deleted++;
            }
        }

        $this->info("Removidos: {$deleted}");
        return self::SUCCESS;
    }
}
