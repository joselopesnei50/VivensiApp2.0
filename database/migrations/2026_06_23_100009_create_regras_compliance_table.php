<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 9/9).
 *
 * Coração do compliance. Regras (vedações, teto de taxa de administração,
 * classificação custeio×capital) NUNCA são hardcoded no PHP — vivem aqui,
 * resolvidas por data de vigência via RegrasComplianceService.
 *
 * Schema:
 *  - tabela é GLOBAL (sem tenant_id): regras vêm de portarias federais, valem
 *    pra todos os tenants.
 *  - chave é o identificador semântico (ex.: 'taxa_administracao_teto').
 *  - parametros é JSON: cada chave tem schema próprio sem explodir colunas.
 *  - fonte_legal é rastreável (ex.: "Portaria MDS 1.131/2025, art. 14").
 *  - curado_por_user_id + curado_em: trilha jurídica obrigatória — o service
 *    se recusa a aplicar regra sem curadoria (R1 do guardião).
 *  - aplica_a_modalidade: direta/indireta/ambas (algumas regras só valem em
 *    uma das modalidades).
 *
 * Datas: vigencia_inicio é obrigatória; vigencia_fim nullable (regra ainda
 * vigente). Resolução por data faz BETWEEN com tolerância no fim.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('regras_compliance')) {
            return;
        }

        Schema::create('regras_compliance', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 80);
            $table->json('parametros')->nullable();
            $table->string('fonte_legal', 300);
            $table->date('vigencia_inicio');
            $table->date('vigencia_fim')->nullable();
            $table->enum('aplica_a_modalidade', ['direta', 'indireta', 'ambas'])->default('ambas');

            $table->foreignId('curado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('curado_em')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['chave', 'vigencia_inicio']);
            $table->index(['chave', 'vigencia_fim']);
            $table->index('curado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_compliance');
    }
};
