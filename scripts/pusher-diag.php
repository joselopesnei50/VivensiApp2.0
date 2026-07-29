<?php
/**
 * Diagnóstico do Pusher / broadcasting.
 *
 * Uso:
 *   sudo -u www-data php /var/www/vivensi/scripts/pusher-diag.php
 *
 * Cobre:
 *  - SystemSettings Pusher (dinâmico via /admin/settings)
 *  - Config runtime do broadcasting.pusher (o que o Laravel realmente vê)
 *  - Envio de evento de teste pro canal público (broadcasts.test)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo str_repeat('=', 70) . PHP_EOL;
echo ' 1) SystemSettings Pusher (dinâmicos)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$candidates = [
    'pusher_app_id', 'pusher_app_key', 'pusher_app_secret', 'pusher_app_cluster',
    'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
];
foreach ($candidates as $k) {
    $v = \App\Models\SystemSetting::getValue($k, '');
    printf("  %-22s -> %s\n", $k, $v ? 'OK (' . strlen($v) . ' chars)' : '# não definido');
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 2) Config runtime (o que o Laravel realmente vê)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
$cfg = config('broadcasting.connections.pusher');
$show = fn ($v) => $v ? 'OK (' . strlen((string) $v) . ' chars)' : '# vazio';
printf("  key                : %s\n", $show($cfg['key'] ?? ''));
printf("  secret             : %s\n", $show($cfg['secret'] ?? ''));
printf("  app_id             : %s\n", $show($cfg['app_id'] ?? ''));
printf("  options.cluster    : %s\n", $cfg['options']['cluster'] ?? '(vazio)');
printf("  options.host       : %s\n", $cfg['options']['host']    ?? '(vazio)');
printf("  options.scheme     : %s\n", $cfg['options']['scheme']  ?? '(vazio)');
printf("  BROADCAST_DRIVER   : %s\n", config('broadcasting.default'));

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 3) Envio de evento de teste' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

try {
    $bcast = app(\Illuminate\Contracts\Broadcasting\Broadcaster::class);
    // Broadcast direto num canal público de teste — não precisa fazer autorização
    // e serve pra confirmar que o servidor Pusher responde.
    broadcast(new \App\Events\WhatsappMessageReceived(
        new \App\Models\WhatsappMessage(['content' => '[DIAG TEST] ' . date('H:i:s'), 'type' => 'text']),
        new \App\Models\WhatsappChat(['tenant_id' => 16, 'wa_id' => '000000', 'contact_name' => 'DIAG', 'id' => 0])
    ));
    echo "  ✓ Evento disparado sem exceção. Se Pusher está OK, foi entregue.\n";
    echo "    Verifique em https://dashboard.pusher.com → Debug Console do seu app.\n";
} catch (\Throwable $e) {
    echo "  ✗ Falha ao broadcast:\n";
    echo "    " . $e->getMessage() . "\n";
    echo "    (Se erro for 'invalid credentials' ou similar, cadastre em /admin/settings)\n";
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 4) Ultimas linhas de log com "Pusher" ou "Broadcast"' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $matches = [];
    foreach (array_reverse($lines) as $line) {
        $l = strtolower($line);
        if (str_contains($l, 'pusher') || str_contains($l, 'broadcast') || str_contains($l, 'whatsappmessagereceived')) {
            $matches[] = $line;
            if (count($matches) >= 10) break;
        }
    }
    if (empty($matches)) echo "  (nenhuma linha relevante no log de hoje)\n";
    else foreach (array_reverse($matches) as $m) echo '  ' . mb_substr($m, 0, 220) . PHP_EOL;
} else {
    echo "  (log de hoje ainda não criado)\n";
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' Diagnostico completo.' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
