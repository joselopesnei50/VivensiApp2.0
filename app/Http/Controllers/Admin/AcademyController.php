<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AcademyController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $courses = Course::latest()->paginate(10);
        return view('admin.academy.index', compact('courses'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        return view('admin.academy.create');
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable',
            'thumbnail' => 'nullable|image|max:2048', // 2MB
            'teacher_name' => 'nullable|max:255',
        ]);

        $data = $request->only(['title', 'description', 'teacher_name']);
        // Endereço único: dois cursos com o mesmo título davam erro 500 (slug repetido).
        $data['slug'] = $this->uniqueSlug($request->title);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('courses', 'public');
            $data['thumbnail_url'] = Storage::url($path);
        }

        Course::create($data);

        return redirect()->route('admin.academy.index')->with('success', 'Curso criado com sucesso!');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit($id)
    {
        $course = Course::findOrFail($id);
        return view('admin.academy.edit', compact('course'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable',
            'thumbnail' => 'nullable|image|max:2048',
            'teacher_name' => 'nullable|max:255',
        ]);

        // O endereço do curso (slug) não muda ao editar o título: links já divulgados continuam valendo.
        $data = $request->only(['title', 'description', 'teacher_name']);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('thumbnail')) {
            // Delete old
            if ($course->thumbnail_url) {
                // Logic to delete old file if needed
            }
            $path = $request->file('thumbnail')->store('courses', 'public');
            $data['thumbnail_url'] = Storage::url($path);
        }

        $course->update($data);

        return redirect()->route('admin.academy.index')->with('success', 'Curso atualizado!');
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Sem chave estrangeira no banco: apaga módulos, aulas e progresso junto.
        // Os certificados emitidos ficam (guardam o nome do curso e continuam verificáveis).
        \Illuminate\Support\Facades\DB::transaction(function () use ($course) {
            $moduleIds = Module::where('course_id', $course->id)->pluck('id');
            $lessonIds = Lesson::whereIn('module_id', $moduleIds)->pluck('id');
            \Illuminate\Support\Facades\DB::table('lesson_user')->whereIn('lesson_id', $lessonIds)->delete();
            Lesson::whereIn('id', $lessonIds)->delete();
            Module::whereIn('id', $moduleIds)->delete();
            $course->delete();
        });
        return redirect()->route('admin.academy.index')->with('success', 'Curso excluído!');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'curso';
        $slug = $base;
        for ($n = 2; Course::where('slug', $slug)->exists(); $n++) {
            $slug = $base . '-' . $n;
        }
        return $slug;
    }
}
