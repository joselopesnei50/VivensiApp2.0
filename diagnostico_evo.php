<?php

/**
 * Script de Diagnóstico Independente - Evolution API
 * Rode via terminal: php diagnostico_evo.php
 */

$url = 'https://evo.vivensi.app.br';
$apiKey = 'e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e';

echo "--- INICIANDO DIAGNÓSTICO DE REDE ---\n";
echo "Alvo: $url\n\n";

// 1. Teste de DNS
echo "[1/3] Testando DNS...\n";
$host = parse_url($url, PHP_URL_HOST);
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "❌ FALHA: Não foi possível resolver o DNS para $host\n";
} else {
    echo "✅ SUCESSO: $host resolvido para o IP $ip\n";
}

echo "\n";

// 2. Teste de Conexão Bruta (CURL com SSL habilitado)
echo "[2/3] Testando Conexão HTTPS (com validação SSL)...\n";
$ch = curl_init("$url/instance/connectionState/teste");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ ERRO DE CONEXÃO: $error\n";
    echo "Sugestão: Verifique se o Firewall da AWS permite saída para o IP $ip na porta 443.\n";
} else {
    echo "✅ RESPOSTA RECEBIDA: HTTP $httpCode\n";
    echo "Corpo da resposta: $response\n";
}

curl_close($ch);
echo "\n";

// 3. Teste de Conexão Sem SSL (Bypass)
echo "[3/3] Testando Conexão HTTPS (IGNORANDO SSL)...\n";
$ch = curl_init("$url/instance/connectionState/teste");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ ERRO NO BYPASS: $error\n";
} else {
    echo "✅ SUCESSO NO BYPASS: HTTP $httpCode\n";
    if ($httpCode === 401 || $httpCode === 403) {
        echo "⚠️  AVISO: A conexão funciona, mas a GLOBAL_KEY parece INVÁLIDA.\n";
    }
}

curl_close($ch);

echo "\n--- FIM DO DIAGNÓSTICO ---\n";
