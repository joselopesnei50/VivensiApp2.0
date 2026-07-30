<?php
/**
 * Testa a coleta de insights em UM post específico e mostra o response
 * cru da Meta na tela. Útil quando o log não deu detalhe.
 *
 * Uso: sudo -u www-data php scripts/insights-test.php [post_id]
 * Sem post_id: usa o post publicado mais recente.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$postId = $argv[1] ?? null;

$post = \App\Models\ScheduledPost::withoutGlobalScopes()
    ->with('account')
    ->where('status', 'published')
    ->when($postId, fn ($q) => $q->where('id', $postId))
    ->orderByDesc('scheduled_at')
    ->first();

if (!$post) {
    echo "### Nenhum post publicado encontrado" . ($postId ? " com id={$postId}" : '') . "\n";
    exit(1);
}

echo "Post #{$post->id} | tenant={$post->tenant_id} | " . ($post->scheduled_at?->format('d/m H:i')) . "\n";
echo "FB post ID: " . ($post->facebook_post_id ?? '(nenhum)') . "\n";
echo "IG post ID: " . ($post->instagram_post_id ?? '(nenhum)') . "\n";
echo "Conta: " . ($post->account?->page_name ?? '(sem conta)') . "\n";
echo "Token: " . substr($post->account?->access_token ?? '', 0, 14) . "...\n\n";

$token   = $post->account->access_token;
$graphV  = 'v22.0';
$pageId  = $post->account->page_id;

// ── Sondagem inicial: descobre o tipo do objeto e o formato real dos IDs ──
echo str_repeat('=', 70) . "\n";
echo " Sondagem: /{$post->facebook_post_id}?fields=id,object_id (sem post_id)\n";
echo str_repeat('=', 70) . "\n";
$probe = \Illuminate\Support\Facades\Http::get(
    "https://graph.facebook.com/{$graphV}/{$post->facebook_post_id}",
    ['fields' => 'id,object_id,parent_id,created_time,type', 'access_token' => $token]
);
echo "HTTP {$probe->status()}\n";
echo $probe->body() . "\n\n";

echo str_repeat('=', 70) . "\n";
echo " Sondagem: /{$pageId}/posts?limit=3 (formato de IDs de posts da Page)\n";
echo str_repeat('=', 70) . "\n";
$posts = \Illuminate\Support\Facades\Http::get(
    "https://graph.facebook.com/{$graphV}/{$pageId}/posts",
    ['limit' => 3, 'access_token' => $token]
);
echo "HTTP {$posts->status()}\n";
echo $posts->body() . "\n\n";

echo str_repeat('=', 70) . "\n";
echo " Sondagem: /{$pageId}/photos?limit=3 (formato de IDs de fotos)\n";
echo str_repeat('=', 70) . "\n";
$photos = \Illuminate\Support\Facades\Http::get(
    "https://graph.facebook.com/{$graphV}/{$pageId}/photos",
    ['limit' => 3, 'fields' => 'id,name,link,created_time', 'access_token' => $token]
);
echo "HTTP {$photos->status()}\n";
echo $photos->body() . "\n\n";

// ── Facebook ──
if ($post->facebook_post_id) {
    $fbId = $post->facebook_post_id;

    // BACKFILL: se o ID salvo não tem '_', constroi PAGEID_OBJECTID manual.
    if (!str_contains($fbId, '_')) {
        $fixed = "{$pageId}_{$fbId}";
        echo "-- Backfill: {$fbId} -> {$fixed}\n\n";
        $post->facebook_post_id = $fixed;
        $post->save();
        $fbId = $fixed;
    }

    echo str_repeat('=', 70) . "\n";
    echo " FB Insights — GET /{$fbId}/insights\n";
    echo str_repeat('=', 70) . "\n";

    $res = \Illuminate\Support\Facades\Http::get(
        "https://graph.facebook.com/{$graphV}/{$fbId}/insights",
        [
            'metric'       => 'post_impressions,post_reactions_like_total,post_clicks',
            'access_token' => $token,
        ]
    );
    echo "HTTP {$res->status()}\n";
    echo $res->body() . "\n\n";

    // Engagement summary (comments/shares/reactions) — endpoint separado
    echo "-- Engagement summary (comments/shares/reactions):\n";
    $res2 = \Illuminate\Support\Facades\Http::get(
        "https://graph.facebook.com/{$graphV}/{$fbId}",
        [
            'fields'       => 'comments.summary(true).limit(0),shares,reactions.summary(true).limit(0)',
            'access_token' => $token,
        ]
    );
    echo "HTTP {$res2->status()}\n";
    echo $res2->body() . "\n\n";
}

// ── Instagram ──
if ($post->instagram_post_id) {
    echo str_repeat('=', 70) . "\n";
    echo " IG Insights — GET /{$post->instagram_post_id}/insights\n";
    echo str_repeat('=', 70) . "\n";

    $res = \Illuminate\Support\Facades\Http::get(
        "https://graph.facebook.com/{$graphV}/{$post->instagram_post_id}/insights",
        [
            'metric'       => 'impressions,reach,likes,comments,saved,shares',
            'access_token' => $token,
        ]
    );
    echo "HTTP {$res->status()}\n";
    echo $res->body() . "\n\n";
}

echo str_repeat('=', 70) . "\n";
echo " Fim.\n";
echo str_repeat('=', 70) . "\n";
