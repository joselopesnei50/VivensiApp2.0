<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Linha do tempo do lead (Fase 5 — item 5.2): notas manuais do atendente,
     * eventos do WhatsApp, conclusões de formulário, ações sugeridas pelo
     * Bruce. É o que o painel de detalhe do lead renderiza em ordem
     * cronológica.
     */
    public function up(): void
    {
        Schema::create('lead_timeline_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // type: note | whatsapp_message | form_completed | consent | next_action | ai_suggestion | status_change
            $table->string('type', 30);
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_timeline_items');
    }
};
