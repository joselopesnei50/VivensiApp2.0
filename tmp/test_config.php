<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Pusher Key: " . config('broadcasting.connections.pusher.key') . "\n";
echo "Pusher Host: " . config('broadcasting.connections.pusher.options.host') . "\n";
echo "Pusher Port: " . config('broadcasting.connections.pusher.options.port') . "\n";
echo "Pusher Scheme: " . config('broadcasting.connections.pusher.options.scheme') . "\n";
echo "Pusher TLS: " . (config('broadcasting.connections.pusher.options.useTLS') ? 'true' : 'false') . "\n";
