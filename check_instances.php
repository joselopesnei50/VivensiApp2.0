<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Tenant;

echo "--- USERS WITH EVOLUTION INSTANCE ---\n";
$users = User::whereNotNull('evolution_instance_name')->get();
foreach ($users as $u) {
    echo "User [{$u->id}] {$u->name} - Instance: {$u->evolution_instance_name}\n";
}

echo "\n--- TENANTS WITH EVOLUTION INSTANCE ---\n";
$tenants = Tenant::whereNotNull('evolution_instance_name')->get();
foreach ($tenants as $t) {
    echo "Tenant [{$t->id}] {$t->name} - Instance: {$t->evolution_instance_name}\n";
}
