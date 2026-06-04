<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migracao LGPD/Hardening: public_receipt_token sai de plaintext para
 * encrypted-at-rest com blind index pesquisavel.
 *
 * Antes: transactions.public_receipt_token VARCHAR(64) UNIQUE com UUID
 * plaintext. Um dump do banco expunha todas as URLs ativas de recibos.
 *
 * Depois (espelha o padrao ja aplicado em ngo_donors.portal_token):
 * - public_receipt_token: TEXT NULL, contem Crypt::encryptString do UUID
 * - public_receipt_token_bidx: VARCHAR(64) indexado, contem
 *   hash_hmac('sha256', $token, app.key) para lookup O(1) sem decifrar
 *
 * Backfill: encrypta tokens plaintext existentes e computa bidx. Para
 * linhas que ja estao encriptadas (idempotencia em re-run), so
 * recomputa o bidx a partir do plaintext decifrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Drop dos indices unicos sobre o campo plaintext antes de virar TEXT
            foreach (['transactions_public_receipt_token_unique', 'public_receipt_token'] as $idx) {
                try {
                    DB::statement("ALTER TABLE transactions DROP INDEX {$idx}");
                } catch (\Throwable) {}
            }

            DB::statement('ALTER TABLE transactions MODIFY public_receipt_token TEXT NULL');
        }

        if (!Schema::hasColumn('transactions', 'public_receipt_token_bidx')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('public_receipt_token_bidx', 64)->nullable()->after('public_receipt_token');
                $table->index('public_receipt_token_bidx');
            });
        }

        DB::table('transactions')
            ->whereNotNull('public_receipt_token')
            ->where('public_receipt_token', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $token = $row->public_receipt_token;
                    if ($token === null || $token === '') {
                        continue;
                    }

                    $isEncrypted = str_starts_with($token, 'eyJ');

                    if ($isEncrypted) {
                        try {
                            $plain = Crypt::decryptString($token);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            continue;
                        }
                        DB::table('transactions')->where('id', $row->id)->update([
                            'public_receipt_token_bidx' => hash_hmac('sha256', $plain, config('app.key')),
                        ]);
                    } else {
                        DB::table('transactions')->where('id', $row->id)->update([
                            'public_receipt_token'      => Crypt::encryptString($token),
                            'public_receipt_token_bidx' => hash_hmac('sha256', $token, config('app.key')),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Decifra os tokens de volta para plaintext (para o caso de rollback)
        DB::table('transactions')
            ->whereNotNull('public_receipt_token')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $token = $row->public_receipt_token;
                    if ($token === null || $token === '' || !str_starts_with($token, 'eyJ')) {
                        continue;
                    }
                    try {
                        $plain = Crypt::decryptString($token);
                        DB::table('transactions')->where('id', $row->id)->update([
                            'public_receipt_token' => $plain,
                        ]);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                        // Skip — sem app.key original nao da pra recuperar
                    }
                }
            });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transactions MODIFY public_receipt_token VARCHAR(64) NULL');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['public_receipt_token_bidx']);
            $table->dropColumn('public_receipt_token_bidx');
        });
    }
};
