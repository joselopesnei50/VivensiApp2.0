<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kanban geral do Painel Gestor de Projetos (Fase 3 — item 2.2 do roadmap).
     * Tabela de boards: cada tenant tem 1 ou mais boards. O default é criado
     * sob demanda quando o usuário acessa /manager/kanban pela primeira vez,
     * com colunas vindas dos templates por Perfil Operacional.
     */
    public function up(): void
    {
        Schema::create('kanban_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('color', 9)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_boards');
    }
};
