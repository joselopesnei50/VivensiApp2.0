<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    /**
     * Display a listing of courses (Netflix Style).
     */
    public function index()
    {
        // Fetch active courses with modules count and user progress
        $courses = Course::where('is_active', true)
            ->withCount('modules')
            ->with(['modules.lessons'])
            ->get()
            ->map(function ($course) {
                // Calculate progress
                $totalLessons = $course->modules->sum(function ($module) {
                    return $module->lessons->count();
                });

                $completedLessons = 0;
                $user = auth()->user();

                if ($user) {
                    // This is a bit N+1 but for MVP it's fine, we can optimize later with whereHas
                    foreach ($course->modules as $module) {
                        foreach ($module->lessons as $lesson) {
                             if ($lesson->users()->where('user_id', $user->id)->whereNotNull('finished_at')->exists()) {
                                 $completedLessons++;
                             }
                        }
                    }
                }

                $course->progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                $course->total_lessons = $totalLessons;
                return $course;
            });

        return view('academy.index', compact('courses'));
    }

    /**
     * Show the course player.
     */
    public function show($slug)
    {
        $course = Course::where('slug', $slug)
            ->where('is_active', true)
            ->with(['modules' => function ($query) {
                $query->orderBy('order');
            }, 'modules.lessons' => function ($query) {
                $query->orderBy('order');
            }])
            ->firstOrFail();

        // Get the current user
        $user = auth()->user();
        
        // Find the first unfinished lesson or the last one if all finished
        $currentLesson = null;
        $firstLesson = null;

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (!$firstLesson) $firstLesson = $lesson;
                
                // Check if user finished this lesson
                $isFinished = $lesson->users()
                    ->where('user_id', $user->id)
                    ->whereNotNull('finished_at')
                    ->exists();
                
                $lesson->is_completed = $isFinished;

                if (!$isFinished && !$currentLesson) {
                    $currentLesson = $lesson;
                }
            }
        }

        // If all finished, show the first one (or last one?)
        if (!$currentLesson) {
            $currentLesson = $firstLesson;
        }

        // Check if user has certificate for this course
        $certificate = \App\Models\Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // Calculate progress
        $totalLessons = $course->modules->sum(function ($module) {
            return $module->lessons->count();
        });
        $completedLessons = 0;
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if ($lesson->is_completed) {
                    $completedLessons++;
                }
            }
        }
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return view('academy.show', compact('course', 'currentLesson', 'certificate', 'progress'));
    }

    /**
     * Mark a lesson as viewed and check for course completion.
     */
    public function markLessonAsViewed(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $user = auth()->user();

        // Attach or update pivot
        if (!$lesson->users()->where('user_id', $user->id)->exists()) {
            $lesson->users()->attach($user->id, ['finished_at' => now()]);
        } else {
             $lesson->users()->updateExistingPivot($user->id, ['finished_at' => now()]);
        }

        // Check for course completion
        $courseId = $lesson->module->course_id;
        $this->checkAndGenerateCertificate($user, $courseId);

        return response()->json(['success' => true]);
    }

    private function checkAndGenerateCertificate($user, $courseId)
    {
        $course = Course::with(['modules.lessons'])->find($courseId);
        
        $totalLessons = $course->modules->sum(function ($module) {
            return $module->lessons->count();
        });

        // Count completed lessons for this specific course
        $completedLessons = 0;
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                 if ($lesson->users()->where('user_id', $user->id)->whereNotNull('finished_at')->exists()) {
                     $completedLessons++;
                 }
            }
        }

        if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
            // Generate Certificate if not exists
            if (!\App\Models\Certificate::where('user_id', $user->id)->where('course_id', $courseId)->exists()) {
                \App\Models\Certificate::create([
                    'user_id' => $user->id,
                    'course_id' => $courseId,
                    'code' => strtoupper(\Illuminate\Support\Str::random(10)),
                    'issued_at' => now(),
                ]);
            }
        }
    }

    public function downloadCertificate($code)
    {
        $certificate = \App\Models\Certificate::with(['user', 'course'])->where('code', $code)->firstOrFail();
        
        // Check if user is the owner or admin
        if (auth()->id() !== $certificate->user_id && auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('academy.certificate_pdf', compact('certificate'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('Certificado-Vivensi-' . $certificate->code . '.pdf');
    }
}
