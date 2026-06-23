<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 1/9).
 *
 * Termo de Colaboração é a raiz do dossiê: a fonte da verdade contra a qual
 * todo dado de execução (refeições, NFs, parcelas, prestação de contas) se
 * reconcilia. Sem ele, nenhum dado downstream tem suporte jurídico.
 *
 * Decisões de schema (ver docs/fase-0-analise.md §3.2):
 *  - modalidade_execucao fica AQUI (não em Cozinha) para sustentar a regra do
 *    roadmap §2: na execução direta a gestora é uma cozinha de equipamento
 *    próprio (gestora e cozinha colapsam num cadastro só).
 *  - documento_url aponta pra S3 com URL assinada (R5 do guardião).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('termos_colaboracao')) {
            return;
        }

        Schema::create('termos_colaboracao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 60);
            $table->string('mds_sesan_ref', 120)->nullable();
            $table->date('vigencia_inicio');
            $table->date('vigencia_fim');
            $table->decimal('valor_global', 18, 2);
            $table->enum('modalidade_execucao', ['direta', 'indireta']);
            $table->enum('status', ['vigente', 'encerrado', 'suspenso', 'em_diligencia'])->default('vigente');
            $table->string('documento_url', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'numero']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vigencia_inicio', 'vigencia_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termos_colaboracao');
    }
};
