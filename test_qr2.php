<?php
$apiUrl = 'http://100.54.40.185:8080';
$apiKey = 'SenhaForteVivensi2026@!';

function evo($method, $url, $apiKey, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['apikey: ' . $apiKey, 'Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// List all instances
$instances = evo('GET', "$apiUrl/instance/fetchInstances", $apiKey);
echo "=== ALL INSTANCES ===\n";
foreach ($instances as $i) {
    echo " - " . $i['name'] . " status=" . $i['connectionStatus'] . "\n";
}

// Try getting QR for testinstance2026 after a short sleep
$instanceName = 'testinstance2026';
echo "\n=== LOGOUT $instanceName ===\n";
$logout = evo('DELETE', "$apiUrl/instance/logout/$instanceName", $apiKey);
echo json_encode($logout) . "\n";

sleep(2);

echo "\n=== CONNECT (get QR) after logout ===\n";
$connect = evo('GET', "$apiUrl/instance/connect/$instanceName", $apiKey);
echo json_encode($connect, JSON_PRETTY_PRINT) . "\n";

// Check if QR is in a nested field
if (isset($connect['base64'])) echo "\nFOUND QR in connect.base64!\n";
elseif (isset($connect['code'])) echo "\nFOUND QR in connect.code!\n";
elseif (isset($connect['qrcode'])) echo "\nFOUND QR in connect.qrcode: " . json_encode($connect['qrcode']) . "\n";
else echo "\nNO QR FOUND in response keys: " . implode(', ', array_keys($connect ?? [])) . "\n";
