<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cozinha Solidária — Fase 0 (item 7/9).
 *
 * Pivot User × Cozinha. Segue o padrão estabelecido em ProjectMember
 * (app/Models/ProjectMember.php:12-17) — `tenant_id` + outro escopo (cá
 * `cozinha_id`) + `user_id`.
 *
 * Decisão (docs/fase-0-analise.md §3.2): NÃO usar coluna `cozinha_id` em
 * users.  Um coordenador pode cobrir múltiplas cozinhas (cidades vizinhas na
 * execução indireta). Pivot é a generalização correta.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cozinha_coordenadores')) {
            return;
        }

        Schema::create('cozinha_coordenadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cozinha_id')->constrained('cozinhas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('papel', ['coordenador', 'auxiliar'])->default('coordenador');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['cozinha_id', 'user_id']);
            $table->index(['user_id', 'ativo']);
            $table->index(['tenant_id', 'cozinha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cozinha_coordenadores');
    }
};
