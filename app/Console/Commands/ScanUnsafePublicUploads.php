<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Procura na pasta pública (storage/app/public) arquivos que o navegador
 * executa como página: SVG, HTML, XML… Resto da falha de upload (auditoria
 * 26/11/2026): a regra `image` do Laravel 9 aceitava SVG na rifa, na landing
 * page e na linha do tempo dos projetos. Por padrão só LISTA; apagar exige --apagar.
 */
class ScanUnsafePublicUploads extends Command
{
    protected $signature = 'security:uploads-publicos {--apagar : Apaga os arquivos listados}';

    protected $description = 'Lista/apaga arquivos executáveis no navegador (svg, html…) na pasta pública de uploads';

    private const UNSAFE_EXTENSIONS = ['svg', 'svgz', 'html', 'htm', 'xhtml', 'xht', 'xml', 'xsl', 'js', 'mjs', 'php', 'phtml', 'shtml'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $unsafe = collect($disk->allFiles())
            ->filter(fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::UNSAFE_EXTENSIONS, true))
            ->reject(fn ($f) => str_starts_with(basename($f), '.'))
            ->values();

        if ($unsafe->isEmpty()) {
            $this->info('Nenhum arquivo suspeito encontrado. Tudo certo.');
            return self::SUCCESS;
        }

        $this->warn($unsafe->count() . ' arquivo(s) que o navegador pode executar:');
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
