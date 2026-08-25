<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrunoLesson;
use Illuminate\Http\Request;

class BrunoLessonController extends Controller
{
    public function index()
    {
        $lessons = BrunoLesson::orderByDesc('active')
            ->orderByDesc('updated_at')
            ->paginate(20);
        return view('admin.bruno.lessons.index', compact('lessons'));
    }

    public function create()
    {
        return view('admin.bruno.lessons.form', [
            'lesson'         => null,
            'suggestedTags'  => BrunoLesson::SUGGESTED_TAGS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        BrunoLesson::create($data);
        return redirect()->route('admin.bruno.lessons.index')
            ->with('success', 'Licao criada — Bruno ja pode aprender com ela.');
    }

    public function edit(BrunoLesson $lesson)
    {
        return view('admin.bruno.lessons.form', [
            'lesson'        => $lesson,
            'suggestedTags' => BrunoLesson::SUGGESTED_TAGS,
        ]);
    }

    public function update(Request $request, BrunoLesson $lesson)
    {
        $lesson->update($this->validated($request));
        return redirect()->route('admin.bruno.lessons.index')
            ->with('success', 'Licao atualizada.');
    }

    public function destroy(BrunoLesson $lesson)
    {
        $lesson->delete();
        return redirect()->route('admin.bruno.lessons.index')
            ->with('success', 'Licao removida.');
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'tenant_id'     => 'nullable|integer|exists:tenants,id',
            'title'         => 'required|string|max:200',
            'tags'          => 'nullable|string|max:1000',
            'situation'     => 'required|string|max:2000',
            'lead_said'     => 'nullable|string|max:2000',
            'bruno_replied' => 'required|string|max:4000',
            'notes'         => 'nullable|string|max:2000',
            'active'        => 'nullable|boolean',
        ]);

        // tags vem como string separada por virgula — normaliza (trim, lowercase, dedup)
        $rawTags = trim((string) ($data['tags'] ?? ''));
        if ($rawTags === '') {
            $data['tags'] = [];
        } else {
            $tags = array_filter(array_map(
                fn ($t) => trim(mb_strtolower($t)),
                explode(',', $rawTags)
            ));
            $data['tags'] = array_values(array_unique($tags));
        }

        $data['active'] = (bool) ($data['active'] ?? true);

        return $data;
    }
}
