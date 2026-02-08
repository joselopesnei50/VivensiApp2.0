<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademyModuleController extends Controller
{
    public function index($courseId)
    {
        $course = Course::with(['modules.lessons'])->findOrFail($courseId);
        return view('admin.academy.modules', compact('course'));
    }

    public function storeModule(Request $request, $courseId)
    {
        $request->validate([
            'title' => 'required|max:255',
            'order' => 'integer',
        ]);

        Module::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'order' => $request->order ?? 0,
        ]);

        return back()->with('success', 'Módulo criado com sucesso!');
    }

    public function updateModule(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $module->update($request->only(['title', 'order']));
        return back()->with('success', 'Módulo atualizado!');
    }

    public function destroyModule($id)
    {
        $module = Module::findOrFail($id);
        $module->delete();
        return back()->with('success', 'Módulo removido!');
    }

    public function storeLesson(Request $request, $moduleId)
    {
        $request->validate([
            'title' => 'required|max:255',
            'video_url' => 'nullable|url',
            'duration_minutes' => 'integer',
            'type' => 'required|in:video,ebook',
            'order' => 'integer',
        ]);

        Lesson::create([
            'module_id' => $moduleId,
            'title' => $request->title,
            'video_url' => $request->video_url,
            'document_url' => $request->document_url, // Handle upload later if needed
            'duration_minutes' => $request->duration_minutes ?? 0,
            'type' => $request->type,
            'order' => $request->order ?? 0,
        ]);

        return back()->with('success', 'Aula criada com sucesso!');
    }

    public function destroyLesson($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();
        return back()->with('success', 'Aula removida!');
    }
}
