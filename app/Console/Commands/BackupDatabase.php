<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Backup diário: banco (mysqldump) e arquivos enviados (storage/app), guardados no
 * servidor por --keep dias e, se BACKUP_S3_BUCKET estiver no .env, copiados para o
 * S3 (fora do servidor), onde ficam por --remote-keep dias.
 */
class BackupDatabase extends Command
{
    protected $signature   = 'db:backup
                            {--keep=7 : Dias de retenção no servidor}
                            {--remote-keep=30 : Dias de retenção no S3}
                            {--no-files : Não incluir os arquivos enviados (storage/app)}';

    protected $description = 'Backup do banco e dos arquivos, no servidor e (se configurado) no S3';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');
        $dir  = storage_path('backups');
        $date = now()->format('Y-m-d_H-i-s');
        $made = [];

        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        // ── Banco ────────────────────────────────────────────────────────────
        $host   = config('database.connections.mysql.host', '127.0.0.1');
        $port   = config('database.connections.mysql.port', '3306');
        $dbname = config('database.connections.mysql.database');
        $user   = config('database.connections.mysql.username');
        $pass   = config('database.connections.mysql.password');

        if (empty($dbname) || empty($user)) {
            return $this->failBackup('Credenciais do banco não encontradas no .env');
        }

        $dbFile = "{$dir}/{$date}.sql.gz";
        // pipefail: se o mysqldump falhar, o gzip não "esconde" o erro (antes o
        // arquivo saía com ~20 bytes e o backup passava como bem-sucedido).
        $cmd = sprintf(
            'set -o pipefail; MYSQL_PWD=%s mysqldump --single-transaction --quick --routines --triggers -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg((string) $pass), escapeshellarg((string) $host), escapeshellarg((string) $port),
            escapeshellarg((string) $user), escapeshellarg((string) $dbname), escapeshellarg($dbFile)
        );
        $this->info("🗄️  Backup do banco [{$dbname}]...");
        exec('bash -c ' . escapeshellarg($cmd) . ' 2>&1', $out, $code);

        if ($code !== 0 || ! is_file($dbFile) || filesize($dbFile) < 1024) {
            @unlink($dbFile);
            return $this->failBackup('Falha no mysqldump', ['exit_code' => $code, 'output' => array_slice($out, -5)]);
        }
        $made[] = $dbFile;

        // ── Arquivos enviados (documentos, fotos, anexos) ────────────────────
        if (! $this->option('no-files') && is_dir(storage_path('app'))) {
            $filesFile = "{$dir}/{$date}_arquivos.tar.gz";
            exec(sprintf('tar -czf %s -C %s app 2>&1', escapeshellarg($filesFile), escapeshellarg(storage_path())), $out2, $code2);
            if ($code2 === 0 && is_file($filesFile)) {
                $made[] = $filesFile;
            } else {
                @unlink($filesFile);
                $this->warn('⚠️  Não foi possível compactar storage/app (seguindo só com o banco).');
                Log::error('db:backup — falha ao compactar storage/app', ['exit_code' => $code2, 'output' => array_slice($out2, -5)]);
            }
        }

        foreach ($made as $f) {
            $this->info('✅ ' . basename($f) . ' (' . round(filesize($f) / 1048576, 2) . ' MB)');
        }

        // ── Cópia fora do servidor ───────────────────────────────────────────
        $remote = 'não configurado (BACKUP_S3_BUCKET)';
        if (config('filesystems.disks.backups.bucket')) {
            try {
                $disk = Storage::disk('backups');
                foreach ($made as $f) {
                    $stream = fopen($f, 'rb');
                    $disk->writeStream(now()->format('Y/m') . '/' . basename($f), $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                $remoteCleaned = $this->pruneRemote($disk, (int) $this->option('remote-keep'));
                $remote = 'enviado' . ($remoteCleaned ? " ({$remoteCleaned} antigo(s) removido(s))" : '');
            } catch (\Throwable $e) {
                // O backup local existe; o aviso vai para o Sentry.
                $this->error('❌ Falha ao enviar para o S3: ' . $e->getMessage());
                report(new \RuntimeException('db:backup — falha ao enviar para o S3: ' . $e->getMessage(), 0, $e));
                $remote = 'FALHOU';
            }
        }

        // ── Limpeza local ────────────────────────────────────────────────────
        $cleaned = 0;
        $cutoff  = now()->subDays($keep)->timestamp;
        foreach (array_merge(glob("{$dir}/*.sql.gz") ?: [], glob("{$dir}/*.tar.gz") ?: []) as $old) {
            if (filemtime($old) < $cutoff) {
                unlink($old);
                $cleaned++;
            }
        }

        Log::info('db:backup concluído', ['files' => array_map('basename', $made), 's3' => $remote]);
        $this->table(['Campo', 'Valor'], [
            ['Banco', $dbname],
            ['Arquivos', implode(', ', array_map('basename', $made))],
            ['Cópia no S3', $remote],
            ['Retenção local', "{$keep} dias"],
            ['Removidos (local)', $cleaned],
        ]);

        return $remote === 'FALHOU' ? self::FAILURE : self::SUCCESS;
    }

    private function pruneRemote($disk, int $days): int
    {
        if ($days <= 0) {
            return 0;
        }
        $cutoff = now()->subDays($days)->timestamp;
        $removed = 0;
        foreach ($disk->allFiles() as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $removed++;
            }
        }
        return $removed;
    }

    private function failBackup(string $msg, array $ctx = []): int
    {
        $this->error('❌ ' . $msg);
        Log::error('db:backup — ' . $msg, $ctx);
        report(new \RuntimeException('db:backup — ' . $msg));
        return self::FAILURE;
    }
}
