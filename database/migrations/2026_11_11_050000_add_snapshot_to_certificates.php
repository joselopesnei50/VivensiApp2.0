<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Certificado guarda o que foi certificado no dia da emissão (nome do aluno,
 * curso, professor e carga horária). Assim a verificação pública continua
 * certa mesmo se o curso for editado ou excluído depois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'student_name'))     $table->string('student_name')->nullable()->after('code');
            if (!Schema::hasColumn('certificates', 'course_title'))     $table->string('course_title')->nullable()->after('student_name');
            if (!Schema::hasColumn('certificates', 'teacher_name'))     $table->string('teacher_name')->nullable()->after('course_title');
            if (!Schema::hasColumn('certificates', 'workload_minutes')) $table->unsignedInteger('workload_minutes')->nullable()->after('teacher_name');
        });

        // Preenche os certificados já emitidos com os dados atuais.
        DB::table('certificates')->whereNull('course_title')->orderBy('id')->each(function ($c) {
            $course = DB::table('courses')->where('id', $c->course_id)->first();
            $minutes = (int) DB::table('lessons')->join('modules', 'modules.id', '=', 'lessons.module_id')
                ->where('modules.course_id', $c->course_id)->sum('lessons.duration_minutes');
            DB::table('certificates')->where('id', $c->id)->update([
                'student_name'     => DB::table('users')->where('id', $c->user_id)->value('name'),
                'course_title'     => $course->title ?? null,
                'teacher_name'     => $course->teacher_name ?? null,
                'workload_minutes' => $minutes ?: null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['student_name', 'course_title', 'teacher_name', 'workload_minutes']);
        });
    }
};
