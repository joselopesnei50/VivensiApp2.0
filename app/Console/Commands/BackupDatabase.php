<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup
                            {--keep=7 : Número de dias de retenção dos backups}';

    protected $description = 'Gera backup comprimido do banco de dados MySQL e remove backups antigos';

    public function handle(): int
    {
        $keep    = (int) $this->option('keep');
        $dir     = storage_path('backups');
        $date    = now()->format('Y-m-d_H-i');
        $file    = "{$dir}/{$date}.sql.gz";

        // --- Credenciais do .env ---
        $host    = config('database.connections.mysql.host', '127.0.0.1');
        $port    = config('database.connections.mysql.port', '3306');
        $dbname  = config('database.connections.mysql.database');
        $user    = config('database.connections.mysql.username');
        $pass    = config('database.connections.mysql.password');

        if (empty($dbname) || empty($user)) {
            $this->error('❌ Credenciais do banco não encontradas no .env');
            Log::error('db:backup — credenciais ausentes no .env');
            return 1;
        }

        // --- Criar diretório se não existir ---
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        // --- Montar comando mysqldump ---
        // Usa MYSQL_PWD para evitar a senha exposta no processo
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --single-transaction --routines --triggers '
            . '-h %s -P %s -u %s %s | gzip > %s 2>&1',
            escapeshellarg($pass),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($dbname),
            escapeshellarg($file)
        );

        $this->info("🗄️  Iniciando backup de [{$dbname}]...");
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($file) || filesize($file) === 0) {
            $this->error('❌ Falha ao gerar backup!');
            Log::error('db:backup falhou', ['exit_code' => $exitCode, 'output' => $output]);
            return 1;
        }

        $sizeMb = round(filesize($file) / 1024 / 1024, 2);
        $this->info("✅ Backup salvo: {$file} ({$sizeMb} MB)");
        Log::info("db:backup concluído", ['file' => $file, 'size_mb' => $sizeMb]);

        // --- Limpeza de backups antigos ---
        $cleaned = 0;
        $cutoff  = now()->subDays($keep)->timestamp;

        foreach (glob("{$dir}/*.sql.gz") as $oldFile) {
            if (filemtime($oldFile) < $cutoff) {
                unlink($oldFile);
                $cleaned++;
                Log::info("db:backup — arquivo antigo removido: " . basename($oldFile));
            }
        }

        if ($cleaned > 0) {
            $this->info("🧹 {$cleaned} backup(s) antigo(s) removido(s) (retenção: {$keep} dias)");
        }

        $this->table(
            ['Campo', 'Valor'],
            [
                ['Banco',      $dbname],
                ['Arquivo',    basename($file)],
                ['Tamanho',    "{$sizeMb} MB"],
                ['Retenção',   "{$keep} dias"],
                ['Removidos',  $cleaned],
            ]
        );

        return 0;
    }
}
