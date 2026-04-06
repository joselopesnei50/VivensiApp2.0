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

        $data = $request->except('thumbnail');
        $data['slug'] = Str::slug($request->title);
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

        $data = $request->except('thumbnail');
        $data['slug'] = Str::slug($request->title);
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
        $course->delete();
        return redirect()->route('admin.academy.index')->with('success', 'Curso excluído!');
    }
}
