<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
        });

        // Backfill a partir do chat relacionado
        DB::statement('
            UPDATE whatsapp_messages wm
            JOIN whatsapp_chats wc ON wc.id = wm.chat_id
            SET wm.tenant_id = wc.tenant_id
        ');
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
