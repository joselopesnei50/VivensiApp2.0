<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$kernel = $app->make(Kernel::class);

echo "[1] Starting Authenticated Request...\n";
$user = User::first();
echo "[2] User found: " . ($user ? $user->name : 'None') . "\n";

Auth::login($user);
echo "[3] Logged in: " . (microtime(true) - LARAVEL_START) . "s\n";

$request = Request::create('/');
echo "[4] Request Created: " . (microtime(true) - LARAVEL_START) . "s\n";

echo "[5] Starting Kernel@handle...\n";
$response = $kernel->handle($request);
echo "[6] Kernel@handle finished: " . (microtime(true) - LARAVEL_START) . "s\n";
echo "Response status: " . $response->getStatusCode() . "\n";
