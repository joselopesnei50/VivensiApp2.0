<?php
/**
 * Diagnóstico do WhatsApp Cloud API — instance de teste (tenant 6, instance 32).
 *
 * Uso (no VPS, como www-data pra evitar Permission denied em storage/logs):
 *
 *   cd /var/www/vivensi && sudo -u www-data php scripts/whatsapp-cloud-diag.php
 *
 * Cobre:
 *  - SystemSettings do meta_cloud_* preenchidas
 *  - Instance 32 existe, provider='cloud_api', phone_number_id ok
 *  - Últimas 5 mensagens da instance (chats → messages)
 *  - Últimos 5 chats (com last_message_at)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$INSTANCE_ID = 32;
// $TENANT_ID é derivado dinamicamente da instance abaixo — a mesma
// instance pode ser movida entre tenants (foi realocada de 6 → 16).

echo str_repeat('=', 70) . PHP_EOL;
echo ' 1) SystemSettings meta_cloud_*' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

foreach (['meta_cloud_app_id', 'meta_cloud_app_secret', 'meta_cloud_verify_token', 'meta_cloud_config_id'] as $k) {
    $v = \App\Models\SystemSetting::getValue($k, '');
    printf("  %-27s -> %s\n", $k, $v ? 'OK (' . strlen($v) . ' chars)' : '### VAZIO ###');
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 2) WhatsappInstance ' . $INSTANCE_ID . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$inst = \App\Models\WhatsappInstance::withoutGlobalScope('tenant')->find($INSTANCE_ID);
if (!$inst) {
    echo "  ### INSTANCE {$INSTANCE_ID} NAO ENCONTRADA ###\n";
    exit(1);
}

printf("  id             : %d\n", $inst->id);
printf("  tenant_id      : %d\n", $inst->tenant_id);
printf("  provider       : %s\n", $inst->provider);
printf("  phone_number_id: %s\n", $inst->phone_number_id ?? '###vazio###');
printf("  waba_id        : %s\n", $inst->waba_id ?? '###vazio###');
printf("  has_token      : %s\n", $inst->graph_access_token ? 'sim' : 'NAO');

$TENANT_ID = (int) $inst->tenant_id;

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' 3) Chats do tenant ' . $TENANT_ID . ' (top 5 recentes)' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$chats = \App\Models\WhatsappChat::withoutGlobalScope('tenant')
    ->where('tenant_id', $TENANT_ID)
    ->orderByDesc('last_message_at')
    ->limit(5)
    ->get(['id', 'wa_id', 'contact_name', 'last_message_at', 'last_inbound_at']);

printf("  total: %d\n\n", $chats->count());
foreach ($chats as $c) {
    printf(
        "  chat #%d | wa_id=%s | name=%s\n     last_msg=%s | last_inbound=%s\n\n",
        $c->id,
        $c->wa_id,
        mb_substr($c->contact_name ?? '', 0, 30),
        $c->last_message_at ?? '(nunca)',
        $c->last_inbound_at ?? '(nunca)',
    );
}

echo str_repeat('=', 70) . PHP_EOL;
echo ' 4) Últimas 5 mensagens do tenant ' . $TENANT_ID . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

$chatIds = \App\Models\WhatsappChat::withoutGlobalScope('tenant')
    ->where('tenant_id', $TENANT_ID)
    ->pluck('id');

$msgs = \App\Models\WhatsappMessage::withoutGlobalScope('tenant')
    ->whereIn('chat_id', $chatIds)
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'chat_id', 'message_id', 'direction', 'type', 'content', 'status', 'created_at']);

printf("  total: %d\n\n", $msgs->count());
foreach ($msgs as $m) {
    printf(
        "  msg #%d | chat=%d | %s | %s | %s\n     status=%s | msg_id=%s\n     content=%s\n\n",
        $m->id,
        $m->chat_id,
        $m->direction,
        $m->type,
        $m->created_at,
        $m->status ?? '-',
        mb_substr($m->message_id ?? '', 0, 30),
        mb_substr($m->content ?? '', 0, 80),
    );
}

echo str_repeat('=', 70) . PHP_EOL;
echo ' 5) Últimos 5 registros de log do WhatsApp Cloud' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

// Grep leve nos últimos logs pra pegar handshakes e falhas do webhook.
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $matches = [];
    foreach (array_reverse($lines) as $line) {
        if (stripos($line, 'CloudApi') !== false || stripos($line, 'cloud-webhook') !== false || stripos($line, 'meta_cloud') !== false) {
            $matches[] = $line;
            if (count($matches) >= 5) break;
        }
    }
    if (empty($matches)) {
        echo "  (nenhuma linha CloudApi/webhook/meta_cloud no log de hoje)\n";
    } else {
        foreach (array_reverse($matches) as $m) {
            echo '  ' . mb_substr($m, 0, 200) . PHP_EOL;
        }
    }
} else {
    echo "  (log de hoje ainda nao criado: $logFile)\n";
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo ' Diagnostico completo.' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
