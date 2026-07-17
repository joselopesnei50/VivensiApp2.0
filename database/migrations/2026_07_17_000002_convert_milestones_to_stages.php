<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 — Etapas de Projeto (ProjectStage).
 *
 * Estrategia: renomear `project_milestones` -> `project_stages` e adicionar
 * colunas financeiras/temporais. `title` e `target_date` sao preservados
 * para retrocompatibilidade da UI de Planejamento (Fase 3 substitui).
 *
 * `stage_id` nullable em tasks + transactions para vincular tarefa/gasto a
 * uma etapa quando o projeto usa stages (feature opt-in).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_stages') && Schema::hasTable('project_milestones')) {
            Schema::rename('project_milestones', 'project_stages');
        }

        Schema::table('project_stages', function (Blueprint $t) {
            if (! Schema::hasColumn('project_stages', 'start_date')) {
                $t->date('start_date')->nullable()->after('description');
            }
            if (! Schema::hasColumn('project_stages', 'end_date')) {
                $t->date('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('project_stages', 'planned_value')) {
                $t->decimal('planned_value', 12, 2)->default(0)->after('end_date');
            }
            if (! Schema::hasColumn('project_stages', 'order')) {
                $t->unsignedInteger('order')->default(0)->after('planned_value');
            }
            if (! Schema::hasColumn('project_stages', 'deleted_at')) {
                $t->softDeletes();
            }
        });

        // Enum antigo era ('pending','reached','missed'). ProjectStage precisa
        // de 'in_progress' e 'completed' (regra de is_current). Trocar por
        // VARCHAR simplifica evolucao futura sem novo ALTER de enum.
        // `target_date` era NOT NULL nos milestones antigos; stages novas usam
        // start_date/end_date. Tornar nullable pra permitir criar stages sem
        // esse campo legado.
        //
        // MySQL usa DDL direto (mais rapido e evita quirks do doctrine com enum).
        // SQLite (usado nos tests unitarios in-memory) trata enum como VARCHAR
        // com CHECK; deixamos o CHECK cair via change() do doctrine.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_stages MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE project_stages MODIFY COLUMN target_date DATE NULL");
        } else {
            Schema::table('project_stages', function (Blueprint $t) {
                $t->string('status', 30)->default('pending')->change();
                $t->date('target_date')->nullable()->change();
            });
        }

        Schema::table('project_stages', function (Blueprint $t) {
            $t->index(['project_id', 'order']);
        });

        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'stage_id')) {
            Schema::table('tasks', function (Blueprint $t) {
                $t->foreignId('stage_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('project_stages')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'stage_id')) {
            Schema::table('transactions', function (Blueprint $t) {
                $t->foreignId('stage_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('project_stages')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'stage_id')) {
            Schema::table('transactions', function (Blueprint $t) {
                $t->dropForeign(['stage_id']);
                $t->dropColumn('stage_id');
            });
        }

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'stage_id')) {
            Schema::table('tasks', function (Blueprint $t) {
                $t->dropForeign(['stage_id']);
                $t->dropColumn('stage_id');
            });
        }

        if (Schema::hasTable('project_stages')) {
            Schema::table('project_stages', function (Blueprint $t) {
                $t->dropIndex(['project_id', 'order']);
                if (Schema::hasColumn('project_stages', 'deleted_at')) {
                    $t->dropSoftDeletes();
                }
                $t->dropColumn(['start_date', 'end_date', 'planned_value', 'order']);
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE project_stages MODIFY COLUMN status ENUM('pending','reached','missed') NOT NULL DEFAULT 'pending'");
                DB::statement("ALTER TABLE project_stages MODIFY COLUMN target_date DATE NOT NULL");
            }

            Schema::rename('project_stages', 'project_milestones');
        }
    }
};
