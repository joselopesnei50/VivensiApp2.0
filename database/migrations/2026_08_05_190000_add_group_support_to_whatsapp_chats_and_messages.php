<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suporte a grupos WhatsApp no chat (2026-08-05).
 *
 * Antes, o webhook Evolution extraia so o numero do remoteJid, entao chats
 * de grupo colidiam com contatos individuais e o contact_name virava o nome
 * de quem falou por ultimo — bagunca visivel no painel. Agora:
 *  - whatsapp_chats.is_group: marca chats de grupo (@g.us)
 *  - whatsapp_messages.sender_wa_id: quem falou (key.participant do Baileys)
 *  - whatsapp_messages.sender_name: nome exibido (pushName do participante)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('contact_phone')->index();
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('sender_wa_id', 60)->nullable()->after('chat_id');
            $table->string('sender_name', 120)->nullable()->after('sender_wa_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropIndex(['is_group']);
            $table->dropColumn('is_group');
        });
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['sender_wa_id', 'sender_name']);
        });
    }
};
