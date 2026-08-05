<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1 (2026-08-05) — historico dedicado de assignments do chat WhatsApp.
 *
 * Ate agora so tinhamos WhatsappAuditLog com JSON details, o que dificulta
 * queries agregadas para dashboard de produtividade (tempo medio, chats
 * ativos por agente, chats por atendimento). Esta tabela mantem um registro
 * canonico por handoff: quando comeca, quando termina, quem trocou.
 *
 * Convencao:
 *  - Assignment "aberto" tem `ended_at = NULL`. Ao criar um novo, o anterior
 *    do mesmo chat e fechado (ended_at + duration_seconds preenchidos).
 *  - `to_user_id = NULL` representa release (volta pra fila) — nao gera novo
 *    assignment aberto, so fecha o anterior.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_chat_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->string('action', 20); // take_over | transfer | release
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('chat_id')->references('id')->on('whatsapp_chats')->cascadeOnDelete();
            $table->foreign('from_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'to_user_id', 'ended_at'], 'wca_tenant_user_open_idx');
            $table->index(['chat_id', 'ended_at'], 'wca_chat_open_idx');
            $table->index(['tenant_id', 'started_at'], 'wca_tenant_started_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_assignments');
    }
};
