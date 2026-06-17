<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colunas do Kanban (Fase 3 — item 2.2). Posição relativa por board
     * (1, 2, 3...). Cor opcional pra identidade visual.
     */
    public function up(): void
    {
        Schema::create('kanban_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('kanban_boards')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 9)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedSmallInteger('wip_limit')->nullable();
            $table->timestamps();

            $table->index(['board_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_columns');
    }
};
