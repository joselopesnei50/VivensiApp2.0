<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration idempotente das tabelas do Academy (LMS).
 *
 * A migration original 2026_02_08_234540_create_academy_tables.php ficou vazia
 * (so criou academy_tables com id+timestamps). As tabelas reais foram criadas
 * manualmente em producao. Esta migration cobre dev/CI/testes com
 * RefreshDatabase, checando hasTable antes de criar pra nao conflitar com
 * ambientes onde ja existem.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->string('teacher_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id');
                $table->string('title');
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
                $table->index(['course_id', 'order']);
            });
        }

        if (!Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('module_id');
                $table->string('title');
                $table->string('video_url')->nullable();
                $table->string('document_url')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->string('type', 20)->default('video'); // video | ebook | pdf
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
                $table->index(['module_id', 'order']);
            });
        }

        if (!Schema::hasTable('lesson_user')) {
            Schema::create('lesson_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->unique(['lesson_id', 'user_id'], 'lesson_user_uniq');
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id');
                $table->string('code', 32)->unique();
                $table->timestamp('issued_at');
                $table->timestamps();
                $table->unique(['user_id', 'course_id'], 'cert_user_course_uniq');
            });
        }
    }

    public function down(): void
    {
        // Nao dropa em down — protecao contra reverter em prod acidentalmente
        // e apagar historico de alunos. Se precisar mesmo, faca manualmente.
    }
};
