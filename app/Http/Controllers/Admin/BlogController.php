<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts          = Post::orderBy('created_at', 'desc')->paginate(15);
        $totalCount     = Post::count();
        $publishedCount = Post::where('is_published', true)->count();
        $draftCount     = $totalCount - $publishedCount;

        // Analytics: views por post (1 query com GROUP BY)
        $viewsByPost = DB::table('post_views')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $totalViews  = $viewsByPost->sum();
        $viewsMonth  = DB::table('post_views')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Injetar view_count em cada post
        foreach ($posts as $post) {
            $post->view_count = (int) ($viewsByPost[$post->id] ?? 0);
        }

        return view('admin.blog.index', compact(
            'posts', 'totalCount', 'publishedCount', 'draftCount',
            'totalViews', 'viewsMonth'
        ));
    }

    public function create()
    {
        return view('admin.blog.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|max:255',
            'content' => 'required',
            'image'   => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['title', 'content', 'excerpt', 'meta_description', 'tags']);
        $data['is_published'] = $request->boolean('is_published');
        $data['slug']         = $this->uniqueSlug(Str::slug($request->title));
        $data['published_at'] = $data['is_published'] ? now() : null;

        if ($request->hasFile('image')) {
            $path         = $request->file('image')->store('blog', 'public');
            $data['image'] = '/storage/' . $path;
        }

        Post::create($data);

        return redirect()->route('admin.blog.index')->with('success', 'Post criado com sucesso!');
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        return view('admin.blog.edit', compact('post'));
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title'   => 'required|max:255',
            'content' => 'required',
            'image'   => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['title', 'content', 'excerpt', 'meta_description', 'tags']);
        $data['is_published'] = $request->boolean('is_published');

        $newSlug = Str::slug($request->title);
        $data['slug'] = ($newSlug !== $post->slug)
            ? $this->uniqueSlug($newSlug, $post->id)
            : $post->slug;

        if ($data['is_published'] && !$post->is_published) {
            $data['published_at'] = now();
        } else {
            $data['published_at'] = $post->published_at;
        }

        if ($request->hasFile('image')) {
            $path          = $request->file('image')->store('blog', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $post->update($data);

        return redirect()->route('admin.blog.index')->with('success', 'Post atualizado com sucesso!');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', 'Post excluído com sucesso!');
    }

    private function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug  = $base;
        $count = 1;

        while (true) {
            $query = Post::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (!$query->exists()) {
                return $slug;
            }
            $slug = $base . '-' . $count;
            $count++;
        }
    }
}
