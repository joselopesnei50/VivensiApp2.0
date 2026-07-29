<?php
/**
 * Diagnóstico do OAuth Facebook Login pro Vivensi.
 *
 * Uso:
 *   sudo -u www-data php /var/www/vivensi/scripts/social-oauth-diag.php
 *
 * Cobre:
 *  - SystemSettings meta_social_*
 *  - MetaSocialAuthService::isConfigured
 *  - Últimas 15 linhas de log com "OAuth", "social" ou "MetaSocial"
 *  - Contas conectadas em social_accounts
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo str_repeat('=', 70) . PHP_EOL;
echo ' 1) SystemSettings meta_social_*' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

foreach (['meta_social_app_id', 'meta_social_app_secret'] as $k) {
    $v = \App\Models\SystemSetting::getValue($k, '');
    printf("  %-25s -> %s\n", $k, $v ? 'OK (' . strlen($v) . ' chars)' : '### VAZIO ###');
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 2) MetaSocialAuthService::isConfigured' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$svc = app(\App\Services\MetaSocialAuthService::class);
echo '  isConfigured: ' . ($svc->isConfigured() ? 'true' : '### FALSE ###') . PHP_EOL;
echo '  redirect URI: ' . route('social.facebook.callback') . PHP_EOL;

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 3) Últimos 15 registros de log com "social" ou "OAuth" ou "MetaSocial"' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $matches = [];
    foreach (array_reverse($lines) as $line) {
        $needle = strtolower($line);
        if (str_contains($needle, 'social') || str_contains($needle, 'oauth') || str_contains($needle, 'metasocial') || str_contains($needle, 'facebook')) {
            $matches[] = $line;
            if (count($matches) >= 15) break;
        }
    }
    if (empty($matches)) {
        echo "  (nenhuma linha relevante no log de hoje)\n";
    } else {
        foreach (array_reverse($matches) as $m) {
            echo '  ' . mb_substr($m, 0, 250) . PHP_EOL;
        }
    }
} else {
    echo "  (log de hoje ainda nao criado: $logFile)\n";
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 4) social_accounts (total geral, ignorando tenant scope)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$count = \App\Models\SocialAccount::withoutGlobalScopes()->count();
echo "  Total: {$count}\n";

if ($count > 0) {
    \App\Models\SocialAccount::withoutGlobalScopes()->latest()->limit(3)->get(['id', 'tenant_id', 'page_id', 'page_name', 'is_active', 'created_at'])
        ->each(function ($a) {
            printf("  #%d | tenant=%d | page=%s (%s) | active=%s | %s\n",
                $a->id, $a->tenant_id, $a->page_name, $a->page_id,
                $a->is_active ? 'yes' : 'no', $a->created_at);
        });
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' Diagnostico completo.' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
