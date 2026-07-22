<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes_requisito', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->unsignedBigInteger('ciclo_conformidade_id');
            $table->foreign('ciclo_conformidade_id')->references('id')->on('ciclos_conformidade')->onDelete('cascade');

            $table->unsignedBigInteger('requisito_legal_id');
            $table->foreign('requisito_legal_id')->references('id')->on('requisitos_legais');

            $table->string('resultado', 20);               // verde/amarelo/vermelho/nao_aplicavel
            $table->decimal('valor_calculado', 10, 2)->nullable(); // Tipo A: percentual calculado
            $table->timestamp('avaliado_em');
            $table->unsignedBigInteger('avaliado_por')->nullable(); // null = calculado pelo sistema
            $table->foreign('avaliado_por')->references('id')->on('users')->onDelete('set null');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            // Uma avaliação por requisito por ciclo (mais recente sobrescreve via updateOrCreate)
            $table->index(['tenant_id', 'ciclo_conformidade_id', 'resultado'], 'aval_req_tenant_ciclo_resultado_idx');
            $table->index(['tenant_id', 'requisito_legal_id'], 'aval_req_tenant_requisito_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_requisito');
    }
};
