<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formulários conversacionais por tenant (Fase 4 — item 2.5 do roadmap).
     * Cada form tem N perguntas ordenadas; o atendente dispara o form em
     * uma conversa e o FormEngine vai mandando as perguntas e capturando
     * as respostas até concluir.
     */
    public function up(): void
    {
        Schema::create('whatsapp_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_forms');
    }
};
