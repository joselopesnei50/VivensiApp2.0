<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_acao_conformidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->unsignedBigInteger('requisito_legal_id');
            $table->foreign('requisito_legal_id')->references('id')->on('requisitos_legais');

            $table->string('titulo', 200);
            $table->text('descricao');
            $table->string('responsavel', 100);
            $table->date('prazo');
            $table->string('status', 20)->default('pendente'); // pendente|em_andamento|concluido|cancelado
            $table->text('observacoes_resolucao')->nullable();
            $table->timestamp('resolvido_em')->nullable();
            $table->unsignedBigInteger('resolvido_por')->nullable();
            $table->foreign('resolvido_por')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['tenant_id', 'status'],              'planos_acao_tenant_status_idx');
            $table->index(['tenant_id', 'requisito_legal_id'],  'planos_acao_tenant_req_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_acao_conformidade');
    }
};
