<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 6/9).
 *
 * Cozinha é a sub-unidade do tenant. Modelo:
 *  - direta:   1-1 com o Termo (gestora = cozinha própria). Criada via observer
 *              quando o Termo é salvo com modalidade=direta (R3 do guardião).
 *  - indireta: N por Termo (gestora apoia uma rede).
 *
 * modalidade_execucao é COPIADA do Termo (decisão §3.2) — queries por cozinha
 * não precisam joinar termo toda vez.
 *
 * endereco_* e lat/lng espelham o pattern estruturado dos beneficiaries
 * (2026_05_25_100000) pra consistência de UX (mesmas labels, mesmo geocoding).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cozinhas')) {
            return;
        }

        Schema::create('cozinhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('termo_id')->constrained('termos_colaboracao')->cascadeOnDelete();
            $table->string('nome', 180);
            $table->unsignedInteger('meta_refeicoes_mes')->default(0);
            $table->enum('modalidade_execucao', ['direta', 'indireta']);
            $table->enum('status', ['ativa', 'inativa'])->default('ativa');

            // Endereço estruturado (mesmo pattern de beneficiaries).
            $table->string('address_zip', 12)->nullable();
            $table->string('address_street', 180)->nullable();
            $table->string('address_number', 30)->nullable();
            $table->string('address_complement', 120)->nullable();
            $table->string('address_neighborhood', 120)->nullable();
            $table->string('address_city', 120)->nullable();
            $table->string('address_state', 2)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('termo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cozinhas');
    }
};
