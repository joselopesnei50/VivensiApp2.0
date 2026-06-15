<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona coluna label_ids (JSON nullable) em broadcast_campaigns para
     * suportar o novo audience_type='labels' (Fase 2 das etiquetas — disparo
     * de campanha segmentado por etiqueta).
     *
     * Operação compatível: campanhas existentes continuam funcionando (label_ids
     * é null). Audience_type é string (não enum), então 'labels' é um novo valor
     * lógico sem necessidade de ALTER ao enum.
     */
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->json('label_ids')->nullable()->after('group_ids');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn('label_ids');
        });
    }
};
