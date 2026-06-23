<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 1 (item 4/5).
 *
 * Marca períodos (cozinha_id, mês) como FECHADOS. A partir do fechamento:
 *  - registros do mês ficam imutáveis (observer reject update direto)
 *  - correção exige fluxo de estorno (estornos_refeicao)
 *
 * content_hash_total: SHA-256 sobre o agregado de hashes dos registros do mês,
 * pra detectar tampering retroativo no banco.
 *
 * Permissão: somente NGO/super_admin pode fechar (Policy). Coordenador NÃO
 * fecha período — separação de papéis.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('periodos_fechados')) {
            return;
        }

        Schema::create('periodos_fechados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cozinha_id')->constrained()->cascadeOnDelete();
            $table->date('mes'); // primeiro dia do mês (YYYY-MM-01)
            $table->timestamp('fechado_em');
            $table->foreignId('fechado_por_user_id')->constrained('users');
            $table->string('content_hash_total', 64);
            $table->unsignedInteger('registros_count')->default(0);
            $table->unsignedInteger('refeicoes_total')->default(0);
            $table->timestamps();

            $table->unique(['cozinha_id', 'mes']);
            $table->index(['tenant_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_fechados');
    }
};
