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
        $this->onQueue('ai');
    }

    public function handle(SocialAIContentService $aiService)
    {
        $post = AiSocialPost::find($this->postId);
        if (!$post) return;

        try {
            $useReferenceAsFinal = (bool) $post->use_reference_as_final;
            $format              = $post->format ?: 'square';
            $visualStyle         = $post->visual_style ?: null;

            // 1. Validar cota SÓ se for gerar imagem (referência não consome cota)
            if (!$useReferenceAsFinal) {
                $aiService->validateQuota($post->user_id);
            }

            // 2. DeepSeek: gera 3 variações de legenda + image_prompt
            $content = $aiService->generateContent(
                $post->title_theme,
                $post->user_context,
                $visualStyle,
                $format
            );

            $captions = $content['captions'];

            $post->update([
                'body_text'          => $captions[0],  // primeira é o default
                'caption_variations' => $captions,     // trio completo pro user escolher
            ]);

            // 3. Imagem — dois caminhos:
            //    A) user marcou "usar referência como imagem final" → copia
            //       o arquivo já subido pra o path final, sem chamar Together AI.
            //    B) fluxo normal → gera imagem via FLUX.1.
            if ($useReferenceAsFinal && $post->reference_image_path) {
                $refPath = $post->reference_image_path;
                if (Storage::disk('public')->exists($refPath)) {
                    // Ext original (jpg/png/webp)
                    $ext = pathinfo($refPath, PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'social_ai/' . uniqid() . '.' . $ext;
                    Storage::disk('public')->copy($refPath, $filename);
                    $post->update([
                        'image_path' => $filename,
                        'image_url'  => null,
                        'status'     => 'draft',
                    ]);
                    Log::info("SocialAI: usou referência do user como imagem final (post {$post->id})");
                } else {
                    Log::warning("SocialAI: reference_image_path {$refPath} não encontrado, caindo pra geração normal");
                    $useReferenceAsFinal = false;
                }
            }

            if (!$useReferenceAsFinal) {
                // Se não tínhamos validado cota antes (referência final), valida agora
                $aiService->validateQuota($post->user_id);

                $imageUrl = $aiService->generateImage($content['image_prompt'], $format);
                $imageContents = Http::timeout(60)->get($imageUrl)->body();
                $filename = 'social_ai/' . uniqid() . '.jpg';
                Storage::disk('public')->put($filename, $imageContents);

                $post->update([
                    'image_path' => $filename,
                    'image_url'  => $imageUrl,
                    'status'     => 'draft',
                ]);

                AiImageUsageLog::incrementUsage($post->user_id);
            }

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
