<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_configs', 'client_token_hash')) {
                $table->string('client_token_hash', 64)->nullable()->after('client_token');
                $table->index('client_token_hash');
            }
        });

        // Backfill hashes for existing rows
        $rows = DB::table('whatsapp_configs')
            ->select('id', 'client_token', 'client_token_hash')
            ->whereNotNull('client_token')
            ->get();

        foreach ($rows as $r) {
            if (!empty($r->client_token_hash)) continue;
            $token = trim((string) $r->client_token);
            if ($token === '') continue;
            DB::table('whatsapp_configs')
                ->where('id', $r->id)
                ->update(['client_token_hash' => hash('sha256', $token)]);
        }
    }

    public function down()
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_configs', 'client_token_hash')) {
                $table->dropIndex(['client_token_hash']);
                $table->dropColumn('client_token_hash');
            }
        });
    }
};

