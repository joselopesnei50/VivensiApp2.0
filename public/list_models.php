<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

$apiKey = SystemSetting::getValue('gemini_api_key');
$response = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey);

if ($response->successful()) {
    $models = $response->json()['models'] ?? [];
    $list = "";
    foreach ($models as $m) {
        $list .= $m['name'] . "\n";
    }
    file_put_contents(__DIR__ . '/models_list.txt', $list);
    echo "DONE: models_list.txt created.\n";
} else {
    echo "ERROR: " . $response->status() . " - " . $response->body() . "\n";
}
