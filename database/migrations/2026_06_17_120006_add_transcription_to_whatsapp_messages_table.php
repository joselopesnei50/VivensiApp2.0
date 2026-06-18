<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transcrição de áudio (Fase 4 — item 2.4 do roadmap). Campo opcional
     * preenchido sob demanda pelo AudioTranscriptionService. NUNCA contém
     * texto da IA respondendo o usuário — é só a fala original transcrita.
     *
     * Migration aditiva — não toca dados existentes.
     */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'transcription')) {
                $table->text('transcription')->nullable()->after('media_caption');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'transcription')) {
                $table->dropColumn('transcription');
            }
        });
    }
};
