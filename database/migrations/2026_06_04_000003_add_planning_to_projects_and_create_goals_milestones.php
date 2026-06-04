<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 do modulo de Planejamento estrategico de projetos:
 * - Apresentacao institucional + objetivos (campos 1:1 no projects)
 * - Metas qualitativas (project_goals) — title, indicator, status, prazo
 * - Marcos planejados (project_milestones) — distintos do timeline historico
 *
 * Atras de feature flag PROJECT_PLANNING_ENABLED (default false) — sem
 * impacto no UI ate alguem ligar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('presentation')->nullable()->after('description');
            $table->text('justification')->nullable()->after('presentation');
            $table->text('context')->nullable()->after('justification');
            $table->text('target_audience')->nullable()->after('context');
            $table->text('general_objective')->nullable()->after('target_audience');
            $table->json('specific_objectives')->nullable()->after('general_objective');
        });

        Schema::create('project_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('indicator')->nullable(); // como medir qualitativamente
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_id']);
            $table->index('status');
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'reached', 'missed'])->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'project_id']);
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_goals');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'presentation',
                'justification',
                'context',
                'target_audience',
                'general_objective',
                'specific_objectives',
            ]);
        });
    }
};
