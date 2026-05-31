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

        // Preenche tenant_id a partir do chat relacionado (subquery — compatível com MySQL e SQLite)
        DB::statement('
            UPDATE whatsapp_notes
            SET tenant_id = (SELECT tenant_id FROM whatsapp_chats WHERE id = whatsapp_notes.chat_id)
            WHERE tenant_id IS NULL
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
