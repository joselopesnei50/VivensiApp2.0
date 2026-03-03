<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$kernel = $app->make(Kernel::class);

echo "[1] Starting Real Request Simulation...\n";
$request = Request::create('/');
$response = $kernel->handle($request);
echo "[2] Request handle finished: " . (microtime(true) - LARAVEL_START) . "s\n";
echo "Status: " . $response->getStatusCode() . "\n";
echo "Length: " . strlen($response->getContent()) . "\n";
