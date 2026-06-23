<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 1 (item 5/5).
 *
 * Estorno auditado de registro de refeição. Único caminho legítimo para
 * "corrigir" um registro depois que o período fecha.
 *
 * Workflow:
 *  - status=pendente: solicitação aberta pelo coordenador/gestor.
 *  - aprovado: NGO aprova (na modalidade indireta) OU gestora aprova a si
 *    própria (direta). O registro_id correspondente vai para
 *    status='estornado' e deixa de contar no realizado.
 *  - rejeitado: NGO recusa o pedido; registro permanece valido.
 *
 * evidencia_url aponta pra S3 (foto da nota de erro, justificativa escrita
 * digitalizada, etc).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('estornos_refeicao')) {
            return;
        }

        Schema::create('estornos_refeicao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registro_id')->constrained('registros_refeicao')->cascadeOnDelete();

            $table->text('motivo');
            $table->string('evidencia_url', 500)->nullable();

            $table->foreignId('solicitado_por_user_id')->constrained('users');
            $table->foreignId('aprovado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable();

            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('registro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estornos_refeicao');
    }
};
