<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turmas — camada intermediaria entre Projeto NGO e ClassSession.
 *
 * Contexto: hoje professores criam N ClassSession soltas ("Aula de Violao 05/07",
 * "Aula de Violao 07/07"...) com mesmo horario, professor e alunos, sem noção
 * de "turma persistente". ProjectClass = turma recorrente com defaults +
 * matriculados fixos; ClassSession vira a chamada de UM dia dessa turma.
 *
 * Nome ProjectClass (nao Course) para nao colidir com Academy\Course (LMS).
 *
 * Retrocompat: ClassSession.project_class_id e NULLABLE. Chamadas antigas
 * continuam funcionando sem serem tocadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Defaults propagados para novas ClassSessions
            $table->unsignedBigInteger('default_teacher_user_id')->nullable()->index()
                ->comment('FK omitida — mesmo padrao de class_sessions.teacher_user_id');
            $table->enum('default_mode', ['fechada', 'aberta'])->default('fechada');
            $table->time('default_start_time')->nullable();
            $table->time('default_end_time')->nullable();

            // Programacao semanal — JSON array de ISO weekdays (1=segunda ... 7=domingo)
            // Ex: [1,3,5] = seg/qua/sex; [] = sem programacao fixa
            $table->json('weekdays')->nullable();

            $table->date('start_date')->nullable()->comment('Inicio de vigencia da turma');
            $table->date('end_date')->nullable()->comment('Fim de vigencia; NULL = indefinido');

            $table->unsignedInteger('max_students')->nullable()
                ->comment('Limite de vagas; NULL = ilimitado');

            $table->enum('status', ['ativo', 'encerrado'])->default('ativo');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'project_id', 'status'], 'project_classes_tenant_project_status_idx');
        });

        Schema::create('project_class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_class_id')->constrained('project_classes')->cascadeOnDelete();
            $table->foreignId('project_person_id')->constrained('project_people')->cascadeOnDelete();

            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('unenrolled_at')->nullable();
            $table->enum('status', ['ativo', 'saiu', 'concluido'])->default('ativo');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Um aluno so pode ter UM enrollment por turma
            $table->unique(['project_class_id', 'project_person_id'], 'project_class_enrollments_unique');
            $table->index(['tenant_id', 'project_class_id', 'status'], 'project_class_enrollments_tenant_status_idx');
        });

        // Retrocompat: chamadas antigas continuam sem turma vinculada.
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('project_class_id')->nullable()->after('project_id')
                ->constrained('project_classes')->nullOnDelete()
                ->comment('Turma da qual esta chamada faz parte. NULL = chamada avulsa (legado).');

            $table->index(['project_class_id', 'date'], 'class_sessions_project_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['project_class_id']);
            $table->dropIndex('class_sessions_project_class_date_idx');
            $table->dropColumn('project_class_id');
        });

        Schema::dropIfExists('project_class_enrollments');
        Schema::dropIfExists('project_classes');
    }
};
