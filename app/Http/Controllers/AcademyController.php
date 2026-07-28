<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    /**
     * Catalogo de cursos (index Netflix-style).
     *
     * Otimizado contra N+1: eager loading em modules.lessons + pivot filtrado
     * ao user autenticado. O calculo de progresso e feito em memoria a partir
     * da coleção ja carregada — zero queries adicionais no loop.
     */
    public function index()
    {
        $userId = auth()->id();

        $courses = Course::where('is_active', true)
            ->with([
                'modules' => fn ($q) => $q->orderBy('order'),
                'modules.lessons' => fn ($q) => $q->orderBy('order'),
                // Pivot filtrado ao user autenticado — traz apenas as aulas
                // finalizadas por ele (finished_at NOT NULL).
                'modules.lessons.users' => fn ($q) => $q
                    ->where('lesson_user.user_id', $userId)
                    ->whereNotNull('lesson_user.finished_at'),
            ])
            ->get();

        foreach ($courses as $course) {
            [$total, $completed] = $this->computeProgress($course);
            $course->total_lessons = $total;
            $course->progress      = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        }

        return view('academy.index', compact('courses'));
    }

    /**
     * Player do curso. Se `?lesson_id=X` for informado E pertencer ao curso,
     * abre essa aula. Caso contrario, abre a primeira nao concluida (ou a
     * primeira do curso se tudo estiver ok).
     *
     * BUG anterior: o parametro lesson_id gerado pelos links da sidebar era
     * ignorado — o usuario clicava numa aula e o player abria em outra.
     */
    public function show(Request $request, $slug)
    {
        $userId = auth()->id();

        $course = Course::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'modules' => fn ($q) => $q->orderBy('order'),
                'modules.lessons' => fn ($q) => $q->orderBy('order'),
                'modules.lessons.users' => fn ($q) => $q
                    ->where('lesson_user.user_id', $userId)
                    ->whereNotNull('lesson_user.finished_at'),
            ])
            ->firstOrFail();

        // Marca is_completed em memoria (pivot ja preloaded, sem query extra).
        $allLessons = collect();
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $lesson->is_completed = $lesson->users->isNotEmpty();
                $allLessons->push($lesson);
            }
        }

        // Resolve currentLesson honrando ?lesson_id=X com validacao anti-IDOR
        // (a aula tem que estar no curso; senao ignora o parametro).
        $requestedLessonId = (int) $request->query('lesson_id', 0);
        $currentLesson = null;
        if ($requestedLessonId > 0) {
            $currentLesson = $allLessons->firstWhere('id', $requestedLessonId);
        }
        if (!$currentLesson) {
            $currentLesson = $allLessons->firstWhere('is_completed', false)
                ?? $allLessons->first();
        }

        [$total, $completed] = $this->computeProgress($course);
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $certificate = Certificate::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->first();

        return view('academy.show', compact('course', 'currentLesson', 'certificate', 'progress'));
    }

    /**
     * Marca uma aula como assistida pelo usuario autenticado.
     *
     * Guardas:
     *  - Aula so pode ser marcada se pertence a curso is_active=true
     *    (evita cadastrar progresso em cursos desativados/rascunho).
     *  - Rate limit (throttle:60,1 na rota) protege contra loop.
     *  - Idempotente: chamar 2x atualiza finished_at sem duplicar linha.
     */
    public function markLessonAsViewed(Request $request, $id)
    {
        $userId = auth()->id();

        // Eager load do curso via modules pra validar is_active sem N+1.
        $lesson = Lesson::with('module.course:id,is_active')->findOrFail($id);
        $course = $lesson->module?->course;

        if (!$course || !$course->is_active) {
            return response()->json([
                'success' => false,
                'error'   => 'Aula indisponivel.',
            ], 404);
        }

        // Idempotente via updateOrCreate na pivot table.
        if ($lesson->users()->where('user_id', $userId)->exists()) {
            $lesson->users()->updateExistingPivot($userId, ['finished_at' => now()]);
        } else {
            $lesson->users()->attach($userId, ['finished_at' => now()]);
        }

        $certificateIssued = $this->checkAndGenerateCertificate($userId, $course->id);

        return response()->json([
            'success'            => true,
            'certificate_issued' => $certificateIssued,
        ]);
    }

    /**
     * Gera certificado se o user completou 100% do curso.
     * Otimizado: usa 2 queries agregadas (COUNT) em vez do loop N+1 anterior.
     * Codigo: 16 chars uppercase (~2.8e24 combinacoes) — colisao pratica zero.
     */
    private function checkAndGenerateCertificate(int $userId, int $courseId): bool
    {
        $totalLessons = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $courseId))
            ->count();

        if ($totalLessons === 0) {
            return false;
        }

        $completedLessons = \DB::table('lesson_user')
            ->join('lessons', 'lessons.id', '=', 'lesson_user.lesson_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->where('lesson_user.user_id', $userId)
            ->whereNotNull('lesson_user.finished_at')
            ->distinct('lesson_user.lesson_id')
            ->count('lesson_user.lesson_id');

        if ($completedLessons < $totalLessons) {
            return false;
        }

        return (bool) Certificate::firstOrCreate(
            ['user_id' => $userId, 'course_id' => $courseId],
            [
                'code'      => strtoupper(\Illuminate\Support\Str::random(16)),
                'issued_at' => now(),
            ]
        )->wasRecentlyCreated;
    }

    /**
     * Lista todos os certificados do usuario autenticado.
     */
    public function certificates()
    {
        $certificates = Certificate::with('course:id,title,slug,thumbnail_url,teacher_name')
            ->where('user_id', auth()->id())
            ->orderByDesc('issued_at')
            ->get();

        return view('academy.certificates', compact('certificates'));
    }

    /**
     * Download do PDF do certificado.
     * Autorizacao: dono OR super_admin. Rate-limitado via rota (throttle:20,1).
     */
    public function downloadCertificate($code)
    {
        $certificate = Certificate::with(['user', 'course'])
            ->where('code', $code)
            ->firstOrFail();

        if (auth()->id() !== $certificate->user_id && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('academy.certificate_pdf', compact('certificate'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Certificado-Vivensi-' . $certificate->code . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Conta total de aulas do curso e quantas o user autenticado ja completou,
     * usando SO a coleção pre-carregada (zero queries adicionais).
     * Requer $course carregado com modules.lessons.users filtrado ao user.
     *
     * @return array [totalLessons, completedLessons]
     */
    private function computeProgress(Course $course): array
    {
        $total = 0;
        $completed = 0;
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $total++;
                if ($lesson->users->isNotEmpty()) {
                    $completed++;
                }
            }
        }
        return [$total, $completed];
    }
}
