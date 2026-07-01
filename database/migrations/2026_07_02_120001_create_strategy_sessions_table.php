<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sala de Estrategia — Fase 0.
 *
 * Cada sessao e um "debate" (na Fase 0, so o Agente Financeiro fala,
 * mas o schema ja acomoda multiplos agentes na Fase 1+). trigger_type
 * distingue entre teste manual, queda no health score, doador em
 * declinio, etc — decidido caso a caso quando a orquestracao entrar.
 *
 * Espelha o padrao de internal_chats (meta) + internal_messages (falas)
 * conforme docs/vivensi-sala-estrategia-arquitetura.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('trigger_type');   // 'manual_test' | futuros: 'health_drop', 'donor_decline'
            $table->string('status')->default('em_andamento'); // 'em_andamento' | 'concluida'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_sessions');
    }
};
