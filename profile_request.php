<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$kernel = $app->make(Kernel::class);

echo "[1] Start Bootstrap: " . (microtime(true) - LARAVEL_START) . "s\n";
// We don't call bootstrap() directly if we use handle(), because handle() calls bootstrap()
$request = Request::create('/');
echo "[2] Request Created: " . (microtime(true) - LARAVEL_START) . "s\n";

echo "[3] Starting Kernel@handle...\n";
$response = $kernel->handle($request);
echo "[4] Kernel@handle finished: " . (microtime(true) - LARAVEL_START) . "s\n";
echo "Response status: " . $response->getStatusCode() . "\n";
