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
    public function index()
    {
        $posts    = ScheduledPost::with('account')->orderByDesc('scheduled_at')->paginate(20);
        $accounts = SocialAccount::where('is_active', true)->get();
        return view('social.posts.index', compact('posts', 'accounts'));
    }

    public function create()
    {
        $accounts = SocialAccount::where('is_active', true)->get();
        return view('social.posts.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        // Modo "publicar agora": pula validação de data futura e usa now() UTC.
        // Caso contrário: converte scheduled_at do timezone do usuário (Brasília)
        // pra UTC ANTES de validar — sem isso, "00:55 BRT" chega como "00:55 UTC"
        // que é passado pra quem está no Brasil e o validator 'after:now' reprova.
        $publishNow = $request->boolean('publish_now');

        if (!$publishNow && $request->filled('scheduled_at')) {
            try {
                $request->merge([
                    'scheduled_at' => Carbon::parse($request->input('scheduled_at'), 'America/Sao_Paulo')
                        ->utc()
                        ->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // Deixa o validator abaixo pegar o formato inválido
            }
        }

        $data = $request->validate([
            'social_account_id' => 'nullable|integer',
            'platform'          => 'required|in:facebook,instagram,both',
            'caption'           => 'required|string|max:2200',
            'scheduled_at'      => $publishNow ? 'nullable|date' : 'required|date|after:now',
            'media'             => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:51200',
            'media_type'        => 'nullable|in:none,image,video',
            'media_url_external'=> 'nullable|url|max:2048',
            'publish_now'       => 'nullable|boolean',
        ]);

        // Conta é opcional — sem conta o post fica como rascunho
        $account = $data['social_account_id']
            ? SocialAccount::findOrFail($data['social_account_id'])
            : null;

        $mediaUrl  = null;
        $mediaType = 'none';

        if ($request->hasFile('media')) {
            $tenantId = auth()->user()->tenant_id;
            $path     = $request->file('media')->store("tenants/{$tenantId}/social-media", 'public');
            // IMPORTANTE: Meta exige URL absoluta pública pra baixar a mídia.
            // Storage::url() retorna caminho relativo (/storage/...) — url()
            // prefixa com APP_URL (https://vivensi.app.br).
            $mediaUrl = url(Storage::url($path));
            $mediaType = str_starts_with($request->file('media')->getMimeType(), 'video') ? 'video' : 'image';
        } elseif (!empty($data['media_url_external'])) {
            // URL externa (ex: Unsplash) — usada quando gerado pelo Marketing Intelligence
            $mediaUrl  = $data['media_url_external'];
            $mediaType = 'image';
        }

        // Se "publicar agora" marcado, scheduled_at vira now() UTC pro publisher
        // pegar imediatamente. Status também.
        $scheduledAt = $publishNow ? now()->utc() : $data['scheduled_at'];

        $post = ScheduledPost::create([
            'tenant_id'         => auth()->user()->tenant_id,
            'social_account_id' => $account?->id,
            'user_id'           => auth()->id(),
            'platform'          => $data['platform'],
            'caption'           => $data['caption'],
            'media_url'         => $mediaUrl,
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

        // Mesma conversão BRT → UTC do store (timezone-safe).
        if ($request->filled('scheduled_at')) {
            try {
                $request->merge([
                    'scheduled_at' => Carbon::parse($request->input('scheduled_at'), 'America/Sao_Paulo')
                        ->utc()
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
        $posts = ScheduledPost::with('account')
            ->whereIn('status', ['draft', 'scheduled', 'published'])
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'title' => substr($p->caption, 0, 40) . '...',
                'start' => $p->scheduled_at->toIso8601String(),
                'color' => match($p->status) {
                    'published' => '#10b981',
                    'failed'    => '#ef4444',
                    'draft'     => '#f59e0b',
                    default     => '#4f6ef7',
                },
                'extendedProps' => [
                    'status'   => $p->status,
                    'platform' => $p->platform,
                    'account'  => $p->account?->page_name,
                ],
            ]);

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
