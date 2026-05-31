<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('whatsapp_notes', 'tenant_id')) {
            return;
        }

        Schema::table('whatsapp_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
        });

        // Preenche tenant_id a partir do chat relacionado
        DB::statement('
            UPDATE whatsapp_notes wn
            JOIN whatsapp_chats wc ON wn.chat_id = wc.id
            SET wn.tenant_id = wc.tenant_id
            WHERE wn.tenant_id IS NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_notes', 'tenant_id')) {
            Schema::table('whatsapp_notes', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
