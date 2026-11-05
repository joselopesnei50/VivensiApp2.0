<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Lista (e opcionalmente apaga) arquivos que não são imagem na pasta pública
 * de uploads das campanhas de e-mail — resto da falha que gravava a extensão
 * enviada pelo usuário (.html, .svg…). Por padrão só LISTA; apagar exige --apagar.
 */
class CleanUnsafeCampaignUploads extends Command
{
    protected $signature = 'security:uploads-campanha {--apagar : Apaga os arquivos listados}';

    protected $description = 'Lista/apaga arquivos que não são imagem em storage/email_campaigns_uploads';

    private const SAFE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $unsafe = collect($disk->allFiles('email_campaigns_uploads'))
            ->reject(fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::SAFE_EXTENSIONS, true))
            ->values();

        if ($unsafe->isEmpty()) {
            $this->info('Nenhum arquivo suspeito encontrado. Tudo certo.');
            return self::SUCCESS;
        }

        $this->warn($unsafe->count() . ' arquivo(s) que não são imagem:');
        $this->table(['Arquivo', 'Tamanho', 'Data'], $unsafe->map(fn ($f) => [
            $f,
            number_format($disk->size($f) / 1024, 1, ',', '.') . ' KB',
            date('d/m/Y H:i', $disk->lastModified($f)),
        ])->all());

        if (! $this->option('apagar')) {
            $this->line('Nada foi apagado. Para apagar, rode de novo com --apagar');
            return self::SUCCESS;
        }

        $disk->delete($unsafe->all());
        $this->info($unsafe->count() . ' arquivo(s) apagado(s).');
        return self::SUCCESS;
    }
}
