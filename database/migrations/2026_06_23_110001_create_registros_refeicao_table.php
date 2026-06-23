<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 1 (item 1/5).
 *
 * Tabela-tronco do LASTRO. Cada registro de refeição é o que sustenta o
 * recurso numa diligência CGU/TCU — integridade é INEGOCIÁVEL.
 *
 * Estados:
 *  - pendente  → registro incompleto (sem foto/geotag/presença OU geotag fora
 *                do raio da cozinha). NÃO conta como "prestável" no realizado
 *                vs meta. UI deve pedir complemento.
 *  - valido    → registro completo, validado. Conta como realizado.
 *  - estornado → registro foi estornado via fluxo auditado. Não conta no
 *                realizado.
 *
 * Imutabilidade: após `periodos_fechados.fechado_em` ser populado para
 * (cozinha_id, mês do data_servico), updates diretos são bloqueados pelo
 * observer. Correção só por estorno + novo registro.
 *
 * content_hash: SHA-256 do conteúdo serializado, populado no fechamento do
 * período. Detecta tampering a posteriori.
 *
 * meta_id: FK opcional para a Meta física do Plano de Trabalho — quando
 * preenchido, reconciliação fica trivial. Quando nulo, fallback pra
 * cozinhas.meta_refeicoes_mes.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('registros_refeicao')) {
            return;
        }

        Schema::create('registros_refeicao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cozinha_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_id')->nullable()->constrained('metas')->nullOnDelete();

            $table->date('data_servico');
            $table->timestamp('datetime_registrado'); // hora do servidor — anti-fraude
            $table->enum('tipo', ['almoco', 'janta', 'sopa', 'marmita', 'outro']);
            $table->unsignedInteger('quantidade');

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->enum('status', ['pendente', 'valido', 'estornado'])->default('pendente');
            $table->string('content_hash', 64)->nullable();

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'cozinha_id', 'data_servico']);
            $table->index(['cozinha_id', 'status']);
            $table->index(['tenant_id', 'status', 'data_servico']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_refeicao');
    }
};
