<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique index if it still exists (idempotent — may have been dropped in a partial run)
        try {
            DB::statement('ALTER TABLE whatsapp_instances DROP INDEX whatsapp_instances_instance_token_unique');
        } catch (\Throwable) {}

        DB::statement('ALTER TABLE whatsapp_instances MODIFY instance_token TEXT NULL');

        if (!Schema::hasColumn('whatsapp_instances', 'instance_token_bidx')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->string('instance_token_bidx', 64)->nullable()->after('instance_token');
                $table->index('instance_token_bidx');
            });
        }

        DB::table('whatsapp_instances')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $token = $row->instance_token;
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
                    DB::table('whatsapp_instances')->where('id', $row->id)->update([
                        'instance_token_bidx' => hash_hmac('sha256', $plain, config('app.key')),
                    ]);
                } else {
                    DB::table('whatsapp_instances')->where('id', $row->id)->update([
                        'instance_token'      => Crypt::encryptString($token),
                        'instance_token_bidx' => hash_hmac('sha256', $token, config('app.key')),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('whatsapp_instances')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $token = $row->instance_token;
                if ($token === null || $token === '' || !str_starts_with($token, 'eyJ')) {
                    continue;
                }
                try {
                    $plain = Crypt::decryptString($token);
                    DB::table('whatsapp_instances')->where('id', $row->id)->update([
                        'instance_token' => $plain,
                    ]);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    // Skip
                }
            }
        });

        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropIndex(['instance_token_bidx']);
            $table->dropColumn('instance_token_bidx');
        });

        DB::statement('ALTER TABLE whatsapp_instances MODIFY instance_token VARCHAR(64) NOT NULL');
        DB::statement('ALTER TABLE whatsapp_instances ADD UNIQUE INDEX whatsapp_instances_instance_token_unique (instance_token)');
    }
};
