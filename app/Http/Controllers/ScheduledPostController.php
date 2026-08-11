<?php

namespace App\Http\Controllers;

use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Services\DeepSeekService;
use App\Services\MetaSocialPublisherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScheduledPostController extends Controller
{
    public function index(Request $request)
    {
        $filter = in_array($request->query('status'), ['scheduled', 'published', 'failed', 'draft'], true)
            ? $request->query('status') : null;

        $posts = ScheduledPost::with(['account', 'metrics'])
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->orderByDesc('scheduled_at')
            ->paginate(12)
            ->withQueryString();

        $accounts = SocialAccount::where('is_active', true)->get();

        // Contadores por status pros filtros no topo — usa withoutGlobalScopes
        // não; queremos respeitar tenant (BelongsToTenant já filtra automático).
        $counts = ScheduledPost::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $counts['all'] = array_sum($counts);

        return view('social.posts.index', compact('posts', 'accounts', 'counts', 'filter'));
    }

    public function create()
    {
        $accounts = SocialAccount::where('is_active', true)->get();
        return view('social.posts.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        // app.timezone = America/Sao_Paulo, então gravamos em BRT direto.
        // Antes convertia pra UTC (->utc()) mas na leitura o Carbon interpretava
        // a string UTC como BRT — sumiam 3h no display e o cron posts:publish
        // publicava 3h atrasado. Fix 2026-08-11.
        $publishNow = $request->boolean('publish_now');

        if (!$publishNow && $request->filled('scheduled_at')) {
            try {
                $request->merge([
                    'scheduled_at' => Carbon::parse($request->input('scheduled_at'), 'America/Sao_Paulo')
                        ->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // Deixa o validator abaixo pegar o formato inválido
            }
        }

        $data = $request->validate([
            'social_account_id' => 'nullable|integer',
            'platform'          => 'required|in:facebook,instagram,both',
            'format'            => 'nullable|in:feed,story',
            'caption'           => 'required|string|max:2200',
            'scheduled_at'      => $publishNow ? 'nullable|date' : 'required|date|after:now',
            // Legado — 1 arquivo único (compat com uploads antigos e integrações
            // que não sabem enviar múltiplos).
            'media'             => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:51200',
            'media_type'        => 'nullable|in:none,image,video',
            'media_url_external'=> 'nullable|url|max:2048',
            // Novo — array de arquivos pra carrossel (até 10 pela regra Meta).
            'media_files'       => 'nullable|array|max:10',
            'media_files.*'     => 'file|mimes:jpg,jpeg,png,gif,mp4|max:51200',
            'publish_now'       => 'nullable|boolean',
        ]);

        // Story só publica no Instagram — Meta praticamente fechou FB Stories
        // via API. Ajusta silenciosamente pra não confundir o publisher.
        $format = $data['format'] ?? 'feed';
        if ($format === 'story' && $data['platform'] === 'facebook') {
            $data['platform'] = 'instagram';
        }

        // Conta é opcional — sem conta o post fica como rascunho
        $accountId = $data['social_account_id'] ?? null;
        $account   = $accountId ? SocialAccount::findOrFail($accountId) : null;

        $mediaUrl   = null;
        $mediaType  = 'none';
        $mediaItems = null;
        $tenantId   = auth()->user()->tenant_id;

        // Regra de precedência (do mais rico ao mais simples):
        // 1) media_files[] (múltiplas mídias — pode virar carrossel)
        // 2) media (arquivo único legado)
        // 3) media_url_external (URL pronta — usado pelo Marketing IA)
        if ($request->hasFile('media_files')) {
            $items = [];
            foreach ($request->file('media_files') as $file) {
                if (!$file || !$file->isValid()) continue;
                $path = $file->store("tenants/{$tenantId}/social-media", 'public');
                $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
                $items[] = [
                    'url'  => url(Storage::url($path)),
                    'type' => $type,
                ];
            }
            if (!empty($items)) {
                $mediaItems = $items;
                // Primeira mídia também vira media_url pra fallback (Facebook
                // hoje só posta 1 foto — publisher pega a primeira do carrossel).
                $mediaUrl  = $items[0]['url'];
                $mediaType = $items[0]['type'];
            }
        } elseif ($request->hasFile('media')) {
            $path = $request->file('media')->store("tenants/{$tenantId}/social-media", 'public');
            $mediaUrl  = url(Storage::url($path));
            $mediaType = str_starts_with($request->file('media')->getMimeType(), 'video') ? 'video' : 'image';
        } elseif (!empty($data['media_url_external'])) {
            $mediaUrl  = $data['media_url_external'];
            $mediaType = 'image';
        }

        // Se "publicar agora" marcado, scheduled_at vira now() (BRT) pro publisher
        // pegar na próxima passagem do cron posts:publish.
        $scheduledAt = $publishNow ? now() : $data['scheduled_at'];

        $post = ScheduledPost::create([
            'tenant_id'         => $tenantId,
            'social_account_id' => $account?->id,
            'user_id'           => auth()->id(),
            'platform'          => $data['platform'],
            'format'            => $format,
            'caption'           => $data['caption'],
            'media_url'         => $mediaUrl,
            'media_items'       => $mediaItems,
            'media_type'        => $mediaType,
            'scheduled_at'      => $scheduledAt,
            'status'            => $account ? 'scheduled' : 'draft',
        ]);

        // Publica imediatamente se "publicar agora" + conta vinculada.
        if ($publishNow && $account) {
            try {
                $ok = app(MetaSocialPublisherService::class)->publish($post);
                if ($ok) {
                    return redirect()->route('social.posts.index')
                        ->with('success', 'Publicado com sucesso no Facebook/Instagram!');
                }
                // Falha: publisher já gravou o motivo real em error_message
                $post->refresh();
                return redirect()->route('social.posts.index')
                    ->with('error', 'Falha ao publicar: ' . ($post->error_message ?: 'motivo não registrado.'));
            } catch (\Throwable $e) {
                Log::error('ScheduledPost publish_now failed', [
                    'post_id' => $post->id,
                    'error'   => $e->getMessage(),
                ]);
                return redirect()->route('social.posts.index')
                    ->with('error', 'Erro ao publicar: ' . $e->getMessage());
            }
        }

        return redirect()->route('social.posts.index')
            ->with('success', 'Post agendado com sucesso!');
    }

    public function edit(ScheduledPost $post)
    {
        abort_if($post->status !== 'scheduled', 403, 'Apenas posts agendados podem ser editados.');
        $accounts = SocialAccount::where('is_active', true)->get();
        return view('social.posts.edit', compact('post', 'accounts'));
    }

    public function update(Request $request, ScheduledPost $post)
    {
        abort_if($post->status !== 'scheduled', 403, 'Apenas posts agendados podem ser editados.');

        // Grava BRT direto (app.timezone = America/Sao_Paulo). Ver comentário no store().
        if ($request->filled('scheduled_at')) {
            try {
                $request->merge([
                    'scheduled_at' => Carbon::parse($request->input('scheduled_at'), 'America/Sao_Paulo')
                        ->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // Deixa o validator abaixo pegar
            }
        }

        $data = $request->validate([
            'platform'     => 'required|in:facebook,instagram,both',
            'caption'      => 'required|string|max:2200',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $post->update($data);

        return redirect()->route('social.posts.index')->with('success', 'Post atualizado com sucesso!');
    }

    public function destroy(ScheduledPost $post)
    {
        Gate::authorize('delete', $post);
        abort_if($post->status === 'published', 403, 'Não é possível excluir um post já publicado.');
        $post->delete();
        return back()->with('success', 'Post removido.');
    }

    /** API: retorna posts para o calendário (JSON) */
    public function calendar()
    {
        $posts = ScheduledPost::with(['account', 'metrics'])
            ->whereIn('status', ['draft', 'scheduled', 'published', 'failed'])
            ->get()
            ->map(function ($p) {
                $fbUrl = ($p->status === 'published' && $p->facebook_post_id && $p->account)
                    ? 'https://www.facebook.com/' . $p->facebook_post_id
                    : null;
                $igUrl = ($p->status === 'published' && $p->instagram_post_id)
                    ? 'https://www.instagram.com/p/' . $p->instagram_post_id . '/'
                    : null;

                $metrics = null;
                if ($p->status === 'published' && $p->metrics->isNotEmpty()) {
                    $metrics = $p->metricsTotal();
                }

                return [
                    'id'    => $p->id,
                    'title' => \Illuminate\Support\Str::limit($p->caption, 40),
                    'start' => $p->scheduled_at->toIso8601String(),
                    'color' => match ($p->status) {
                        'published' => '#10b981',
                        'failed'    => '#ef4444',
                        'draft'     => '#f59e0b',
                        default     => '#4f46e5',
                    },
                    'extendedProps' => [
                        'status'         => $p->status,
                        'platform'       => $p->platform,
                        'account'        => $p->account?->page_name,
                        'caption'        => $p->caption,
                        'media_url'      => $p->media_url,
                        'media_type'     => $p->media_type,
                        'error_message'  => $p->error_message,
                        'edit_url'       => $p->status === 'scheduled'
                            ? route('social.posts.edit', $p->id) : null,
                        'facebook_url'   => $fbUrl,
                        'instagram_url'  => $igUrl,
                        'metrics'        => $metrics, // null se ainda não coletado
                    ],
                ];
            });

        return response()->json($posts);
    }

    /** API: gera legenda com Gemini */
    public function generateCaption(Request $request)
    {
        $request->validate([
            'topic'    => 'required|string|max:300',
            'platform' => 'required|in:facebook,instagram,both',
        ]);

        $ds = new DeepSeekService();

        $tenantName = auth()->user()->tenant?->name ?? 'nossa organização';
        $platform   = $request->platform;
        $topic      = $request->topic;

        $prompt = "Você é um especialista em marketing digital para o terceiro setor. "
            . "Crie uma legenda para {$platform} sobre o tema: \"{$topic}\". "
            . "A legenda é para a organização \"{$tenantName}\". "
            . "Seja engajador, use emojis moderadamente, inclua uma chamada para ação e hashtags relevantes. "
            . "Máximo de 300 palavras. Retorne apenas o texto da legenda, sem explicações.";

        try {
            $result = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $text   = $result['choices'][0]['message']['content'] ?? null;
            if (!$text) {
                return response()->json(['error' => 'Não foi possível gerar legenda. Verifique a chave DeepSeek no painel admin.'], 422);
            }
            return response()->json(['caption' => trim($text)]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha ao gerar legenda. Tente novamente.'], 500);
        }
    }
}
