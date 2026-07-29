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
            $fb = $this->publishToFacebook($post, $account->page_id, $token);
            if (!empty($fb['id'])) {
                $post->facebook_post_id = $fb['id'];
                $success = true;
            } else {
                $this->fail($post, $fb['error'] ?? 'Falha ao publicar no Facebook.');
                return false;
            }
        }

        // Publicar no Instagram
        if (in_array($post->platform, ['instagram', 'both']) && $account->instagram_business_id) {
            $ig = $this->publishToInstagram($post, $account->instagram_business_id, $token);
            if (!empty($ig['id'])) {
                $post->instagram_post_id = $ig['id'];
                $success = true;
            } else {
                $this->fail($post, $ig['error'] ?? 'Falha ao publicar no Instagram.');
                return false;
            }
        }

        if ($success) {
            $post->status = 'published';
            $post->save();
        }

        return $success;
    }

    /**
     * Garante que a URL da mídia é absoluta com esquema http(s). Meta rejeita
     * caminho relativo (Storage::url() sozinho retorna /storage/... que não é
     * "valid URL" segundo a Graph API — bug em prod 2026-07-29).
     */
    private function absoluteMediaUrl(?string $url): ?string
    {
        if (!$url) return null;
        if (preg_match('#^https?://#i', $url)) return $url;
        return url($url); // prefixa com APP_URL
    }

    /**
     * Traduz códigos de erro comuns da Graph API pra mensagem PT-BR acionável.
     * Referência: https://developers.facebook.com/docs/graph-api/guides/error-handling/
     */
    private function friendlyError(array $err): string
    {
        $code    = isset($err['code']) ? (int) $err['code'] : null;
        $subcode = isset($err['error_subcode']) ? (int) $err['error_subcode'] : null;
        $msg     = trim((string) ($err['message'] ?? ''));

        $friendly = match (true) {
            $code === 190 => 'Sessão do Facebook expirou. Reconecte sua conta em Redes Sociais.',
            $code === 200 || $code === 10 => 'O app não tem permissão para publicar nesta Página. Verifique se pages_manage_posts / instagram_content_publish foram aprovadas pela Meta, e se você (ou o cliente) autorizou essa permissão na conexão OAuth.',
            $code === 100 && $subcode === 33 => 'O objeto solicitado não existe ou não pertence à Página conectada.',
            $code === 100 => 'Parâmetro inválido na chamada Meta. Verifique se a mídia tem URL pública acessível e formato correto.',
            $code === 4   => 'Limite de chamadas da Meta atingido temporariamente. Aguarde alguns minutos.',
            $code === 368 => 'Sua Página do Facebook está temporariamente bloqueada de publicar.',
            $code === 1487 => 'A URL da mídia não pôde ser baixada pela Meta. Confirme que o link é público.',
            default => null,
        };

        return $friendly
            ?? ($msg !== '' ? $msg . ($code ? " (code {$code})" : '') : 'Erro desconhecido da Meta.');
    }

    /** @return array{id: ?string, error: ?string} */
    private function publishToFacebook(ScheduledPost $post, string $pageId, string $token): array
    {
        $endpoint = "https://graph.facebook.com/{$this->graphVersion}/{$pageId}";
        $payload  = ['message' => $post->caption, 'access_token' => $token];

        // Meta exige URL absoluta (http/https). Se veio relativa (/storage/...)
        // prefixa com APP_URL. Defesa em profundidade — controller já faz isso.
        $mediaUrl = $this->absoluteMediaUrl($post->media_url);

        if ($mediaUrl && $post->media_type === 'image') {
            $endpoint .= '/photos';
            $payload['url'] = $mediaUrl;
            $payload['published'] = true;
        } elseif ($mediaUrl && $post->media_type === 'video') {
            $endpoint .= '/videos';
            $payload['file_url'] = $mediaUrl;
        } else {
            $endpoint .= '/feed';
        }

        $response = Http::post($endpoint, $payload);

        if (!$response->successful()) {
            $body = $response->json() ?? [];
            $err  = (array) ($body['error'] ?? []);
            Log::error('Facebook publish failed', [
                'post_id' => $post->id,
                'status'  => $response->status(),
                'error'   => $err,
                'body'    => $body ?: $response->body(),
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        return [
            'id'    => $response->json('id') ?? $response->json('post_id'),
            'error' => null,
        ];
    }

    /** @return array{id: ?string, error: ?string} */
    private function publishToInstagram(ScheduledPost $post, string $igAccountId, string $token): array
    {
        // Passo 1: criar container de mídia
        $containerPayload = [
            'caption'      => $post->caption,
            'access_token' => $token,
        ];

        $mediaUrl = $this->absoluteMediaUrl($post->media_url);

        if ($mediaUrl && $post->media_type === 'image') {
            $containerPayload['image_url'] = $mediaUrl;
            $containerPayload['media_type'] = 'IMAGE';
        } elseif ($mediaUrl && $post->media_type === 'video') {
            $containerPayload['video_url'] = $mediaUrl;
            $containerPayload['media_type'] = 'REELS';
        } else {
            // Instagram exige mídia — pula se não tiver
            Log::warning('Instagram publish skipped: no media', ['post_id' => $post->id]);
            return ['id' => null, 'error' => 'Instagram exige uma imagem ou vídeo — este post não tem mídia anexada.'];
        }

        $containerRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media",
            $containerPayload
        );

        if (!$containerRes->successful()) {
            $body = $containerRes->json() ?? [];
            $err  = (array) ($body['error'] ?? []);
            Log::error('Instagram container failed', [
                'post_id' => $post->id,
                'status'  => $containerRes->status(),
                'error'   => $err,
                'body'    => $body ?: $containerRes->body(),
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        $containerId = $containerRes->json('id');

        // Passo 2: publicar o container
        $publishRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish",
            ['creation_id' => $containerId, 'access_token' => $token]
        );

        if (!$publishRes->successful()) {
            $body = $publishRes->json() ?? [];
            $err  = (array) ($body['error'] ?? []);
            Log::error('Instagram publish failed', [
                'post_id' => $post->id,
                'status'  => $publishRes->status(),
                'error'   => $err,
                'body'    => $body ?: $publishRes->body(),
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        return ['id' => $publishRes->json('id'), 'error' => null];
    }

    private function fail(ScheduledPost $post, string $message): void
    {
        $post->status        = 'failed';
        $post->error_message = $message;
        $post->save();
        Log::error("ScheduledPost #{$post->id} failed: {$message}");
    }
}
