<?php
$apiUrl = 'http://100.54.40.185:8080';
$apiKey = 'SenhaForteVivensi2026@!';
$instanceName = 'testinstance2026';

function evo($method, $url, $apiKey, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
    if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp; // raw response
}

// Poll for QR code 20 times, 1 second apart
echo "Polling /instance/connect/$instanceName for QR Code...\n";
for ($i = 1; $i <= 20; $i++) {
    $raw = evo('GET', "$apiUrl/instance/connect/$instanceName", $apiKey);
    $data = json_decode($raw, true);
    echo "Attempt $i: keys=" . implode(',', array_keys($data ?? []));
    
    // Check all possible QR fields
    if (!empty($data['base64'])) { echo " => FOUND base64!\n"; echo substr($data['base64'], 0, 100) . "\n"; break; }
    if (!empty($data['code'])) { echo " => FOUND code!\n"; echo substr($data['code'], 0, 100) . "\n"; break; }
    if (!empty($data['qrcode'])) { echo " => FOUND qrcode: " . json_encode($data['qrcode']) . "\n"; break; }
    if (!empty($data['pairingCode'])) { echo " => FOUND pairingCode!\n"; break; }
    echo " | raw=" . substr($raw, 0, 80) . "\n";
    sleep(1);
}
echo "\nDone.\n";
