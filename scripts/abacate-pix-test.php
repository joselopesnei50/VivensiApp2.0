<?php
/**
 * Script de diagnóstico do fluxo /transparents/create da AbacatePay.
 *
 * Uso:  sudo -u www-data php scripts/abacate-pix-test.php
 *
 * O que faz:
 *  1. Lê API key do SystemSetting + verifica que não está vazia
 *  2. Testa endpoint de listagem (confirma auth) — deve dar 200
 *  3. Testa createPixCharge do AbacatePayService (nosso wrapper) — deve retornar array
 *  4. Testa POST cru pra /transparents/create (bypass do service) — deve dar 200 com brCode
 *  5. Compara os 2 caminhos e imprime diagnóstico
 *
 * Zero shell escape, tudo em PHP puro.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;
use App\Services\AbacatePayService;
use Illuminate\Support\Facades\Http;

echo str_repeat('=', 70) . "\n";
echo " AbacatePay PIX Test — diagnóstico completo\n";
echo str_repeat('=', 70) . "\n\n";

// ── 1. Config ─────────────────────────────────────────────────────
$apiKey = SystemSetting::getValue('abacatepay_api_key');
$env    = SystemSetting::getValue('abacatepay_environment') ?? 'sandbox';
$secret = SystemSetting::getValue('abacatepay_webhook_secret');

echo "1) CONFIG SystemSetting:\n";
echo "   env:            {$env}\n";
echo "   api_key:        " . ($apiKey ? substr($apiKey, 0, 10) . '...' . substr($apiKey, -4) . " (len " . strlen($apiKey) . ")" : "VAZIO") . "\n";
echo "   webhook_secret: " . ($secret ? 'setado (len ' . strlen($secret) . ')' : 'VAZIO') . "\n\n";

if (!$apiKey) {
    echo "❌ API key vazia. Configure em /admin/settings antes de continuar.\n";
    exit(1);
}

// ── 2. Auth check via /checkouts/list ─────────────────────────────
echo "2) AUTH CHECK — GET /checkouts/list\n";
try {
    $r = Http::withToken($apiKey)->timeout(15)->get('https://api.abacatepay.com/v2/checkouts/list');
    echo "   HTTP {$r->status()}\n";
    if (!$r->successful()) {
        echo "   BODY: " . substr($r->body(), 0, 300) . "\n\n";
        echo "❌ Auth falhou. Confira se a API key é de produção e está válida no painel Abacate.\n";
        exit(1);
    }
    echo "   ✅ OK\n\n";
} catch (\Throwable $e) {
    echo "   EXCEPTION: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 3. POST cru pra /transparents/create ──────────────────────────
echo "3) POST CRU — /transparents/create (bypass service)\n";
try {
    $r = Http::withToken($apiKey)->timeout(30)->post('https://api.abacatepay.com/v2/transparents/create', [
        'amount'      => 500, // R$ 5,00
        'description' => 'Teste diagnostico PIX',
        'expiresIn'   => 3600,
        'customer'    => [
            'name'  => 'Teste Diagnostico',
            'email' => 'diag@vivensi.app.br',
        ],
        'metadata'    => ['origin' => 'diagnostic_script'],
    ]);
    echo "   HTTP {$r->status()}\n";
    echo "   BODY:\n";
    echo "   " . str_replace("\n", "\n   ", $r->body()) . "\n\n";

    if ($r->successful()) {
        $data = $r->json('data');
        if ($data) {
            echo "   ✅ SUCESSO — resposta parseada:\n";
            echo "      id:           " . ($data['id'] ?? '(none)') . "\n";
            echo "      brCode:       " . (isset($data['brCode']) ? substr($data['brCode'], 0, 50) . '...' : '(none)') . "\n";
            echo "      brCodeBase64: " . (isset($data['brCodeBase64']) ? 'setado (len ' . strlen($data['brCodeBase64']) . ')' : '(none)') . "\n";
            echo "      status:       " . ($data['status'] ?? '(none)') . "\n\n";
        }
    } else {
        echo "❌ Response não foi 2xx. Vê o BODY acima pra motivo real.\n\n";
    }
} catch (\Throwable $e) {
    echo "   EXCEPTION: " . $e->getMessage() . "\n\n";
}

// ── 4. AbacatePayService::createPixCharge (nosso wrapper) ─────────
echo "4) VIA SERVICE — AbacatePayService::createPixCharge\n";
try {
    $svc = new AbacatePayService();
    $result = $svc->createPixCharge(
        500,
        'Teste via service',
        ['name' => 'Teste', 'email' => 'diag@vivensi.app.br'],
        ['origin' => 'service_test']
    );
    if ($result === null) {
        echo "   ❌ Retornou NULL (service falhou silenciosamente)\n";
        echo "   → Se passo 3 acima funcionou, bug no wrapper. Se falhou, bug na API.\n\n";
    } else {
        echo "   ✅ SUCESSO — service retornou:\n";
        echo "      id:           " . ($result['id'] ?? '(none)') . "\n";
        echo "      brCode:       " . (isset($result['brCode']) ? substr($result['brCode'], 0, 50) . '...' : '(none)') . "\n";
        echo "      brCodeBase64: " . (isset($result['brCodeBase64']) ? 'setado (len ' . strlen($result['brCodeBase64']) . ')' : '(none)') . "\n\n";
    }
} catch (\Throwable $e) {
    echo "   EXCEPTION: " . $e->getMessage() . "\n\n";
}

echo str_repeat('=', 70) . "\n";
echo " Fim do diagnóstico.\n";
echo str_repeat('=', 70) . "\n";
