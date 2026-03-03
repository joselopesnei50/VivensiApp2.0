<?php
/**
 * Diagnóstico: Gerar Pairing Code via Evolution API
 * 
 * Acesse: http://localhost/vivensi-laravel/public/../test_pairing.php?phone=SEU_NUMERO
 * Ex: http://localhost/vivensi-laravel/test_pairing.php?phone=11999999999
 */

$apiUrl  = 'http://100.54.40.185:8080';
$apiKey  = 'SenhaForteVivensi2026@!';
$instance = $_GET['instance'] ?? 'testecoletivo'; // passa ?instance=NOME_DA_INSTANCIA na URL

// Pega o telefone da URL ou usa um padrão para teste
$phoneRaw = $_GET['phone'] ?? '11999999999';
$phone    = preg_replace('/\D/', '', $phoneRaw);

// Adiciona DDI 55 se não tiver
if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
    $phone = '55' . $phone;
}

echo "<h2>Diagnóstico Pairing Code - Evolution API</h2>";
echo "<p><b>Número enviado para API:</b> <code>{$phone}</code> (original: {$phoneRaw})</p>";
echo "<p><b>Instância:</b> <code>{$instance}</code></p>";

// --- 1. Estado da instância ---
echo "<h3>1. Estado da Instância</h3>";
$ch = curl_init("{$apiUrl}/instance/connectionState/{$instance}");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["apikey: {$apiKey}"]]);
$state = json_decode(curl_exec($ch), true);
curl_close($ch);
echo "<pre>" . json_encode($state, JSON_PRETTY_PRINT) . "</pre>";

$currentState = $state['instance']['state'] ?? 'unknown';
echo "<p><b>Estado:</b> <code>{$currentState}</code></p>";

if ($currentState === 'open') {
    echo "<p style='color:green;'><b>✅ Instância JÁ CONECTADA! Não é possível gerar pairing code.</b></p>";
    exit;
}

if ($currentState !== 'connecting') {
    // Precisa conectar primeiro
    echo "<h3>1b. Conectando instância (iniciando)...</h3>";
    $ch = curl_init("{$apiUrl}/instance/connect/{$instance}");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["apikey: {$apiKey}"]]);
    $conn = curl_exec($ch);
    curl_close($ch);
    echo "<pre>{$conn}</pre>";
    sleep(2);
}

// --- 2. Solicitar Pairing Code ---
echo "<h3>2. Solicitando Pairing Code (POST /instance/pairingCode/{$instance})</h3>";
$body = json_encode(['number' => $phone]);

$ch = curl_init("{$apiUrl}/instance/pairingCode/{$instance}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        "apikey: {$apiKey}",
        "Content-Type: application/json",
        "Content-Length: " . strlen($body),
    ],
]);
$rawResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><b>HTTP Status:</b> <code>{$httpCode}</code></p>";
echo "<p><b>Resposta bruta:</b></p>";
echo "<pre>" . htmlspecialchars($rawResponse) . "</pre>";

$decoded = json_decode($rawResponse, true);
if (isset($decoded['pairingCode'])) {
    echo "<h2 style='color:green;'>✅ Pairing Code: <b style='font-size:2rem;letter-spacing:8px;'>" . $decoded['pairingCode'] . "</b></h2>";
} elseif (isset($decoded['code'])) {
    echo "<h2 style='color:orange;'>⚠️ Campo 'code' encontrado: " . substr($decoded['code'], 0, 50) . "...</h2>";
} else {
    echo "<p style='color:red;'><b>❌ Nenhum código de pareamento encontrado na resposta.</b></p>";
}
