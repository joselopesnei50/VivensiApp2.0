<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Services\EvolutionApiService;
use App\Models\Tenant;

$tenant = Tenant::first();
if (!$tenant) {
    die("No tenant found.\n");
}

echo "Testing Evolution API for Tenant: {$tenant->name} (Evolution Instance: {$tenant->evolution_instance_name})\n";

$evo = new EvolutionApiService($tenant);

echo "1. Getting Connection State:\n";
$state = $evo->getConnectionState();
print_r($state);

// We won't send a message yet, just checking the connection state first to see if the instance is ready.
