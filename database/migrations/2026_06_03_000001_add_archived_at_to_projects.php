<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona arquivamento "leve" de projetos:
 *  - archived_at: timestamp em que o projeto foi arquivado (null = ativo)
 *  - archived_by: id do usuário que arquivou (para auditoria)
 *
 * Não é soft delete: preserva todos os relacionamentos (transações,
 * timeline, beneficiários, tarefas) e permite "reativar". A tabela
 * projects tem const UPDATED_AT = null no model, então essa migration
 * adiciona apenas as novas colunas sem tocar em outras.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('status');
                $table->index('archived_at', 'projects_archived_at_index');
            }
            if (!Schema::hasColumn('projects', 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'archived_at')) {
                try { $table->dropIndex('projects_archived_at_index'); } catch (\Throwable $e) {}
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('projects', 'archived_by')) {
                $table->dropColumn('archived_by');
            }
        });
    }
};
