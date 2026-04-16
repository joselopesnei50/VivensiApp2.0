<?php
/**
 * Vivensi — GitHub Webhook Receiver
 * URL: https://vivensi.app.br/deploy-webhook.php
 *
 * Configure no GitHub:
 *   Settings → Webhooks → Add webhook
 *   Payload URL: https://vivensi.app.br/deploy-webhook.php
 *   Content type: application/json
 *   Secret: (mesmo valor de DEPLOY_WEBHOOK_SECRET no .env)
 *   Events: Just the push event → branch: main
 */

// --- Configuração ---
$secret = getenv('DEPLOY_WEBHOOK_SECRET');
if (empty($secret)) {
    respond(500, 'Webhook secret not configured');
}
$deployScript = '/var/www/vivensi/scripts/deploy.sh';
$logFile      = '/var/www/vivensi/storage/logs/webhook.log';
$branch       = 'refs/heads/main';

// --- Helpers ---
function respond(int $code, string $message, array $data = []): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['message' => $message, 'ts' => date('c')], $data));
    exit;
}

function logWebhook(string $msg, string $file): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

// --- Apenas POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Method Not Allowed');
}

// --- Ler payload ---
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// --- Validar assinatura HMAC ---
if (empty($signature)) {
    logWebhook('REJECTED — assinatura ausente', $logFile);
    respond(403, 'Forbidden: Missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (! hash_equals($expected, $signature)) {
    logWebhook('REJECTED — assinatura inválida', $logFile);
    respond(403, 'Forbidden: Invalid signature');
}

// --- Parsear JSON ---
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respond(400, 'Bad Request: Invalid JSON');
}

// --- Verificar se é o branch main ---
$ref = $data['ref'] ?? '';
if ($ref !== $branch) {
    logWebhook("SKIPPED — branch ignorado: {$ref}", $logFile);
    respond(200, 'Ignored: Not main branch', ['ref' => $ref]);
}

// --- Extrair info do commit ---
$pusher   = $data['pusher']['name'] ?? 'unknown';
$commitMsg = $data['head_commit']['message'] ?? 'no message';
$commitId  = substr($data['head_commit']['id'] ?? '', 0, 7);

logWebhook("DEPLOY iniciado — [{$commitId}] {$commitMsg} by {$pusher}", $logFile);

// --- Executar script de deploy em background ---
if (! file_exists($deployScript)) {
    logWebhook('ERRO — deploy.sh não encontrado: ' . $deployScript, $logFile);
    respond(500, 'Deploy script not found');
}

$cmd    = "bash {$deployScript} >> {$logFile} 2>&1 &";
$output = shell_exec($cmd);

logWebhook("DEPLOY disparado em background (commit: {$commitId})", $logFile);

respond(200, 'Deploy started', [
    'commit'  => $commitId,
    'message' => $commitMsg,
    'pusher'  => $pusher,
]);
