<?php
/**
 * WhatsApp Diagnostic Script - DELETE AFTER USE
 * Access: https://vivensi.app.br/diag_wa.php?key=VivensiDiag2026
 */
if (($_GET['key'] ?? '') !== 'VivensiDiag2026') {
    http_response_code(403); die('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace; font-size:13px; padding:20px;'>";
echo "=== WhatsApp Diagnostic ===\n\n";

// 1. Check WhatsappConfig
$configs = \App\Models\WhatsappConfig::all();
echo "WhatsappConfigs found: " . $configs->count() . "\n";
foreach ($configs as $c) {
    echo "  - ID:{$c->id} tenant:{$c->tenant_id} ai_enabled:" . ($c->ai_enabled ? 'YES' : 'NO') .
         " outbound_enabled:" . ($c->outbound_enabled ? 'YES' : 'NO') .
         " instance_name:" . (strlen($c->evolution_instance_name ?? '') > 0 ? $c->evolution_instance_name : '(from tenant)') . "\n";
}

echo "\n";

// 2. Check Tenant evolution fields
$tenants = \App\Models\Tenant::whereNotNull('evolution_instance_name')->orWhere('evolution_instance_name', '!=', '')->get();
echo "Tenants with evolution_instance_name:\n";
foreach ($tenants as $t) {
    echo "  - ID:{$t->id} name:{$t->name} instance:{$t->evolution_instance_name} token_set:" . (!empty($t->evolution_instance_token) ? 'YES' : 'NO') . "\n";
}

// Also show first tenant
$firstTenant = \App\Models\Tenant::first();
if ($firstTenant) {
    echo "\nFirst tenant: id={$firstTenant->id} instance_name='{$firstTenant->evolution_instance_name}' token='" . substr($firstTenant->evolution_instance_token ?? '', 0, 20) . "...'\n";
}

echo "\n";

// 3. Test Evolution API sendText directly
$baseUrl = env('EVOLUTION_API_URL');
$globalKey = env('EVOLUTION_GLOBAL_KEY');

echo "Evolution API URL: $baseUrl\n";
echo "Global key set: " . (!empty($globalKey) ? 'YES' : 'NO') . "\n\n";

// Fetch instances
$ch = curl_init("{$baseUrl}/instance/fetchInstances");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["apikey: $globalKey"], CURLOPT_TIMEOUT => 5]);
$instResult = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Instances:\n";
foreach ((array)$instResult as $inst) {
    echo "  - " . ($inst['name'] ?? '?') . " status:" . ($inst['connectionStatus'] ?? '?') . "\n";
}

echo "\n";

// 4. Test send a message if GET param provided
if (!empty($_GET['to']) && !empty($_GET['instance'])) {
    $to = $_GET['to'];
    $instance = $_GET['instance'];
    $msg = $_GET['msg'] ?? 'Teste de envio via Vivensi Diagnostic';
    
    echo "Testing sendText to: $to via instance: $instance\n";
    
    $payload = json_encode(['number' => $to, 'textMessage' => ['text' => $msg]]);
    $ch2 = curl_init("{$baseUrl}/message/sendText/{$instance}");
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ["apikey: $globalKey", "Content-Type: application/json"],
        CURLOPT_TIMEOUT => 15,
    ]);
    $sendResult = curl_exec($ch2);
    $sendHttp = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    echo "HTTP Status: $sendHttp\n";
    echo "Response: " . $sendResult . "\n";
}

// 5. Check last log errors
echo "\n=== Last WhatsApp errors in Laravel log ===\n";
$logFile = base_path('storage/logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $waLines = array_filter($lines, fn($l) => stripos($l, 'whatsapp') !== false || stripos($l, 'evolution') !== false);
    $last = array_slice($waLines, -30);
    echo implode('', $last);
} else {
    echo "Log file not found.\n";
}

echo "\n=== END ===\n</pre>";
echo "<p>To test send: <a href='?key=VivensiDiag2026&to=5511999999999&instance=vivensiteste&msg=Teste'>?to=5516XXXXXXX&instance=vivensiteste&msg=Teste</a></p>";
