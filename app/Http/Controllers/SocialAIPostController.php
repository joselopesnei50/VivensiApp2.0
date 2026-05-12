<?php

namespace App\Http\Controllers;

use App\Models\AiSocialPost;
use App\Models\AiImageUsageLog;
use App\Jobs\GenerateSocialPostJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SocialAIPostController extends Controller
{
    public function index()
    {
        Gate::authorize('access-whatsapp'); // Reutilizando a gate existente de mensageria
        
        $userId = auth()->id();
        $posts = AiSocialPost::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $quotaUsed = AiImageUsageLog::getCurrentUsage($userId);

        return view('social_ai.index', compact('posts', 'quotaUsed'));
    }

    public function generate(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $request->validate([
            'theme' => 'required|string|max:500',
        ]);

        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        // Verifica cota ANTES de despachar para evitar filas inúteis
        $currentUsage = AiImageUsageLog::getCurrentUsage($userId);
        if ($currentUsage >= 60) {
            return response()->json([
                'success' => false, 
                'message' => 'Limite mensal de 60 imagens atingido.'
            ], 422);
        }

        // Cria o registro com status inicial
        $post = AiSocialPost::create([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'title_theme' => $request->theme,
            'status'      => 'draft', // Será atualizado pelo Job
        ]);

        // Despacha o Job
        GenerateSocialPostJob::dispatch($post->id);

        return response()->json([
            'success' => true,
            'message' => 'Geração iniciada! O post aparecerá em instantes no seu hub.',
            'post'    => $post
        ]);
    }

    public function destroy($id)
    {
        $post = AiSocialPost::where('user_id', auth()->id())->findOrFail($id);
        $post->delete();

        return redirect()->back()->with('success', 'Post removido com sucesso.');
    }
}
