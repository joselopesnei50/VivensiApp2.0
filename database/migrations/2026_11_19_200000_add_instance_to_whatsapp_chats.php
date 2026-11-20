<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Por qual número (instância) a conversa chegou. A resposta tem de sair pelo
 * mesmo número: com Evolution e API oficial conectadas, a resposta ia sempre
 * pela oficial e a Meta recusava (o cliente tinha escrito no outro número).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('whatsapp_chats', 'whatsapp_instance_id')) {
            return;
        }
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('whatsapp_instance_id')->nullable()->after('tenant_id');
            $table->index('whatsapp_instance_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('whatsapp_chats', 'whatsapp_instance_id')) {
            return;
        }
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_instance_id']);
            $table->dropColumn('whatsapp_instance_id');
        });
    }
};
