<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bruno lessons — passagens de conversas que fecharam venda, cadastradas
 * manualmente por gestor. Bruno le as N mais relevantes por match de tag
 * com a situacao atual do lead e injeta no prompt como few_shots dinamicos.
 *
 * Formato alternativa mais leve ao RAG com embeddings — vai bem ate ~500
 * lessons cadastradas; depois disso migrar pra embeddings + vetorizacao.
 *
 * tenant_id nullable: NULL = licao global (todo tenant usa). Preenchido
 * = licao especifica desse tenant (ex: casos regionais, verticais).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bruno_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title', 200)->comment('Titulo curto pra o gestor identificar');
            $table->json('tags')->comment('Ex: ["ong-pequena", "objecao-preco", "desconfianca-vendor"]');
            $table->text('situation')->comment('Quando essa licao se aplica');
            $table->text('lead_said')->nullable()->comment('Exemplo do que o lead disse');
            $table->text('bruno_replied')->comment('Resposta que fechou / avancou o funil');
            $table->text('notes')->nullable()->comment('Anotacoes internas — nao vao pro prompt');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bruno_lessons');
    }
};
