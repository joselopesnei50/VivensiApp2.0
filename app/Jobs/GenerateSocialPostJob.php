<?php

namespace App\Jobs;

use App\Models\AiSocialPost;
use App\Models\AiImageUsageLog;
use App\Services\SocialAIContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $backoff  = 60;
    public $timeout  = 300; // 5 min: DeepSeek(60) + Together AI(120) + download(60) + margem

    protected $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    public function handle(SocialAIContentService $aiService)
    {
        $post = AiSocialPost::find($this->postId);
        if (!$post) return;

        try {
            // 1. Validar Cota
            $aiService->validateQuota($post->user_id);

            // 2. Chamar DeepSeek (Texto + Prompt Imagem)
            $content = $aiService->generateContent($post->title_theme);
            
            $post->update([
                'body_text' => $content['caption'],
            ]);

            // 3. Chamar Together AI (Imagem)
            $imageUrl = $aiService->generateImage($content['image_prompt']);

            // 4. Download e Save da Imagem no Storage Local
            $imageContents = Http::timeout(60)->get($imageUrl)->body();
            $filename = 'social_ai/' . uniqid() . '.jpg';
            Storage::disk('public')->put($filename, $imageContents);

            // 5. Finalizar Post (status 'draft' = gerado, pronto para publicar)
            $post->update([
                'image_path' => $filename,
                'image_url'  => $imageUrl,
                'status'     => 'draft',
            ]);

            // 6. Incrementar Cota
            AiImageUsageLog::incrementUsage($post->user_id);

            Log::info("Post de IA gerado com sucesso para o usuário {$post->user_id}");

        } catch (Exception $e) {
            Log::error("GenerateSocialPostJob Error: " . $e->getMessage());
            
            $post->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e; // Permite que o Laravel tente novamente se $tries > 1
        }
    }
}
