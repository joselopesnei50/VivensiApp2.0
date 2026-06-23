<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 5/9).
 *
 * Parcelas de repasse do MDS para o Termo. Atrasos/bloqueios geram alertas na
 * Fase 5 ("parcela em risco").
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('parcelas')) {
            return;
        }

        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('termo_id')->constrained('termos_colaboracao')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->decimal('valor', 18, 2);
            $table->date('previsto_em');
            $table->date('recebido_em')->nullable();
            $table->enum('status', ['prevista', 'recebida', 'atrasada', 'bloqueada'])->default('prevista');
            $table->string('comprovante_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['termo_id', 'numero']);
            $table->index(['tenant_id', 'status']);
            $table->index('previsto_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
