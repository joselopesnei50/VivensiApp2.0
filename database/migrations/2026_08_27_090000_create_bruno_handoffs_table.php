<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bruno handoffs — quando Bruno decide escalar pra humano, ele gera
 * briefing TL;DR + dados estruturados extraidos da conversa e cria um
 * registro pendente pro time humano assumir.
 *
 * Inbox do time humano vive em /admin/bruno/handoffs.
 *
 * Status:
 *   pendente  -> Bruno escalou, ninguem assumiu
 *   assumido  -> alguem do time humano clicou "assumir" (assumed_by/at)
 *   resolvido -> atendimento fechou (resolved_at)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bruno_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_chat_id')->constrained('whatsapp_chats')->cascadeOnDelete();
            $table->text('briefing')->comment('TL;DR gerado por IA — 2-3 paragrafos que o humano le em 20s');
            $table->json('structured_data')->comment('organizacao, cidade, orcamento_mencionado, urgencia, fase_funil, proxima_acao');
            $table->enum('status', ['pendente', 'assumido', 'resolvido'])->default('pendente');
            $table->foreignId('assumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assumed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable()->comment('Nota do humano ao resolver — opcional');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('assumed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bruno_handoffs');
    }
};
