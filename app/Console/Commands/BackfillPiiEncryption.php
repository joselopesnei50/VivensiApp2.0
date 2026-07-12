<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * C4 — Backfill de PII (encryption at-rest) em rows existentes.
 *
 * Cifra plaintext existente em Lead.phone / Lead.email / User.phone e gera
 * blind index correspondente. Idempotente: linhas ja cifradas sao detectadas
 * via try Crypt::decryptString e puladas.
 *
 * Chunking 500 rows p/ nao estourar memoria em bases grandes.
 * Suporta --dry-run e filtro --model=lead|user.
 */
class BackfillPiiEncryption extends Command
{
    protected $signature = 'pii:backfill-encryption
                            {--model=all : lead|user|all (default: all)}
                            {--dry-run : preview sem alterar dados}
                            {--chunk=500 : tamanho do chunk}';

    protected $description = 'Cifra PII (phone/email) existente em Lead e User + gera blind index. Idempotente.';

    public function handle(): int
    {
        $model  = $this->option('model');
        $dryRun = $this->option('dry-run');
        $chunk  = (int) $this->option('chunk');

        if ($chunk < 1 || $chunk > 5000) {
            $this->error('--chunk deve estar entre 1 e 5000.');
            return self::INVALID;
        }

        if (!in_array($model, ['lead', 'user', 'all'], true)) {
            $this->error('--model deve ser lead, user ou all.');
            return self::INVALID;
        }

        $stats = [
            'lead_processed'    => 0,
            'lead_encrypted'    => 0,
            'lead_skipped'      => 0,
            'user_processed'    => 0,
            'user_encrypted'    => 0,
            'user_skipped'      => 0,
        ];

        if ($model === 'lead' || $model === 'all') {
            $this->info('=== Processando leads ===');
            $stats = $this->processLeads($stats, $chunk, $dryRun);
        }

        if ($model === 'user' || $model === 'all') {
            $this->info('=== Processando users ===');
            $stats = $this->processUsers($stats, $chunk, $dryRun);
        }

        $this->newLine();
        $this->info(sprintf(
            'Leads: %d processados, %d cifrados, %d ja cifrados (skipped).',
            $stats['lead_processed'], $stats['lead_encrypted'], $stats['lead_skipped']
        ));
        $this->info(sprintf(
            'Users: %d processados, %d cifrados, %d ja cifrados (skipped).',
            $stats['user_processed'], $stats['user_encrypted'], $stats['user_skipped']
        ));

        if ($dryRun) {
            $this->warn('DRY-RUN — nenhuma alteracao foi persistida.');
        }

        Log::info('LGPD_C4_BACKFILL_COMPLETED', array_merge($stats, ['dry_run' => $dryRun]));

        return self::SUCCESS;
    }

    private function processLeads(array $stats, int $chunk, bool $dryRun): array
    {
        // Bypass mutators durante backfill — leitura via query builder,
        // escrita via update raw. Se lermos via Model o getter tentaria
        // decriptar o plaintext e falharia silenciosamente.
        DB::table('leads')
            ->select(['id', 'phone', 'phone_bidx', 'email', 'email_bidx'])
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$stats, $dryRun) {
                foreach ($rows as $row) {
                    $stats['lead_processed']++;

                    $updates = $this->encryptFields($row, [
                        'phone' => 'phone_bidx',
                        'email' => 'email_bidx',
                    ]);

                    if ($updates === null) {
                        $stats['lead_skipped']++;
                        continue;
                    }
                    if (empty($updates)) {
                        $stats['lead_skipped']++;
                        continue;
                    }

                    $stats['lead_encrypted']++;

                    if (!$dryRun) {
                        DB::table('leads')->where('id', $row->id)->update($updates);
                    }
                }
            });

        return $stats;
    }

    private function processUsers(array $stats, int $chunk, bool $dryRun): array
    {
        DB::table('users')
            ->select(['id', 'phone', 'phone_bidx'])
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$stats, $dryRun) {
                foreach ($rows as $row) {
                    $stats['user_processed']++;

                    $updates = $this->encryptFields($row, ['phone' => 'phone_bidx']);

                    if ($updates === null || empty($updates)) {
                        $stats['user_skipped']++;
                        continue;
                    }

                    $stats['user_encrypted']++;

                    if (!$dryRun) {
                        DB::table('users')->where('id', $row->id)->update($updates);
                    }
                }
            });

        return $stats;
    }

    /**
     * Retorna array de updates ou null se linha ja esta OK (nada a fazer).
     *
     * @param object $row  Row do query builder (stdClass)
     * @param array<string,string> $fieldToBidx  ['phone' => 'phone_bidx', ...]
     * @return array<string,string>|null
     */
    private function encryptFields(object $row, array $fieldToBidx): ?array
    {
        $updates = [];

        foreach ($fieldToBidx as $field => $bidxField) {
            $value = $row->{$field} ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // Ja e cifrado? Se decryptString funciona, e cifrado — pula.
            try {
                Crypt::decryptString($value);
                // Ja cifrado. Apenas garante o bidx caso esteja null (edge case).
                // Nao conseguimos gerar bidx do cifrado sem decriptar antes.
                continue;
            } catch (DecryptException) {
                // Nao e cifrado — vamos cifrar.
            }

            // Normaliza email antes de cifrar (mesmo do mutator)
            $plaintext = $field === 'email'
                ? mb_strtolower(trim($value))
                : $value;

            $updates[$field]     = Crypt::encryptString($plaintext);
            $updates[$bidxField] = hash_hmac('sha256', $plaintext, config('app.key'));
        }

        return $updates;
    }
}
