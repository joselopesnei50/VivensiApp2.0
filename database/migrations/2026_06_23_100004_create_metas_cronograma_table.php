<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 4/9).
 *
 * Cronograma mensal por meta. Tabela auxiliar de `metas` (decisão §3.3 — JSON
 * inline ficaria caro pra "realizado vs meta no mês X").
 *
 * `mes` armazenado como date no primeiro dia do mês (YYYY-MM-01) pra ordenação
 * natural e índice eficiente.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('metas_cronograma')) {
            return;
        }

        Schema::create('metas_cronograma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_id')->constrained('metas')->cascadeOnDelete();
            $table->date('mes');
            $table->decimal('quantidade', 18, 4);
            $table->decimal('valor', 18, 2)->nullable();
            $table->timestamps();

            $table->unique(['meta_id', 'mes']);
            $table->index('mes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_cronograma');
    }
};
