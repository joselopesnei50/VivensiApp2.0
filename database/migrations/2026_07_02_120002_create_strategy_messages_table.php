<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sala de Estrategia — Fase 0. Falas dos agentes.
 *
 * facts_used: array JSON com os "handles" dos dados usados pra fundamentar
 * a fala (ex: ["saldo_mes","project_health_score"]). Essa e a trave de
 * confianca discutida em md/vivensi-sala-estrategia-arquitetura.md §4 —
 * permite a UI (Fase 2+) mostrar "isto veio do painel" vs "isto e
 * interpretacao do agente".
 *
 * softDeletes preservam a fala pra auditoria mesmo apos "ocultar", igual
 * ao padrao de internal_messages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('strategy_session_id')
                ->constrained('strategy_sessions')
                ->cascadeOnDelete();
            $table->string('agent');          // 'financeiro' | futuros: 'mobilizacao','inteligencia','estrategista_chefe'
            $table->text('content');          // a fala em portugues
            $table->json('facts_used')->nullable();
            $table->string('confidence');     // 'alta' | 'media' | 'baixa'
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_messages');
    }
};
