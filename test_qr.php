<?php
$apiUrl = 'http://100.54.40.185:8080';
$apiKey = 'SenhaForteVivensi2026@!';
$instanceName = 'testinstance2026';

function evoRequest($method, $url, $apiKey, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
    if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// 1. Create instance
$create = evoRequest('POST', "$apiUrl/instance/create", $apiKey, [
    'instanceName' => $instanceName,
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS',
]);
echo "=== CREATE ===\n";
echo json_encode($create, JSON_PRETTY_PRINT) . "\n";

// 2. Fetch QR
sleep(2);
$connect = evoRequest('GET', "$apiUrl/instance/connect/$instanceName", $apiKey);
echo "=== CONNECT (QR) ===\n";
echo json_encode($connect, JSON_PRETTY_PRINT) . "\n";

// 3. State
$state = evoRequest('GET', "$apiUrl/instance/connectionState/$instanceName", $apiKey);
echo "=== STATE ===\n";
echo json_encode($state, JSON_PRETTY_PRINT) . "\n";
