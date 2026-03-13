<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

$apiKey = SystemSetting::getValue('serper_api_key');
echo "API Key: " . substr($apiKey, 0, 5) . "...\n";

$response = Http::withHeaders(['X-API-KEY' => $apiKey])
    ->post('https://google.serper.dev/maps', [
        'q' => "Restaurantes em Araraquara, SP",
        'gl' => 'br',
        'hl' => 'pt-br'
    ]);

echo "Status: " . $response->status() . "\n";
$data = $response->json();
echo "Keys in response: " . implode(', ', array_keys($data)) . "\n";

if (isset($data['maps'])) {
    echo "Count of 'maps': " . count($data['maps']) . "\n";
    if (count($data['maps']) > 0) {
        echo "Example result keys: " . implode(', ', array_keys($data['maps'][0])) . "\n";
        print_r($data['maps'][0]);
    }
} else {
    echo "'maps' key NOT found in response.\n";
    print_r($data);
}
