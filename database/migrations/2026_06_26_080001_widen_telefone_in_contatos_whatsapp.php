<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vivensi — defesa contra erro 1406 (Data too long for column 'telefone').
 *
 * A coluna `contatos_whatsapp.telefone` foi criada como varchar(20). O Baileys
 * envia wa_id incluindo sufixo (ex.: número de grupo termina em @g.us com
 * 22+ chars) e às vezes números internacionais longos. Aumentamos pra 40 pra
 * não estourar e ainda manter o índice eficiente.
 *
 * Reversível e idempotente.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('contatos_whatsapp')) {
            return;
        }
        Schema::table('contatos_whatsapp', function (Blueprint $table) {
            $table->string('telefone', 40)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contatos_whatsapp')) {
            return;
        }
        Schema::table('contatos_whatsapp', function (Blueprint $table) {
            $table->string('telefone', 20)->change();
        });
    }
};
