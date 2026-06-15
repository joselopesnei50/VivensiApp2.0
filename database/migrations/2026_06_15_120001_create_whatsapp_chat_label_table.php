<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot many-to-many entre whatsapp_chats e whatsapp_labels.
     *
     * Substitui a coluna `labels` JSON em whatsapp_chats por relação real,
     * permitindo:
     *  - Rename de etiqueta sem precisar varrer todos os chats
     *  - Query indexada para filtrar chats por etiqueta (broadcast por
     *    etiqueta na Fase 2 vai usar essa estrutura)
     *  - Integridade referencial: deletar label cascateia no pivot
     *
     * A coluna whatsapp_chats.labels (JSON) é mantida durante a transição
     * para compat. Será removida em release futura após confirmar que
     * todos os chats foram migrados para o pivot via:
     *   php artisan whatsapp:migrate-labels --apply
     */
    public function up(): void
    {
        Schema::create('whatsapp_chat_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('whatsapp_chats')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('whatsapp_labels')->cascadeOnDelete();
            $table->timestamps();

            // Não permite o mesmo chat receber a mesma etiqueta duas vezes
            $table->unique(['chat_id', 'label_id']);

            // Index para queries do broadcast por etiqueta
            $table->index(['label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_label');
    }
};
