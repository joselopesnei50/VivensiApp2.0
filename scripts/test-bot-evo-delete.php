<?php
/**
 * Diagnostico expandido dos endpoints da Evolution API v2.3.6 pra encontrar
 * uma forma de apagar mensagens inbound do storage local (sem depender do
 * REVOKE do WhatsApp, que so funciona pra mensagens do proprio remetente).
 *
 * Rodar: php scripts/test-bot-evo-delete.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key      = config('whatsapp.evolution_global_key');
$baseUrl  = rtrim(config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br')), '/');
$instance = 'VIVENSI-BOT';

function hit(string $verb, string $url, array $headers, ?array $body = null): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $verb,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 8,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => mb_substr((string) $resp, 0, 500)];
}

$h = ['apikey: ' . $key, 'Content-Type: application/json'];

// PARTE 1: Listar rotas / instancias / settings
echo "=== SETTINGS DA INSTANCIA ===\n";
$r = hit('GET', "{$baseUrl}/settings/find/{$instance}", $h);
echo "HTTP {$r['code']}: {$r['body']}\n\n";

// PARTE 2: Endpoints de delete alternativos
$fakeKey = [
    'id'        => 'TEST_DIAG_' . time(),
    'remoteJid' => '5511999999999@s.whatsapp.net',
    'fromMe'    => false,
];

$attempts = [
    ['DELETE', "/chat/deleteMessageForEveryone/{$instance}", $fakeKey, 'delete-for-everyone (REVOKE - so mensagens proprias)'],
    ['DELETE', "/chat/deleteMessageForMe/{$instance}",       $fakeKey, 'delete-for-me (candidato ideal - local)'],
    ['POST',   "/chat/deleteMessageForMe/{$instance}",       $fakeKey, ''],
    ['DELETE', "/chat/{$instance}/5511999999999@s.whatsapp.net", null, 'delete chat inteiro'],
    ['POST',   "/chat/deleteChat/{$instance}", ['remoteJid' => '5511999999999@s.whatsapp.net'], ''],
    ['DELETE', "/chat/deleteChat/{$instance}", ['remoteJid' => '5511999999999@s.whatsapp.net'], ''],
    ['POST',   "/chat/archiveChat/{$instance}", ['chat' => '5511999999999@s.whatsapp.net', 'archive' => true], 'arquivar (esconde da lista)'],
    ['POST',   "/message/delete/{$instance}",   $fakeKey, ''],
    ['GET',    "/chat/findChats/{$instance}",   $h, 'listar chats disponiveis'],
];

foreach ($attempts as [$verb, $path, $bodyOrHeaders, $note]) {
    $url = $baseUrl . $path;
    echo "=== {$verb} {$path}" . ($note ? " ({$note})" : '') . " ===\n";
    // Se o parametro for headers (GET), usa direto; senao body normal
    if ($verb === 'GET' && is_array($bodyOrHeaders) && isset($bodyOrHeaders[0])) {
        $r = hit($verb, $url, $bodyOrHeaders);
    } else {
        $r = hit($verb, $url, $h, is_array($bodyOrHeaders) ? $bodyOrHeaders : null);
    }
    echo "HTTP {$r['code']}: {$r['body']}\n\n";
}
