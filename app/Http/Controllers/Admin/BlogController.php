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

        // Analytics: visitas de pessoas por post (robôs ficam de fora)
        $viewsByPost = collect(app(\App\Services\BlogMetricsService::class)->humanViewsByPost());

        $totalViews  = $viewsByPost->sum();
        $viewsMonth  = DB::table('post_views')
            ->where('is_bot', false)
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

    /** Painel de métricas do blog (todos os posts). */
    public function metrics(Request $request, \App\Services\BlogMetricsService $metrics)
    {
        $m = $metrics->summary((int) $request->query('dias', 30));

        return view('admin.blog.metrics', ['m' => $m, 'post' => null, 'periods' => \App\Services\BlogMetricsService::PERIODS]);
    }

    /** Métricas de um post. */
    public function postMetrics(Request $request, Post $post, \App\Services\BlogMetricsService $metrics)
    {
        $m = $metrics->summary((int) $request->query('dias', 30), $post->id);

        return view('admin.blog.metrics', ['m' => $m, 'post' => $post, 'periods' => \App\Services\BlogMetricsService::PERIODS]);
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
        // Defense-in-depth: HTMLPurifier no store, mesmo o render publico ja usar
        // sanitize_user_html. Bloqueia script/iframe/handlers antes de gravar.
        $data['content']      = sanitize_user_html($data['content'] ?? '');
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
        $data['content']      = sanitize_user_html($data['content'] ?? '');
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
