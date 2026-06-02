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
        try {
            DB::statement('ALTER TABLE ngo_donors DROP INDEX ngo_donors_portal_token_unique');
        } catch (\Throwable) {}

        DB::statement('ALTER TABLE ngo_donors MODIFY portal_token TEXT NULL');

        if (!Schema::hasColumn('ngo_donors', 'portal_token_bidx')) {
            Schema::table('ngo_donors', function (Blueprint $table) {
                $table->string('portal_token_bidx', 64)->nullable()->after('portal_token');
                $table->index('portal_token_bidx');
            });
        }

        DB::table('ngo_donors')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $token = $row->portal_token;
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
                    DB::table('ngo_donors')->where('id', $row->id)->update([
                        'portal_token_bidx' => hash_hmac('sha256', $plain, config('app.key')),
                    ]);
                } else {
                    DB::table('ngo_donors')->where('id', $row->id)->update([
                        'portal_token'      => Crypt::encryptString($token),
                        'portal_token_bidx' => hash_hmac('sha256', $token, config('app.key')),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('ngo_donors')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $token = $row->portal_token;
                if ($token === null || $token === '' || !str_starts_with($token, 'eyJ')) {
                    continue;
                }
                try {
                    $plain = Crypt::decryptString($token);
                    DB::table('ngo_donors')->where('id', $row->id)->update([
                        'portal_token' => $plain,
                    ]);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    // Skip
                }
            }
        });

        DB::statement('ALTER TABLE ngo_donors MODIFY portal_token VARCHAR(36) NULL');

        Schema::table('ngo_donors', function (Blueprint $table) {
            $table->dropIndex(['portal_token_bidx']);
            $table->dropColumn('portal_token_bidx');
        });
    }
};
