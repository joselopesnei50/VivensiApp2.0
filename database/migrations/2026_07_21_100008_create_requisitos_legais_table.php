<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitos_legais', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // ex: CEBAS-AS-002
            $table->string('eixo', 20);             // cebas_geral|cebas_as|cebas_saude|cebas_educacao|mrosc|suas
            $table->string('area', 20)->default('geral'); // as|saude|educacao|geral
            $table->string('titulo', 200);
            $table->text('enunciado');
            $table->string('base_legal', 300)->nullable();
            $table->char('tipo', 1);               // A|B|C
            $table->string('periodicidade', 20);   // continua|mensal|anual|por_ciclo
            $table->string('risco', 10);           // alto|medio|baixo
            $table->date('vigente_de');
            $table->date('vigente_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['eixo', 'ativo']);
            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitos_legais');
    }
};
