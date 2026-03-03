<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\WhatsappBroadcastController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Find a user (preferably context of a manager or super admin)
$user = User::first();
echo "Simulating request for user: " . $user->name . " (Role: " . $user->role . ", Tenant: " . $user->tenant_id . ")\n";

Auth::login($user);

try {
    $controller = new WhatsappBroadcastController();
    $response = $controller->index();
    echo "Index Response Rendered OK.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
