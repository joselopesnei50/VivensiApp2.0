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
        $data = $request->validate([
            'title' => 'required|max:255',
            'order' => 'nullable|integer|min:0',
        ]);
        $module->update(['title' => $data['title'], 'order' => $data['order'] ?? $module->order]);
        return back()->with('success', 'Módulo atualizado!');
    }

    public function destroyModule($id)
    {
        $module = Module::findOrFail($id);
        \Illuminate\Support\Facades\DB::transaction(function () use ($module) {
            $lessonIds = Lesson::where('module_id', $module->id)->pluck('id');
            \Illuminate\Support\Facades\DB::table('lesson_user')->whereIn('lesson_id', $lessonIds)->delete();
            Lesson::whereIn('id', $lessonIds)->delete();
            $module->delete();
        });
        return back()->with('success', 'Módulo removido!');
    }

    public function storeLesson(Request $request, $moduleId)
    {
        $module = Module::findOrFail($moduleId);
        $data = $this->validateLesson($request, true);

        Lesson::create([
            'module_id'        => $module->id,
            'title'            => $data['title'],
            'video_url'        => $data['type'] === 'video' ? ($data['video_url'] ?? null) : null,
            'document_url'     => $data['type'] === 'ebook' ? $this->storeDocument($request) : null,
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'type'             => $data['type'],
            'order'            => $data['order'] ?? 0,
        ]);

        return back()->with('success', 'Aula criada com sucesso!');
    }

    /** Editar aula: título, tipo, link do vídeo, PDF (opcional trocar), duração e ordem. */
    public function updateLesson(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $data = $this->validateLesson($request, false, $lesson);

        $lesson->update([
            'title'            => $data['title'],
            'type'             => $data['type'],
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'order'            => $data['order'] ?? $lesson->order,
            'video_url'        => $data['type'] === 'video' ? ($data['video_url'] ?? null) : null,
            'document_url'     => $data['type'] === 'ebook'
                ? ($request->hasFile('document') ? $this->storeDocument($request) : $lesson->document_url)
                : null,
        ]);

        return back()->with('success', 'Aula atualizada!');
    }

    private function validateLesson(Request $request, bool $creating, ?Lesson $lesson = null): array
    {
        $needsDoc = $request->input('type') === 'ebook' && ($creating || !$lesson?->document_url);

        return $request->validate([
            'title'            => 'required|max:255',
            'type'             => 'required|in:video,ebook',
            'video_url'        => ['nullable', 'url', 'max:500', 'required_if:type,video', function ($attr, $value, $fail) {
                if ($value && !Lesson::toEmbedUrl($value)) {
                    $fail('Use um link do YouTube ou do Vimeo.');
                }
            }],
            'duration_minutes' => 'nullable|integer|min:0|max:6000',
            'order'            => 'nullable|integer|min:0',
            'document'         => [$needsDoc ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:51200'],
        ], [
            'video_url.required_if' => 'Informe o link do vídeo.',
            'document.required'     => 'Anexe o PDF do material.',
        ]);
    }

    private function storeDocument(Request $request): ?string
    {
        if (!$request->hasFile('document')) {
            return null;
        }
        $file = $request->file('document');
        // Nome sem espaços/acentos (o nome original do arquivo quebrava o link).
        $name = time() . '_' . (\Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'material') . '.pdf';
        return Storage::url($file->storeAs('academy/documents', $name, 'public'));
    }

    public function destroyLesson($id)
    {
        $lesson = Lesson::findOrFail($id);
        \Illuminate\Support\Facades\DB::table('lesson_user')->where('lesson_id', $lesson->id)->delete();
        $lesson->delete();
        return back()->with('success', 'Aula removida!');
    }
}
