<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix ProcessEvolutionWebhook failing 1406 Data too long (2026-08-05).
 *
 * `media_caption` foi criado como VARCHAR(255) mas mensagens WhatsApp
 * inbound trazem legendas de imagem/video de ate ~1024 chars (limite Meta) —
 * e alguns clientes colam textos maiores. Job falhava e ia pra failed_jobs.
 * Trocado por TEXT (65k chars) — mesmo tipo ja usado em `content`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->text('media_caption')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('media_caption')->nullable()->change();
        });
    }
};
