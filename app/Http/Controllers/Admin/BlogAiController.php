<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Blog\BlogPostGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogAiController extends Controller
{
    public function __construct(private BlogPostGeneratorService $generator) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'theme'        => ['nullable', 'string', 'max:120'],
            'custom_theme' => ['nullable', 'string', 'max:255'],
        ]);

        $theme = trim($data['custom_theme'] ?? '') !== ''
            ? trim($data['custom_theme'])
            : trim((string) ($data['theme'] ?? ''));

        if ($theme === '') {
            return back()
                ->withErrors(['theme' => 'Escolha um tema curado ou digite um tema livre.'])
                ->withInput();
        }

        try {
            $draft = $this->generator->generateDraft($theme);
        } catch (\Throwable $e) {
            Log::error('BlogAi: geração falhou', [
                'theme' => $theme,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Não foi possível gerar o post: ' . $e->getMessage());
        }

        $post = Post::create([
            'title'            => $draft['title'],
            'excerpt'          => $draft['excerpt'],
            'content'          => sanitize_user_html($draft['content']),
            'meta_description' => $draft['meta_description'],
            'tags'             => $draft['tags'],
            'slug'             => $this->uniqueSlug(Str::slug($draft['title'])),
            'is_published'     => false,
            'published_at'     => null,
        ]);

        return redirect()
            ->route('admin.blog.edit', $post->id)
            ->with('success', 'Rascunho gerado com IA. Revise antes de publicar.');
    }

    private function uniqueSlug(string $base): string
    {
        $slug    = $base !== '' ? $base : 'post-' . uniqid();
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$counter);
        }
        return $slug;
    }
}
