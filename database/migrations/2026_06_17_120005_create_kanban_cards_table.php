<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cards do Kanban (Fase 3 — item 2.2 do roadmap).
     * - Posição relativa por coluna (gaps de 1024 pra facilitar reorder
     *   sem reescrever vizinhos a cada move).
     * - whatsapp_chat_id opcional: card criado a partir de uma conversa
     *   mantém o link de volta para abrir o histórico.
     * - meta (json): campo extensível pra labels, qualificação do Bruce
     *   e metadados sem mudar schema.
     */
    public function up(): void
    {
        Schema::create('kanban_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('column_id')->constrained('kanban_columns')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('whatsapp_chat_id')->nullable()->constrained('whatsapp_chats')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->date('due_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['column_id', 'position']);
            $table->index(['tenant_id', 'whatsapp_chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_cards');
    }
};
