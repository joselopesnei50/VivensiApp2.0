<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

echo "[1] Starting View Render Test...\n";
$plans = collect([]);
$posts = collect([]);
$videoUrl = null;
$testimonials = collect([]);

try {
    $html = view('welcome', compact('plans', 'posts', 'videoUrl', 'testimonials'))->render();
    echo "[2] Render finished: " . (microtime(true) - LARAVEL_START) . "s\n";
    echo "HTML length: " . strlen($html) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
