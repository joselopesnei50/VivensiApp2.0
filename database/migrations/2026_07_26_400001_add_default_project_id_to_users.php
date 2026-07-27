<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Feature Credenciado: user com role=credenciado esta vinculado a UM projeto
// default (para login redirect direto ao workspace). Se a entidade lhe atribuir
// mais projetos, o dashboard /credenciado mostra seletor.
// Nao ha FK dura para nao bloquear delete de Project — projeto excluido apenas
// zera o default e o user cai no seletor de /credenciado (ou 403 se sem
// projetos restantes).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('default_project_id')->nullable()->after('supervisor_id');
            $table->index('default_project_id', 'users_default_project_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_default_project_idx');
            $table->dropColumn('default_project_id');
        });
    }
};
