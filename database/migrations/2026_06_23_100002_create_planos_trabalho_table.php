<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 2/9).
 *
 * Plano de Trabalho aprovado do Termo. 1-N por termo: planos podem ser
 * aditivados/reformulados durante a vigência (campo `versao`). A reconciliação
 * histórica precisa saber qual plano estava vigente "à data X".
 *
 * Status:
 *  - aprovado: vigente, usado pra reconciliar dados de execução
 *  - em_revisao: rascunho, NÃO reconcilia
 *  - substituido: já existe uma versão mais nova aprovada
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('planos_trabalho')) {
            return;
        }

        Schema::create('planos_trabalho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('termo_id')->constrained('termos_colaboracao')->cascadeOnDelete();
            $table->unsignedSmallInteger('versao')->default(1);
            $table->enum('status', ['aprovado', 'em_revisao', 'substituido'])->default('em_revisao');
            $table->text('objeto')->nullable();
            $table->date('aprovado_em')->nullable();
            $table->string('documento_url', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'termo_id', 'status']);
            $table->unique(['termo_id', 'versao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_trabalho');
    }
};
