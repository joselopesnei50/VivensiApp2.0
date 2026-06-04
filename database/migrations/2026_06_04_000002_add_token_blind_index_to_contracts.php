<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Igual a 2026_06_04_000001 mas para contracts.token. Risco era maior
 * neste caso: tokens de 64 chars dao acesso ao endpoint POST /sign/{token}
 * que efetiva assinatura digital. Dump do banco com token plaintext
 * permitiria a um atacante assinar contratos antes do signatario legitimo
 * (forgery).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            foreach (['contracts_token_unique', 'token'] as $idx) {
                try {
                    DB::statement("ALTER TABLE contracts DROP INDEX {$idx}");
                } catch (\Throwable) {}
            }

            DB::statement('ALTER TABLE contracts MODIFY token TEXT NULL');
        }

        if (!Schema::hasColumn('contracts', 'token_bidx')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('token_bidx', 64)->nullable()->after('token');
                $table->index('token_bidx');
            });
        }

        DB::table('contracts')
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $token = $row->token;
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
                        DB::table('contracts')->where('id', $row->id)->update([
                            'token_bidx' => hash_hmac('sha256', $plain, config('app.key')),
                        ]);
                    } else {
                        DB::table('contracts')->where('id', $row->id)->update([
                            'token'      => Crypt::encryptString($token),
                            'token_bidx' => hash_hmac('sha256', $token, config('app.key')),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('contracts')
            ->whereNotNull('token')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $token = $row->token;
                    if ($token === null || $token === '' || !str_starts_with($token, 'eyJ')) {
                        continue;
                    }
                    try {
                        $plain = Crypt::decryptString($token);
                        DB::table('contracts')->where('id', $row->id)->update([
                            'token' => $plain,
                        ]);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                        // Skip
                    }
                }
            });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE contracts MODIFY token VARCHAR(64) NULL');
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['token_bidx']);
            $table->dropColumn('token_bidx');
        });
    }
};
