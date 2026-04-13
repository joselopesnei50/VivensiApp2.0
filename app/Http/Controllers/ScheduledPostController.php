<?php

namespace App\Http\Controllers;

use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        $data = $request->validate([
            'social_account_id' => 'required|integer',
            'platform'          => 'required|in:facebook,instagram,both',
            'caption'           => 'required|string|max:2200',
            'scheduled_at'      => 'required|date|after:now',
            'media'             => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:51200',
            'media_type'        => 'nullable|in:none,image,video',
            'media_url_external'=> 'nullable|url|max:2048',
        ]);

        // Garante que a conta pertence ao tenant
        $account = SocialAccount::findOrFail($data['social_account_id']);
        abort_unless($account->tenant_id === auth()->user()->tenant_id, 403);

        $mediaUrl  = null;
        $mediaType = 'none';

        if ($request->hasFile('media')) {
            $path     = $request->file('media')->store('social-media', 'public');
            $mediaUrl = Storage::url($path);
            $mediaType = str_starts_with($request->file('media')->getMimeType(), 'video') ? 'video' : 'image';
        } elseif (!empty($data['media_url_external'])) {
            // URL externa (ex: Unsplash) — usada quando gerado pelo Marketing Intelligence
            $mediaUrl  = $data['media_url_external'];
            $mediaType = 'image';
        }

        ScheduledPost::create([
            'tenant_id'         => auth()->user()->tenant_id,
            'social_account_id' => $account->id,
            'user_id'           => auth()->id(),
            'platform'          => $data['platform'],
            'caption'           => $data['caption'],
            'media_url'         => $mediaUrl,
            'media_type'        => $mediaType,
            'scheduled_at'      => $data['scheduled_at'],
            'status'            => 'scheduled',
        ]);

        return redirect()->route('social.posts.index')
            ->with('success', 'Post agendado com sucesso!');
    }

    public function destroy(ScheduledPost $post)
    {
        abort_unless($post->tenant_id === auth()->user()->tenant_id, 403);
        abort_if($post->status === 'published', 403, 'Não é possível excluir um post já publicado.');
        $post->delete();
        return back()->with('success', 'Post removido.');
    }

    /** API: retorna posts para o calendário (JSON) */
    public function calendar()
    {
        $posts = ScheduledPost::with('account')
            ->whereIn('status', ['scheduled', 'published'])
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'title' => substr($p->caption, 0, 40) . '...',
                'start' => $p->scheduled_at->toIso8601String(),
                'color' => match($p->status) {
                    'published' => '#10b981',
                    'failed'    => '#ef4444',
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

    /** API: gera legenda com DeepSeek */
    public function generateCaption(Request $request)
    {
        $request->validate([
            'topic'   => 'required|string|max:300',
            'platform' => 'required|in:facebook,instagram,both',
        ]);

        $apiKey = SystemSetting::getValue('deepseek_api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'DeepSeek não configurado.'], 422);
        }

        $tenantName = auth()->user()->tenant?->name ?? 'nossa organização';
        $platform   = $request->platform;
        $topic      = $request->topic;

        $prompt = "Você é um especialista em marketing digital para o terceiro setor e gestão de projetos. "
            . "Crie uma legenda para {$platform} sobre o seguinte tema: \"{$topic}\". "
            . "A legenda é para a organização \"{$tenantName}\". "
            . "Seja engajador, use emojis moderadamente, inclua uma chamada para ação e hashtags relevantes. "
            . "Máximo de 300 palavras.";

        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.deepseek.com/v1/chat/completions', [
                'model'    => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um especialista em copywriting para redes sociais.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens' => 500,
            ]);

            $caption = $response->json('choices.0.message.content');
            return response()->json(['caption' => trim($caption)]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha ao gerar legenda. Tente novamente.'], 500);
        }
    }
}
