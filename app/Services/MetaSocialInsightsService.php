<?php

namespace App\Services;

use App\Models\PostMetric;
use App\Models\ScheduledPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Coleta métricas (insights) de posts publicados na Meta.
 *
 * Duas fontes independentes:
 *   - Facebook Page post → Insights API (precisa pages_read_engagement)
 *   - Instagram Business media → Insights API (precisa instagram_manage_insights)
 *
 * Cada post publicado em Ambos gera 2 linhas em post_metrics (source=facebook + source=instagram).
 *
 * Uso típico:
 *   $svc = app(MetaSocialInsightsService::class);
 *   $svc->syncPost($post);
 *
 * Chamado pelo command posts:sync-metrics (rodado hourly no scheduler).
 * Todas as falhas são não-bloqueantes — Meta pode devolver 401 quando token
 * expira, 400 quando falta permissão, etc. Loga warning e segue.
 */
class MetaSocialInsightsService
{
    private string $graphVersion = 'v22.0';

    /**
     * Sincroniza métricas de um único post. Chama FB e IG conforme o post
     * tem facebook_post_id e/ou instagram_post_id.
     *
     * @return array  ['facebook' => ?bool, 'instagram' => ?bool] — status por rede
     */
    public function syncPost(ScheduledPost $post): array
    {
        $result  = ['facebook' => null, 'instagram' => null];
        $account = $post->account;

        if (!$account || !$account->is_active || $post->status !== 'published') {
            return $result;
        }

        if ($post->facebook_post_id) {
            $result['facebook'] = $this->syncFacebook($post, $account->access_token);
        }
        if ($post->instagram_post_id) {
            $result['instagram'] = $this->syncInstagram($post, $account->access_token);
        }

        return $result;
    }

    /**
     * Facebook Page post insights.
     * https://developers.facebook.com/docs/graph-api/reference/post/insights
     *
     * Meta descontinuou várias métricas ao longo das versões. Em v22:
     * - post_impressions_unique: DEPRECATED (deprecated em v10+)
     * - post_engaged_users:      DEPRECATED
     * - post_impressions:        OK
     * - post_reactions_*_total:  OK (like/love/wow/haha/sorry/anger)
     * - post_clicks:             OK
     *
     * Estratégia: tenta pedir set completo (com métricas atuais); se ainda
     * falhar (mudança futura da Meta), cai pra set mínimo garantido.
     */
    private function syncFacebook(ScheduledPost $post, string $token): bool
    {
        $endpoint = "https://graph.facebook.com/{$this->graphVersion}/{$post->facebook_post_id}/insights";

        // Tentativa 1: métricas atuais válidas em v22
        $res = Http::get($endpoint, [
            'metric'       => 'post_impressions,post_reactions_like_total,post_clicks',
            'access_token' => $token,
        ]);

        // Tentativa 2 (fallback): apenas post_impressions — sempre válida
        if (!$res->successful()) {
            $res = Http::get($endpoint, [
                'metric'       => 'post_impressions',
                'access_token' => $token,
            ]);
        }

        if (!$res->successful()) {
            Log::warning('FB post insights failed', [
                'post_id'    => $post->id,
                'fb_post_id' => $post->facebook_post_id,
                'status'     => $res->status(),
                'body'       => $res->json() ?: $res->body(),
            ]);
            return false;
        }

        // Extrai valores. Cada métrica vem em data[].values[0].value
        $values = collect($res->json('data', []))
            ->keyBy('name')
            ->map(fn ($m) => (int) ($m['values'][0]['value'] ?? 0));

        // Comentários, shares e reactions/likes vêm de endpoints separados
        // (não estão no insights). Um único fetch com summary.
        [$comments, $shares, $likesFromSummary] = $this->fetchFacebookEngagement($post, $token);

        // Likes: prefere insights (post_reactions_like_total), cai no summary
        $likes = (int) $values->get('post_reactions_like_total', 0) ?: $likesFromSummary;
        $engagement = $likes + $comments + $shares + (int) $values->get('post_clicks', 0);

        PostMetric::withoutGlobalScope('tenant')->updateOrCreate(
            [
                'scheduled_post_id' => $post->id,
                'source'            => PostMetric::SOURCE_FACEBOOK,
            ],
            [
                'tenant_id'   => $post->tenant_id,
                'impressions' => (int) $values->get('post_impressions', 0),
                'reach'       => 0, // post_impressions_unique deprecated em v10+
                'likes'       => $likes,
                'comments'    => $comments,
                'shares'      => $shares,
                'clicks'      => (int) $values->get('post_clicks', 0),
                'saved'       => 0, // não existe pra Facebook
                'engagement'  => $engagement,
                'fetched_at'  => now(),
            ]
        );

        return true;
    }

    /**
     * Engagement do post do FB via endpoint principal (não insights):
     * comments, shares e reactions com summary. Público — só requer o
     * page access token, sem escopo insights.
     * Retorna [comments, shares, likes_from_summary].
     */
    private function fetchFacebookEngagement(ScheduledPost $post, string $token): array
    {
        try {
            $res = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$post->facebook_post_id}", [
                'fields'       => 'comments.summary(true).limit(0),shares,reactions.summary(true).limit(0)',
                'access_token' => $token,
            ]);
            if ($res->successful()) {
                $body = $res->json();
                return [
                    (int) ($body['comments']['summary']['total_count']  ?? 0),
                    (int) ($body['shares']['count']                     ?? 0),
                    (int) ($body['reactions']['summary']['total_count'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('FB engagement fetch failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
        }
        return [0, 0, 0];
    }

    /**
     * Instagram media insights.
     * https://developers.facebook.com/docs/instagram-api/guides/insights
     */
    private function syncInstagram(ScheduledPost $post, string $token): bool
    {
        // Instagram tem métricas diferentes por tipo de mídia.
        // Pra IMAGE/VIDEO/CAROUSEL feed: impressions, reach, likes, comments, saved, shares
        // Pra STORY: impressions, reach, replies, taps_forward, taps_back, exits
        // Como a gente não sabe o tipo real da mídia publicada, tenta a lista mais
        // comum e ignora métricas não disponíveis (Meta devolve error se não existir).
        $metrics = 'impressions,reach,likes,comments,saved,shares';

        $res = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$post->instagram_post_id}/insights", [
            'metric'       => $metrics,
            'access_token' => $token,
        ]);

        if (!$res->successful()) {
            // Tenta com métricas mínimas (compatibilidade com contas novas ou
            // permissões parciais)
            $res = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$post->instagram_post_id}/insights", [
                'metric'       => 'impressions,reach',
                'access_token' => $token,
            ]);
        }

        if (!$res->successful()) {
            Log::warning('IG post insights failed', [
                'post_id'    => $post->id,
                'ig_post_id' => $post->instagram_post_id,
                'status'     => $res->status(),
                'body'       => $res->json() ?: $res->body(),
            ]);
            return false;
        }

        $values = collect($res->json('data', []))
            ->keyBy('name')
            ->map(fn ($m) => (int) ($m['values'][0]['value'] ?? 0));

        $likes    = (int) $values->get('likes', 0);
        $comments = (int) $values->get('comments', 0);
        $shares   = (int) $values->get('shares', 0);
        $saved    = (int) $values->get('saved', 0);
        $engagement = $likes + $comments + $shares + $saved;

        PostMetric::withoutGlobalScope('tenant')->updateOrCreate(
            [
                'scheduled_post_id' => $post->id,
                'source'            => PostMetric::SOURCE_INSTAGRAM,
            ],
            [
                'tenant_id'   => $post->tenant_id,
                'impressions' => (int) $values->get('impressions', 0),
                'reach'       => (int) $values->get('reach', 0),
                'likes'       => $likes,
                'comments'    => $comments,
                'shares'      => $shares,
                'clicks'      => 0, // não existe pra IG
                'saved'       => $saved,
                'engagement'  => $engagement,
                'fetched_at'  => now(),
            ]
        );

        return true;
    }
}
