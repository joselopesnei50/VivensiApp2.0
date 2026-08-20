<?php
/**
 * Script de diagnostico do endpoint de delete da Evolution API.
 * Usado pra descobrir qual HTTP verb / path aceito pela versao rodando no VPS.
 * Rodar: php scripts/test-bot-evo-delete.php
 * Nao envia dado real — usa id "TEST_DIAG" que nao existe na conversa.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key      = config('whatsapp.evolution_global_key');
$baseUrl  = rtrim(config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br')), '/');
$instance = 'VIVENSI-BOT';
$body     = [
    'id'        => 'TEST_DIAG_' . time(),
    'remoteJid' => '5511999999999@s.whatsapp.net',
    'fromMe'    => false,
];

$attempts = [
    ['POST',   "{$baseUrl}/chat/deleteMessageForEveryone/{$instance}"],
    ['DELETE', "{$baseUrl}/chat/deleteMessageForEveryone/{$instance}"],
    ['POST',   "{$baseUrl}/chat/deleteMessage/{$instance}"],
    ['DELETE', "{$baseUrl}/chat/deleteMessage/{$instance}"],
    ['POST',   "{$baseUrl}/message/delete/{$instance}"],
];

foreach ($attempts as [$verb, $url]) {
    echo str_repeat('=', 60) . "\n";
    echo "{$verb} {$url}\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $verb,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . $key,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS    => json_encode($body),
        CURLOPT_TIMEOUT       => 8,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "HTTP: {$code}\n";
    if ($err) echo "curl err: {$err}\n";
    echo "body: " . mb_substr((string) $resp, 0, 400) . "\n\n";
}
