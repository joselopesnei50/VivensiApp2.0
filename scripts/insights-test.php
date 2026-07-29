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

// ── Facebook ──
if ($post->facebook_post_id) {
    echo str_repeat('=', 70) . "\n";
    echo " FB Insights — GET /{$post->facebook_post_id}/insights\n";
    echo str_repeat('=', 70) . "\n";

    $res = \Illuminate\Support\Facades\Http::get(
        "https://graph.facebook.com/{$graphV}/{$post->facebook_post_id}/insights",
        [
            'metric'       => 'post_impressions,post_impressions_unique,post_reactions_like_total,post_clicks,post_engaged_users',
            'access_token' => $token,
        ]
    );
    echo "HTTP {$res->status()}\n";
    echo $res->body() . "\n\n";
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
