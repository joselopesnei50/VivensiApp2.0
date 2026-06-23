<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 3/9).
 *
 * Metas físico-financeiras do Plano de Trabalho. Toda NF, refeição, capacitação
 * reconcilia contra UMA meta — nada solto (regra inegociável §3.3 do roadmap).
 *
 * Tipo:
 *  - fisica       → ex.: "refeições/mês servidas" (unidade = "refeições/mês")
 *  - financeira   → ex.: valor de execução em custeio
 *  - qualificacao → meta de capacitação (Fase 6)
 *
 * O cronograma mensal fica em metas_cronograma (próxima migration) — queries
 * "realizado vs meta no mês X" ficam triviais com JOIN + GROUP BY (decisão §3.3
 * em docs/fase-0-analise.md).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('metas')) {
            return;
        }

        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_trabalho_id')->constrained('planos_trabalho')->cascadeOnDelete();
            $table->enum('tipo', ['fisica', 'financeira', 'qualificacao']);
            $table->string('descricao', 500);
            $table->string('unidade', 40);
            $table->decimal('quantidade_prevista', 18, 4);
            $table->decimal('valor_previsto', 18, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'plano_trabalho_id']);
            $table->index(['plano_trabalho_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
