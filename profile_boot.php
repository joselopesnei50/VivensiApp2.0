<?php
define('LARAVEL_START', microtime(true));
echo "Starting...\n";

require __DIR__.'/vendor/autoload.php';
echo "Autoload loaded: " . (microtime(true) - LARAVEL_START) . "s\n";

$app = require_once __DIR__.'/bootstrap/app.php';
echo "App created: " . (microtime(true) - LARAVEL_START) . "s\n";

use Illuminate\Contracts\Console\Kernel;
$kernel = $app->make(Kernel::class);
echo "Kernel made: " . (microtime(true) - LARAVEL_START) . "s\n";

$kernel->bootstrap();
echo "App bootstrapped: " . (microtime(true) - LARAVEL_START) . "s\n";

echo "Done!\n";
