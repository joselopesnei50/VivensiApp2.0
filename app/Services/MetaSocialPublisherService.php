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

        $format = $post->format ?? 'feed';
        $isStory = $format === 'story';

        // Publicar no Facebook — Story via API não é suportada de forma
        // estável pela Meta pra third-parties, então pulamos silenciosamente
        // quando format=story mesmo se o gestor marcou "Ambos" (a UI já
        // avisa isso).
        if (in_array($post->platform, ['facebook', 'both']) && !$isStory) {
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
     * Instagram Story — publica UMA mídia com media_type=STORIES.
     * Story dura 24h, depois vai pro arquivo. Não suporta legenda visível
     * no feed (caption é ignorada na renderização do story), nem carrossel
     * (só single). Suporta imagem (JPG/PNG, ideal 1080×1920 aspect 9:16)
     * ou vídeo (até 60s).
     *
     * Referência: https://developers.facebook.com/docs/instagram-api/guides/content-publishing#stories
     *
     * @param  array{url:string,type:string} $item
     * @return array{id: ?string, error: ?string}
     */
    private function publishInstagramStory(ScheduledPost $post, string $igAccountId, string $token, array $item): array
    {
        $mediaUrl = $this->absoluteMediaUrl($item['url']);
        $type     = $item['type'] ?? 'image';

        $payload = [
            'media_type'   => 'STORIES',
            'access_token' => $token,
        ];
        if ($type === 'image') {
            $payload['image_url'] = $mediaUrl;
        } elseif ($type === 'video') {
            $payload['video_url'] = $mediaUrl;
        } else {
            return ['id' => null, 'error' => 'Story precisa ser imagem ou vídeo.'];
        }

        // Passo 1 — cria container do Story.
        $containerRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media",
            $payload
        );

        if (!$containerRes->successful()) {
            $err = (array) ($containerRes->json('error') ?? []);
            Log::error('Instagram story container failed', [
                'post_id' => $post->id, 'status' => $containerRes->status(), 'error' => $err,
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        $containerId = $containerRes->json('id');

        // Passo 2 — aguarda FINISHED.
        $waitErr = $this->waitForInstagramContainer($containerId, $token);
        if ($waitErr !== null) {
            return ['id' => null, 'error' => $waitErr];
        }

        // Passo 3 — publica.
        $publishRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish",
            ['creation_id' => $containerId, 'access_token' => $token]
        );

        if (!$publishRes->successful()) {
            $err = (array) ($publishRes->json('error') ?? []);
            Log::error('Instagram story publish failed', [
                'post_id' => $post->id, 'status' => $publishRes->status(), 'error' => $err,
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        return ['id' => $publishRes->json('id'), 'error' => null];
    }

    /**
     * Carrossel Instagram (2-10 mídias no mesmo post).
     * Fluxo Meta em 3 fases:
     *   1) Cria N child containers com is_carousel_item=true
     *   2) Aguarda cada child ficar FINISHED (polling)
     *   3) Cria container CAROUSEL com children=[ids] + caption
     *   4) Aguarda o CAROUSEL ficar FINISHED
     *   5) POST /media_publish com creation_id=carousel_id
     *
     * Limite Meta: máximo 10 itens. Suporta mix de imagem+video.
     * Referência: https://developers.facebook.com/docs/instagram-api/guides/content-publishing#carousel-posts
     *
     * @param  array<int, array{url:string,type:string}> $items
     * @return array{id: ?string, error: ?string}
     */
    private function publishInstagramCarousel(ScheduledPost $post, string $igAccountId, string $token, array $items): array
    {
        if (count($items) > 10) {
            $items = array_slice($items, 0, 10);
        }

        $childIds = [];

        // ── Fase 1 — cria cada child container ────────────────────────
        foreach ($items as $idx => $item) {
            $mediaUrl = $this->absoluteMediaUrl($item['url']);
            $type     = $item['type'] ?? 'image';

            $payload = [
                'is_carousel_item' => true,
                'access_token'     => $token,
            ];
            if ($type === 'image') {
                $payload['image_url']  = $mediaUrl;
                $payload['media_type'] = 'IMAGE';
            } elseif ($type === 'video') {
                $payload['video_url']  = $mediaUrl;
                $payload['media_type'] = 'VIDEO'; // dentro de carrossel usa VIDEO, não REELS
            } else {
                Log::warning('Carousel item ignored: tipo desconhecido', [
                    'post_id' => $post->id, 'idx' => $idx, 'type' => $type,
                ]);
                continue;
            }

            $res = Http::post(
                "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media",
                $payload
            );

            if (!$res->successful()) {
                $err = (array) ($res->json('error') ?? []);
                Log::error('Instagram carousel child container failed', [
                    'post_id' => $post->id, 'idx' => $idx,
                    'status'  => $res->status(), 'error' => $err,
                ]);
                return [
                    'id'    => null,
                    'error' => "Falha na mídia #" . ($idx + 1) . " do carrossel: " . $this->friendlyError($err),
                ];
            }

            $childIds[] = $res->json('id');
        }

        if (empty($childIds)) {
            return ['id' => null, 'error' => 'Nenhuma mídia do carrossel pôde ser processada.'];
        }

        // ── Fase 2 — aguarda cada child ficar FINISHED ────────────────
        foreach ($childIds as $idx => $cid) {
            $waitErr = $this->waitForInstagramContainer($cid, $token);
            if ($waitErr !== null) {
                Log::error('Instagram carousel child never finished', [
                    'post_id' => $post->id, 'idx' => $idx, 'container_id' => $cid, 'reason' => $waitErr,
                ]);
                return [
                    'id'    => null,
                    'error' => "Mídia #" . ($idx + 1) . " do carrossel travou processando na Meta. " . $waitErr,
                ];
            }
        }

        // ── Fase 3 — cria container CAROUSEL agrupando os children ────
        $carouselRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media",
            [
                'media_type'   => 'CAROUSEL',
                'caption'      => $post->caption,
                'children'     => implode(',', $childIds),
                'access_token' => $token,
            ]
        );

        if (!$carouselRes->successful()) {
            $err = (array) ($carouselRes->json('error') ?? []);
            Log::error('Instagram carousel container failed', [
                'post_id' => $post->id, 'status' => $carouselRes->status(), 'error' => $err,
            ]);
            return ['id' => null, 'error' => 'Falha ao montar carrossel: ' . $this->friendlyError($err)];
        }

        $carouselId = $carouselRes->json('id');

        // ── Fase 4 — aguarda o CAROUSEL ficar FINISHED ────────────────
        $waitErr = $this->waitForInstagramContainer($carouselId, $token);
        if ($waitErr !== null) {
            return ['id' => null, 'error' => $waitErr];
        }

        // ── Fase 5 — publica ──────────────────────────────────────────
        $publishRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish",
            ['creation_id' => $carouselId, 'access_token' => $token]
        );

        if (!$publishRes->successful()) {
            $err = (array) ($publishRes->json('error') ?? []);
            Log::error('Instagram carousel publish failed', [
                'post_id' => $post->id, 'status' => $publishRes->status(), 'error' => $err,
            ]);
            return ['id' => null, 'error' => 'Falha no publish do carrossel: ' . $this->friendlyError($err)];
        }

        return ['id' => $publishRes->json('id'), 'error' => null];
    }

    /**
     * Aguarda o Instagram terminar de processar um container de mídia antes
     * de publicar. Faz polling GET /{container_id}?fields=status_code.
     *
     * status_code possíveis:
     *  - IN_PROGRESS: ainda baixando/processando → espera
     *  - FINISHED:    pronto → OK, retorna null
     *  - ERROR:       falhou → retorna mensagem
     *  - EXPIRED:     passou de 24h → retorna mensagem
     *  - PUBLISHED:   já foi publicado antes → OK
     *
     * @return string|null null em caso de sucesso, msg PT-BR em caso de falha.
     */
    private function waitForInstagramContainer(string $containerId, string $token, int $maxTries = 8): ?string
    {
        // Backoff crescente: 1s, 1s, 2s, 2s, 3s, 3s, 4s, 5s = até ~21s total.
        // Cobre a maioria dos casos (imagem pequena processa em <5s).
        $delays = [1, 1, 2, 2, 3, 3, 4, 5];

        for ($i = 0; $i < $maxTries; $i++) {
            sleep($delays[$i] ?? 3);

            $res = Http::get(
                "https://graph.facebook.com/{$this->graphVersion}/{$containerId}",
                ['fields' => 'status_code', 'access_token' => $token]
            );

            if (!$res->successful()) {
                // Erro de leitura do status — não bloqueia, tenta publicar
                // mesmo assim (às vezes só a leitura falha).
                return null;
            }

            $status = $res->json('status_code');

            if ($status === 'FINISHED' || $status === 'PUBLISHED') {
                return null; // pronto pra publicar
            }
            if ($status === 'ERROR') {
                return 'O Instagram rejeitou a mídia. Verifique se o formato/proporção está correto (imagem quadrada JPG/PNG recomendada).';
            }
            if ($status === 'EXPIRED') {
                return 'O container do Instagram expirou (24h). Crie o post novamente.';
            }
            // IN_PROGRESS → continua o loop
        }

        return 'O Instagram demorou muito pra processar a mídia. Tente com uma imagem menor ou tente novamente em alguns segundos.';
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
            $code === 9007 => 'O Instagram ainda estava processando a mídia quando pedimos pra publicar. Tente novamente em alguns segundos.',
            default => null,
        };

        return $friendly
            ?? ($msg !== '' ? $msg . ($code ? " (code {$code})" : '') : 'Erro desconhecido da Meta.');
    }

    /**
     * Publica no Facebook Page.
     *
     * Fluxo pra POST COM IMAGEM (Meta v22 mudou comportamento):
     *   1) POST /{pageId}/photos com published=false → obtém media_fbid
     *   2) POST /{pageId}/feed com attached_media=[{media_fbid}] + message
     *      → obtém post_id real no formato PAGEID_POSTID
     *
     * Por quê o fluxo em 2 fases?
     *   - POST /photos com published=true às vezes cria a foto SEM criar
     *     post no feed. Fica órfã: id não é indexável em Insights nem
     *     Engagement API. Bug confirmado em prod 2026-07-29 (post #10).
     *   - Attached_media é o padrão oficial recomendado pela Meta pra
     *     posts com foto e SEMPRE retorna um post_id real.
     *
     * Vídeo continua via /videos (não tem esse bug — sempre publica).
     * Texto puro continua via /feed.
     *
     * @return array{id: ?string, error: ?string}
     */
    private function publishToFacebook(ScheduledPost $post, string $pageId, string $token): array
    {
        $mediaUrl = $this->absoluteMediaUrl($post->media_url);

        // ── FLUXO 1: post com imagem — 2 fases (upload + attach) ──
        if ($mediaUrl && $post->media_type === 'image') {
            return $this->publishFacebookPhotoPost($post, $pageId, $token, $mediaUrl);
        }

        // ── FLUXO 2: vídeo — endpoint /videos ──
        if ($mediaUrl && $post->media_type === 'video') {
            $res = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/videos", [
                'message'      => $post->caption,
                'file_url'     => $mediaUrl,
                'access_token' => $token,
            ]);
            return $this->parseFacebookPublishResponse($res, $post, $pageId);
        }

        // ── FLUXO 3: texto puro — /feed ──
        $res = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", [
            'message'      => $post->caption,
            'access_token' => $token,
        ]);
        return $this->parseFacebookPublishResponse($res, $post, $pageId);
    }

    /**
     * Publica foto na Page em 2 fases (padrão oficial Meta).
     * Fase 1: /photos published=false → media_fbid
     * Fase 2: /feed attached_media=[{media_fbid}] → post_id real
     */
    private function publishFacebookPhotoPost(ScheduledPost $post, string $pageId, string $token, string $mediaUrl): array
    {
        // Fase 1: upload da foto SEM publicar (só cria o media_fbid)
        $uploadRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/photos",
            [
                'url'          => $mediaUrl,
                'published'    => false,
                'access_token' => $token,
            ]
        );

        if (!$uploadRes->successful()) {
            $err = (array) ($uploadRes->json('error') ?? []);
            Log::error('FB photo upload (unpublished) failed', [
                'post_id' => $post->id, 'status' => $uploadRes->status(), 'error' => $err,
            ]);
            return ['id' => null, 'error' => $this->friendlyError($err)];
        }

        $mediaFbid = $uploadRes->json('id');
        if (!$mediaFbid) {
            return ['id' => null, 'error' => 'Meta não retornou id da foto no upload.'];
        }

        // Fase 2: cria post no feed com a foto anexada
        // Meta espera attached_media como JSON string na v22
        $feedRes = Http::post(
            "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed",
            [
                'message'        => $post->caption,
                'attached_media' => json_encode([['media_fbid' => $mediaFbid]]),
                'access_token'   => $token,
            ]
        );

        return $this->parseFacebookPublishResponse($feedRes, $post, $pageId);
    }

    /**
     * Extrai o id do response e normaliza pro formato PAGEID_POSTID.
     * /feed sempre retorna 'id' já composto. Fallback constroi manualmente
     * pra defesa em profundidade.
     */
    private function parseFacebookPublishResponse($response, ScheduledPost $post, string $pageId): array
    {
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

        $rawId  = $response->json('id');
        $postId = $response->json('post_id');

        if (!$postId && $rawId && !str_contains($rawId, '_')) {
            $postId = "{$pageId}_{$rawId}";
        }

        return ['id' => $postId ?? $rawId, 'error' => null];
    }

    /** @return array{id: ?string, error: ?string} */
    private function publishToInstagram(ScheduledPost $post, string $igAccountId, string $token): array
    {
        $items = $post->mediaList();

        if (empty($items)) {
            Log::warning('Instagram publish skipped: no media', ['post_id' => $post->id]);
            return ['id' => null, 'error' => 'Instagram exige uma imagem ou vídeo — este post não tem mídia anexada.'];
        }

        // Story tem fluxo próprio (independe de quantas mídias — só usa a
        // primeira, story não suporta carrossel). Roteamos antes do check
        // de carrossel abaixo pra não confundir.
        if (($post->format ?? 'feed') === 'story') {
            return $this->publishInstagramStory($post, $igAccountId, $token, $items[0]);
        }

        // 2+ mídias no feed → carrossel. Meta permite até 10.
        if (count($items) >= 2) {
            return $this->publishInstagramCarousel($post, $igAccountId, $token, $items);
        }

        // 1 mídia → fluxo simples (foto ou reel).
        $only = $items[0];
        $mediaUrl = $this->absoluteMediaUrl($only['url']);
        $type     = $only['type'] ?? 'image';

        $containerPayload = [
            'caption'      => $post->caption,
            'access_token' => $token,
        ];

        if ($type === 'image') {
            $containerPayload['image_url']  = $mediaUrl;
            $containerPayload['media_type'] = 'IMAGE';
        } elseif ($type === 'video') {
            $containerPayload['video_url']  = $mediaUrl;
            $containerPayload['media_type'] = 'REELS';
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

        // Passo 1.5: aguardar Instagram terminar de processar a mídia.
        // Sem esse polling, o /media_publish quase sempre volta code 9007
        // "Media ID is not available" — o container existe mas a imagem/video
        // ainda está sendo baixado da URL fornecida.
        // Referência: https://developers.facebook.com/docs/instagram-api/guides/content-publishing#step-2--check-media-container-status
        $waitError = $this->waitForInstagramContainer($containerId, $token);
        if ($waitError !== null) {
            Log::error('Instagram container timeout/error', [
                'post_id'      => $post->id,
                'container_id' => $containerId,
                'reason'       => $waitError,
            ]);
            return ['id' => null, 'error' => $waitError];
        }

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
