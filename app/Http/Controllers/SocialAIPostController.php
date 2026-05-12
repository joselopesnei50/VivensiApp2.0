<?php

namespace App\Http\Controllers;

use App\Models\AiSocialPost;
use App\Models\AiImageUsageLog;
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

    public function generate(Request $request)
    {
        Gate::authorize('access-social-ai');

        $request->validate([
            'theme'        => 'required|string|max:500',
            'user_context' => 'nullable|string|max:1000',
        ]);

        $userId   = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
        if ($currentUsage >= 60) {
            return response()->json([
                'success' => false,
                'message' => 'Limite mensal de 60 imagens atingido. Sua cota renova no próximo mês.',
            ], 422);
        }

        $post = AiSocialPost::create([
            'tenant_id'    => $tenantId,
            'user_id'      => $userId,
            'title_theme'  => $request->theme,
            'user_context' => $request->user_context,
            'status'       => 'processing',
        ]);

        GenerateSocialPostJob::dispatch($post->id);

        return response()->json([
            'success' => true,
            'message' => 'Geração iniciada! O post aparecerá aqui em instantes.',
            'post_id' => $post->id,
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

    public function destroy($id)
    {
        $post = AiSocialPost::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        if ($post->image_path && Storage::disk('public')->exists($post->image_path)) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return response()->json(['success' => true]);
    }
}
