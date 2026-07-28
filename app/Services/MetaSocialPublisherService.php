<?php

namespace App\Services;

use App\Models\ScheduledPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaSocialPublisherService
{
    private string $graphVersion = 'v22.0';

    /** Publica o post agendado nas plataformas configuradas */
    public function publish(ScheduledPost $post): bool
    {
        $account = $post->account;
        if (!$account || !$account->is_active) {
            $this->fail($post, 'Conta social inativa ou não encontrada.');
            return false;
        }

        $token = $account->access_token;
        $success = false;

        // Publicar no Facebook
        if (in_array($post->platform, ['facebook', 'both'])) {
            $fbId = $this->publishToFacebook($post, $account->page_id, $token);
            if ($fbId) {
                $post->facebook_post_id = $fbId;
                $success = true;
            } else {
                $this->fail($post, 'Falha ao publicar no Facebook.');
                return false;
            }
        }

        // Publicar no Instagram
        if (in_array($post->platform, ['instagram', 'both']) && $account->instagram_business_id) {
            $igId = $this->publishToInstagram($post, $account->instagram_business_id, $token);
            if ($igId) {
                $post->instagram_post_id = $igId;
                $success = true;
            } else {
                $this->fail($post, 'Falha ao publicar no Instagram.');
                return false;
            }
        }

        if ($success) {
            $post->status = 'published';
            $post->save();
        }

        return $success;
    }

    private function publishToFacebook(ScheduledPost $post, string $pageId, string $token): ?string
    {
        $endpoint = "https://graph.facebook.com/{$this->graphVersion}/{$pageId}";
        $payload  = ['message' => $post->caption, 'access_token' => $token];

        if ($post->media_url && $post->media_type === 'image') {
            $endpoint .= '/photos';
            $payload['url'] = $post->media_url;
            $payload['published'] = true;
        } elseif ($post->media_url && $post->media_type === 'video') {
            $endpoint .= '/videos';
            $payload['file_url'] = $post->media_url;
        } else {
            $endpoint .= '/feed';
        }

        $response = Http::post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error('Facebook publish failed', ['post_id' => $post->id, 'body' => $response->body()]);
            return null;
        }

        return $response->json('id') ?? $response->json('post_id');
    }

    private function publishToInstagram(ScheduledPost $post, string $igAccountId, string $token): ?string
    {
        // Passo 1: criar container de mídia
        $containerPayload = [
            'caption'      => $post->caption,
            'access_token' => $token,
        ];

        if ($post->media_url && $post->media_type === 'image') {
            $containerPayload['image_url'] = $post->media_url;
            $containerPayload['media_type'] = 'IMAGE';
        } elseif ($post->media_url && $post->media_type === 'video') {
            $containerPayload['video_url'] = $post->media_url;
            $containerPayload['media_type'] = 'REELS';
        } else {
            // Instagram exige mídia — pula se não tiver
            Log::warning('Instagram publish skipped: no media', ['post_id' => $post->id]);
            return null;
        }

        $containerRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media",
            $containerPayload
        );

        if (!$containerRes->successful()) {
            Log::error('Instagram container failed', ['body' => $containerRes->body()]);
            return null;
        }

        $containerId = $containerRes->json('id');

        // Passo 2: publicar o container
        $publishRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish",
            ['creation_id' => $containerId, 'access_token' => $token]
        );

        if (!$publishRes->successful()) {
            Log::error('Instagram publish failed', ['body' => $publishRes->body()]);
            return null;
        }

        return $publishRes->json('id');
    }

    private function fail(ScheduledPost $post, string $message): void
    {
        $post->status        = 'failed';
        $post->error_message = $message;
        $post->save();
        Log::error("ScheduledPost #{$post->id} failed: {$message}");
    }
}
