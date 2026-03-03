<?php
$apiUrl = 'http://100.54.40.185:8080';
$apiKey = 'SenhaForteVivensi2026@!';
$instanceName = 'qrtest001';

function evo($method, $url, $apiKey, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    return curl_exec($ch);
}

// List current instances
$list = json_decode(evo('GET', "$apiUrl/instance/fetchInstances", $apiKey), true);
echo "=== CURRENT INSTANCES ===\n";
foreach ($list as $i) {
    echo " - " . $i['name'] . " | status=" . $i['connectionStatus'] . "\n";
}

// Delete if test instance exists
evo('DELETE', "$apiUrl/instance/delete/$instanceName", $apiKey);
sleep(1);

// Create fresh instance
$create = json_decode(evo('POST', "$apiUrl/instance/create", $apiKey, [
    'instanceName' => $instanceName,
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS',
]), true);
echo "\n=== CREATE RESPONSE ===\n";
echo "status: " . ($create['instance']['status'] ?? 'unknown') . "\n";
echo "qrcode: " . json_encode($create['qrcode'] ?? 'none') . "\n";

// Poll for QR
echo "\n=== POLLING FOR QR CODE ===\n";
for ($i = 1; $i <= 15; $i++) {
    sleep(2);
    $raw = evo('GET', "$apiUrl/instance/connect/$instanceName", $apiKey);
    $data = json_decode($raw, true);
    $keys = implode(',', array_keys($data ?? []));
    
    if (!empty($data['base64'])) { echo "[$i] QR FOUND in base64! Length: " . strlen($data['base64']) . "\n"; break; }
    if (!empty($data['code'])) { echo "[$i] QR FOUND in code! " . substr($data['code'], 0, 50) . "\n"; break; }
    if (!empty($data['pairingCode'])) { echo "[$i] FOUND pairingCode!\n"; break; }
    echo "[$i] keys=$keys | " . substr($raw, 0, 60) . "\n";
}
