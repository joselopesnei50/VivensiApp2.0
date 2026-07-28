<?php
/**
 * Verifica e (se necessário) inscreve o app NC5HUBDIGITAL-EMP na WABA da
 * instance 32, pra Meta começar a entregar webhooks (messages, statuses).
 *
 * Uso:
 *   sudo -u www-data php scripts/whatsapp-cloud-subscribe.php          # só lê
 *   sudo -u www-data php scripts/whatsapp-cloud-subscribe.php --apply  # inscreve
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$INSTANCE_ID = 32;
$APPLY       = in_array('--apply', $argv, true);

$inst = \App\Models\WhatsappInstance::withoutGlobalScope('tenant')->find($INSTANCE_ID);
if (!$inst) {
    echo "### Instance {$INSTANCE_ID} nao encontrada\n";
    exit(1);
}

$wabaId = $inst->waba_id;
$token  = $inst->graph_access_token;

if (!$wabaId || !$token) {
    echo "### Instance sem waba_id ou access_token\n";
    exit(1);
}

echo "WABA: {$wabaId}\n";
echo "Token: " . substr($token, 0, 12) . '...' . substr($token, -6) . "\n\n";

// ─── 1. Lista apps subscribed nessa WABA ────────────────────────────────────
echo "======= GET /{$wabaId}/subscribed_apps =======\n";
$res = Http::withToken($token)->get("https://graph.facebook.com/v22.0/{$wabaId}/subscribed_apps");
echo 'HTTP ' . $res->status() . "\n";
echo $res->body() . "\n\n";

if (!$res->successful()) {
    echo "### Falhou a consulta. Se erro for 190 (token invalido), token vencido ou WABA errada.\n";
    exit(1);
}

$apps = $res->json('data', []);
$expectedAppId = \App\Models\SystemSetting::getValue('meta_cloud_app_id', '');
$found = false;
foreach ($apps as $a) {
    $appId = $a['whatsapp_business_api_data']['id'] ?? $a['id'] ?? null;
    if ((string) $appId === (string) $expectedAppId) {
        $found = true;
    }
    echo "  app inscrito: " . json_encode($a) . "\n";
}

if (empty($apps)) {
    echo "  (nenhum app inscrito nessa WABA)\n";
}

echo "\n";

// ─── 2. Se não estiver inscrito, inscreve ───────────────────────────────────
if ($found) {
    echo "✅ App {$expectedAppId} JA esta inscrito. Webhook devia estar chegando.\n";
    echo "   Se ainda nao chega mensagem: verifique se o field 'messages' esta\n";
    echo "   marcado no webhook do APP (painel Meta -> WhatsApp -> Configuration).\n";
    exit(0);
}

echo "❌ App {$expectedAppId} NAO esta inscrito nessa WABA.\n";

if (!$APPLY) {
    echo "\nRode novamente com --apply pra inscrever:\n";
    echo "   sudo -u www-data php scripts/whatsapp-cloud-subscribe.php --apply\n";
    exit(0);
}

echo "\n======= POST /{$wabaId}/subscribed_apps (--apply) =======\n";
$res = Http::withToken($token)->post("https://graph.facebook.com/v22.0/{$wabaId}/subscribed_apps");
echo 'HTTP ' . $res->status() . "\n";
echo $res->body() . "\n\n";

if ($res->successful() && ($res->json('success') === true || $res->status() === 200)) {
    echo "✅ App inscrito com sucesso.\n";
    echo "   Manda uma mensagem pro +1 555 167-1404 do teu celular e roda de novo o\n";
    echo "   whatsapp-cloud-diag.php pra ver se aparece.\n";
} else {
    echo "### Falhou a inscricao. Erro provavel:\n";
    echo "   - Token sem escopo whatsapp_business_management (mas ambos ja foram aprovados)\n";
    echo "   - WABA em estado invalido\n";
    exit(1);
}
