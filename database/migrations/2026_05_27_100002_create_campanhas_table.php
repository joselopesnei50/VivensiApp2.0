<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->text('mensagem'); // suporta {nome} como variável
            $table->enum('status', ['rascunho', 'agendada', 'processando', 'concluida', 'cancelada'])
                  ->default('rascunho');
            $table->timestamp('agendada_para')->nullable();
            $table->integer('total_contatos')->default(0);
            $table->integer('total_enviados')->default(0);
            $table->integer('total_falhas')->default(0);
            $table->integer('intervalo_segundos')->default(3);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanhas');
    }
};
