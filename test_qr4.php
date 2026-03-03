<?php
$apiUrl = 'http://100.54.40.185:8080';
$apiKey = 'SenhaForteVivensi2026@!';
$instanceName = 'vivensitest999';

function evo($method, $url, $apiKey, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    return curl_exec($ch);
}

// Delete if exists
$del = evo('DELETE', "$apiUrl/instance/delete/$instanceName", $apiKey);
echo "DELETE: $del\n\n";
sleep(1);

// Create fresh
$create = evo('POST', "$apiUrl/instance/create", $apiKey, [
    'instanceName' => $instanceName,
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS',
]);
$createData = json_decode($create, true);
echo "CREATE: " . json_encode($createData, JSON_PRETTY_PRINT) . "\n\n";

// Check if QR already in create response
if (!empty($createData['qrcode'])) {
    echo "QR in create response: " . json_encode($createData['qrcode']) . "\n";
}

// Poll for QR
echo "Polling for QR code (20 attempts, 2s each)...\n";
for ($i = 1; $i <= 20; $i++) {
    sleep(2);
    $raw = evo('GET', "$apiUrl/instance/connect/$instanceName", $apiKey);
    $data = json_decode($raw, true);
    echo "Attempt $i: " . $raw . "\n";
    
    if (!empty($data['base64']) || !empty($data['code']) || !empty($data['pairingCode'])) {
        echo "\n=== QR FOUND at attempt $i ===\n";
        break;
    }
}
