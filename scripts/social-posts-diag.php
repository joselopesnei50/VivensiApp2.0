<?php
/**
 * Diagnóstico do worker de publicação agendada.
 *
 * Uso:
 *   sudo -u www-data php /var/www/vivensi/scripts/social-posts-diag.php
 *
 * Mostra:
 *  - Posts agendados vencidos (deveriam estar publicados)
 *  - Posts agendados futuros próximos (5 mais próximos)
 *  - Jobs falhados na queue relacionados
 *  - Últimos 5 posts publicados/falhos
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo str_repeat('=', 70) . PHP_EOL;
echo ' 1) Posts agendados VENCIDOS (scheduled_at <= agora, status=scheduled)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$overdue = \App\Models\ScheduledPost::withoutGlobalScopes()
    ->where('status', 'scheduled')
    ->where('scheduled_at', '<=', now())
    ->orderBy('scheduled_at')
    ->limit(10)
    ->get(['id', 'tenant_id', 'platform', 'scheduled_at', 'caption']);

if ($overdue->isEmpty()) {
    echo "  ✓ Nenhum post vencido esperando publicação.\n";
} else {
    printf("  ⚠ %d post(s) VENCIDO(s) esperando processar. Se scheduler+worker OK,\n", $overdue->count());
    echo "    devem sair no próximo ciclo de 5min.\n\n";
    foreach ($overdue as $p) {
        printf("  #%d | tenant=%d | %s | %s | %s\n",
            $p->id, $p->tenant_id, $p->platform,
            $p->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m H:i'),
            mb_substr($p->caption, 0, 50));
    }
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 2) Próximos 5 posts agendados (futuro)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$upcoming = \App\Models\ScheduledPost::withoutGlobalScopes()
    ->where('status', 'scheduled')
    ->where('scheduled_at', '>', now())
    ->orderBy('scheduled_at')
    ->limit(5)
    ->get(['id', 'tenant_id', 'platform', 'scheduled_at', 'caption']);

if ($upcoming->isEmpty()) {
    echo "  (nenhum post agendado pro futuro)\n";
} else {
    foreach ($upcoming as $p) {
        $inMin = now()->diffInMinutes($p->scheduled_at, false);
        printf("  #%d | tenant=%d | %s | %s (em %d min) | %s\n",
            $p->id, $p->tenant_id, $p->platform,
            $p->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m H:i'),
            $inMin,
            mb_substr($p->caption, 0, 50));
    }
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 3) Últimos 5 posts com resultado (published/failed)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$recent = \App\Models\ScheduledPost::withoutGlobalScopes()
    ->whereIn('status', ['published', 'failed'])
    ->orderByDesc('updated_at')
    ->limit(5)
    ->get(['id', 'tenant_id', 'status', 'platform', 'scheduled_at', 'error_message', 'facebook_post_id', 'instagram_post_id']);

if ($recent->isEmpty()) {
    echo "  (ainda nenhum post foi publicado nem falhou)\n";
} else {
    foreach ($recent as $p) {
        $badge = $p->status === 'published' ? '✅' : '❌';
        printf("  %s #%d | %s | %s\n", $badge, $p->id, $p->platform,
            $p->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m H:i'));
        if ($p->status === 'published') {
            if ($p->facebook_post_id) echo "     FB post: {$p->facebook_post_id}\n";
            if ($p->instagram_post_id) echo "     IG post: {$p->instagram_post_id}\n";
        }
        if ($p->status === 'failed' && $p->error_message) {
            echo "     Erro: " . mb_substr($p->error_message, 0, 100) . "\n";
        }
    }
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 4) Jobs falhados na queue (últimos 5 com "PublishScheduledPost")' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

try {
    $failed = \DB::table('failed_jobs')
        ->where('payload', 'like', '%PublishScheduledPostJob%')
        ->orderByDesc('failed_at')
        ->limit(5)
        ->get(['id', 'failed_at', 'exception']);

    if ($failed->isEmpty()) {
        echo "  ✓ Nenhum job de PublishScheduledPost falhado.\n";
    } else {
        foreach ($failed as $f) {
            $firstLine = strtok($f->exception, "\n");
            printf("  ❌ %s\n     %s\n", $f->failed_at, mb_substr($firstLine, 0, 200));
        }
    }
} catch (\Throwable $e) {
    echo "  (tabela failed_jobs indisponível: " . $e->getMessage() . ")\n";
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' Diagnostico completo.' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
echo PHP_EOL;
echo 'Pra forçar publicação agora (bypass do cron de 5min):' . PHP_EOL;
echo '  sudo -u www-data php artisan posts:publish' . PHP_EOL;
