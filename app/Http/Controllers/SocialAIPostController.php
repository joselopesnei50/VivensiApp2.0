<?php

namespace App\Http\Controllers;

use App\Models\AiSocialPost;
use App\Models\AiImageUsageLog;
use App\Models\SocialAccount;
use App\Models\ScheduledPost;
use App\Jobs\GenerateSocialPostJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SocialAIPostController extends Controller
{
    public function index()
    {
        Gate::authorize('access-social-ai');

        $userId   = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $posts = AiSocialPost::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $quotaUsed = AiImageUsageLog::getCurrentUsage($userId);

        return view('social_ai.index', compact('posts', 'quotaUsed'));
    }

    public function about()
    {
        Gate::authorize('access-social-ai');
        return view('social_ai.about');
    }

    public function generate(Request $request)
    {
        Gate::authorize('access-social-ai');

        $request->validate([
            'theme'                  => 'required|string|max:500',
            'user_context'           => 'nullable|string|max:1000',
            'format'                 => 'nullable|in:square,story',
            'visual_style'           => 'nullable|in:photorealistic,illustration,cartoon,corporate,minimalist,watercolor',
            'use_reference_as_final' => 'nullable|boolean',
            'reference_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB
        ]);

        $userId   = auth()->id();
        $tenantId = auth()->user()->tenant_id;
        $useReferenceAsFinal = (bool) $request->boolean('use_reference_as_final');

        // Cota só é consumida se FLUX.1 vai gerar imagem (referência final não gasta)
        if (!$useReferenceAsFinal) {
            $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
            if ($currentUsage >= 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite mensal de 60 imagens atingido. Sua cota renova no próximo mês.',
                ], 422);
            }
        }

        // Salva imagem de referência (se houver) no disk público, pasta refs/
        $referencePath = null;
        if ($request->hasFile('reference_image')) {
            $referencePath = $request->file('reference_image')
                ->store('social_ai/refs', 'public');
        }

        // Se o user marcou "usar como imagem final" mas não subiu imagem, ignora a flag
        if ($useReferenceAsFinal && !$referencePath) {
            $useReferenceAsFinal = false;
        }

        $post = AiSocialPost::create([
            'tenant_id'              => $tenantId,
            'user_id'                => $userId,
            'title_theme'            => $request->theme,
            'user_context'           => $request->user_context,
            'format'                 => $request->input('format', 'square'),
            'visual_style'           => $request->input('visual_style'),
            'reference_image_path'   => $referencePath,
            'use_reference_as_final' => $useReferenceAsFinal,
            'status'                 => 'processing',
        ]);

        GenerateSocialPostJob::dispatch($post->id);

        return response()->json([
            'success' => true,
            'message' => 'Geração iniciada! O post aparecerá aqui em instantes.',
            'post_id' => $post->id,
        ]);
    }

    /**
     * Regenera SÓ a legenda (mantém a imagem existente).
     * Chama DeepSeek de novo e substitui body_text + caption_variations.
     */
    public function regenerateCaption(Request $request, $id)
    {
        Gate::authorize('access-social-ai');

        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        try {
            $service = app(\App\Services\SocialAIContentService::class);
            $content = $service->generateContent(
                $post->title_theme,
                $post->user_context,
                $post->visual_style,
                $post->format ?: 'square'
            );

            $post->update([
                'body_text'          => $content['captions'][0],
                'caption_variations' => $content['captions'],
            ]);

            return response()->json([
                'success'    => true,
                'captions'   => $content['captions'],
                'body_text'  => $post->body_text,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Regenera SÓ a imagem (mantém a legenda existente).
     * Consome 1 crédito da cota mensal.
     */
    public function regenerateImage(Request $request, $id)
    {
        Gate::authorize('access-social-ai');

        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        if ($post->use_reference_as_final) {
            return response()->json([
                'success' => false,
                'message' => 'Este post usa uma imagem de referência como final — não há imagem gerada pra regenerar.',
            ], 422);
        }

        $userId = auth()->id();
        $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
        if ($currentUsage >= 60) {
            return response()->json([
                'success' => false,
                'message' => 'Limite mensal de 60 imagens atingido.',
            ], 422);
        }

        try {
            $service = app(\App\Services\SocialAIContentService::class);
            // Reaproveita o mesmo prompt caso ainda esteja em memória — como não guardamos,
            // pedimos ao DeepSeek um NOVO image_prompt (rápido, custo baixo).
            $content = $service->generateContent(
                $post->title_theme,
                $post->user_context,
                $post->visual_style,
                $post->format ?: 'square'
            );

            $imageUrl = $service->generateImage($content['image_prompt'], $post->format ?: 'square');
            $imageContents = \Illuminate\Support\Facades\Http::timeout(60)->get($imageUrl)->body();
            $filename = 'social_ai/' . uniqid() . '.jpg';
            Storage::disk('public')->put($filename, $imageContents);

            // Apaga imagem anterior (não a referência)
            if ($post->image_path && $post->image_path !== $post->reference_image_path
                && Storage::disk('public')->exists($post->image_path)) {
                Storage::disk('public')->delete($post->image_path);
            }

            $post->update([
                'image_path' => $filename,
                'image_url'  => $imageUrl,
            ]);

            AiImageUsageLog::incrementUsage($userId);

            return response()->json([
                'success'   => true,
                'image_url' => Storage::disk('public')->url($filename),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Troca body_text pela variação escolhida ({index} = 0, 1 ou 2).
     */
    public function chooseVariation(Request $request, $id, $index)
    {
        Gate::authorize('access-social-ai');

        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $index = (int) $index;
        $variations = $post->caption_variations ?? [];

        if (!isset($variations[$index])) {
            return response()->json(['success' => false, 'message' => 'Variação inexistente.'], 422);
        }

        $post->update(['body_text' => $variations[$index]]);

        return response()->json([
            'success'   => true,
            'body_text' => $post->body_text,
        ]);
    }

    public function getStatus($postId)
    {
        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($postId);

        return response()->json([
            'status'     => $post->status,
            'body_text'  => $post->body_text,
            'image_url'  => $post->image_path
                ? Storage::disk('public')->url($post->image_path)
                : null,
        ]);
    }

    public function toBroadcast($id)
    {
        Gate::authorize('access-social-ai');

        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        session()->flash('ai_broadcast_message', $post->body_text);

        return redirect()->route('whatsapp.broadcast.index');
    }

    public function scheduleToCalendar(Request $request, $id)
    {
        Gate::authorize('access-social-ai');

        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'draft')
            ->findOrFail($id);

        $request->validate([
            'platform'     => 'required|in:facebook,instagram,both',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $account = SocialAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return response()->json([
                'success'     => false,
                'message'     => 'Nenhuma conta do Facebook/Instagram conectada. Conecte sua conta Meta para agendar.',
                'connect_url' => route('social.accounts'),
            ], 422);
        }

        $mediaUrl = $post->image_path
            ? Storage::disk('public')->url($post->image_path)
            : $post->image_url;

        ScheduledPost::create([
            'tenant_id'         => $tenantId,
            'social_account_id' => $account->id,
            'user_id'           => auth()->id(),
            'platform'          => $request->platform,
            'caption'           => $post->body_text,
            'media_url'         => $mediaUrl,
            'media_type'        => $mediaUrl ? 'image' : 'none',
            'scheduled_at'      => $request->scheduled_at,
            'status'            => 'scheduled',
        ]);

        $post->update([
            'status'       => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return response()->json([
            'success'      => true,
            'message'      => 'Post agendado com sucesso no calendário de publicação!',
            'calendar_url' => route('social.posts.index'),
        ]);
    }

    public function destroy($id)
    {
        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        if ($post->image_path && Storage::disk('public')->exists($post->image_path)) {
            Storage::disk('public')->delete($post->image_path);
        }

        // Apaga também a imagem de referência (se diferente da final)
        if ($post->reference_image_path
            && $post->reference_image_path !== $post->image_path
            && Storage::disk('public')->exists($post->reference_image_path)) {
            Storage::disk('public')->delete($post->reference_image_path);
        }

        $post->delete();

        return response()->json(['success' => true]);
    }
}
