<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos_conformidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('eixo', 20);           // cebas/mrosc/suas
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('enquadramento', 20)->nullable(); // 3_anos/5_anos/anual/por_parceria
            $table->string('status', 20)->default('em_andamento'); // em_andamento/encerrado/renovado
            $table->text('observacoes')->nullable();
            // Overrides de threshold por tenant (JSON: {"CEBAS-AS-002": 25.0})
            $table->json('overrides')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'eixo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos_conformidade');
    }
};
