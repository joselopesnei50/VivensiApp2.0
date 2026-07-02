<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sala de Estrategia — Fase 3.
 *
 * proposed_action: JSON {titulo, descricao} extraido da sintese do Chefe.
 * kanban_card_id: card criado a partir da decisao (1 por sessao, nullable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('strategy_sessions', function (Blueprint $table) {
            $table->json('proposed_action')->nullable()->after('status');
            $table->unsignedBigInteger('kanban_card_id')->nullable()->after('proposed_action')->index();
        });
    }

    public function down(): void
    {
        Schema::table('strategy_sessions', function (Blueprint $table) {
            $table->dropColumn(['proposed_action', 'kanban_card_id']);
        });
    }
};
