<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Blog\BlogPostGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateBlogDraft extends Command
{
    protected $signature = 'blog:generate-draft
                            {--theme= : Chave de tema curado (ex: lgpd-terceiro-setor). Sorteio aleatorio quando omitido.}
                            {--dry-run : Chama a IA mas nao grava o Post}';

    protected $description = 'Gera rascunho de post do blog via IA. Escolhe tema curado nao usado nos ultimos 60 dias.';

    public function handle(BlogPostGeneratorService $generator): int
    {
        $themeKey = $this->option('theme');
        $themes   = BlogPostGeneratorService::themeChoices();

        if ($themeKey) {
            if (!isset($themes[$themeKey])) {
                $this->error("Tema '{$themeKey}' nao existe. Use um destes:");
                foreach (array_keys($themes) as $k) {
                    $this->line("  - {$k}");
                }
                return self::FAILURE;
            }
        } else {
            $themeKey = $this->pickFreshTheme($themes);
            if ($themeKey === null) {
                $this->warn('Todos os temas curados foram usados nos ultimos 60 dias. Aguarde ou passe --theme=...');
                return self::SUCCESS;
            }
        }

        $themeLabel = $themes[$themeKey];
        $this->info("Gerando rascunho para tema: {$themeLabel}");

        try {
            $draft = $generator->generateDraft($themeKey);
        } catch (\Throwable $e) {
            $this->error('IA falhou: ' . $e->getMessage());
            Log::error('blog:generate-draft falhou', ['theme' => $themeKey, 'error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line("--- DRY RUN ---");
            $this->line("Titulo: {$draft['title']}");
            $this->line("Excerpt: {$draft['excerpt']}");
            $this->line("Tags: {$draft['tags']}");
            $this->line('Content length: ' . mb_strlen($draft['content']));
            return self::SUCCESS;
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

        $this->info("Post rascunho #{$post->id} criado: /admin/blog/{$post->id}/edit");
        Log::info('blog:generate-draft criou rascunho', ['post_id' => $post->id, 'theme' => $themeKey]);

        return self::SUCCESS;
    }

    /**
     * Sorteia tema que nao aparece em rascunho/post publicado dos ultimos 60 dias.
     * Compara pelo slug gerado a partir do label — heuristica leve, evita repetir
     * exatamente o mesmo tema em ondas consecutivas do scheduler.
     */
    private function pickFreshTheme(array $themes): ?string
    {
        $recentTitles = Post::where('created_at', '>=', now()->subDays(60))
            ->pluck('title')
            ->map(fn($t) => Str::slug($t))
            ->all();

        $fresh = [];
        foreach ($themes as $key => $label) {
            $labelSlug = Str::slug($label);
            $collision = false;
            foreach ($recentTitles as $t) {
                if (str_contains($t, Str::slug($key)) || str_contains($labelSlug, $t) || str_contains($t, $labelSlug)) {
                    $collision = true;
                    break;
                }
            }
            if (!$collision) {
                $fresh[] = $key;
            }
        }

        if (empty($fresh)) {
            return null;
        }

        return $fresh[array_rand($fresh)];
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
