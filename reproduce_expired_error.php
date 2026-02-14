<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Find or create a user with an expired trial
$user = User::whereHas('tenant', function($q) {
    $q->where('subscription_status', 'trialing')
      ->where('trial_ends_at', '<', now());
})->first();

if (!$user) {
    echo "No expired trial user found. Temporarily modifying a user for testing...\n";
    $user = User::whereHas('tenant')->first();
    if ($user) {
        $user->tenant->update([
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->subDays(1)
        ]);
        echo "Modified user {$user->email} ({$user->id}) to have expired trial.\n";
    } else {
        echo "No users found at all.\n";
        exit;
    }
} else {
    echo "Found expired trial user: {$user->email} ({$user->id})\n";
}

// 2. Simulate Login
Auth::login($user);

// 3. Simulate Request to Dashboard
$request = Request::create('/dashboard', 'GET');

// 4. Run Middleware Manually (CheckSubscription)
echo "Testing Middleware...\n";
try {
    $middleware = new \App\Http\Middleware\CheckSubscription();
    $response = $middleware->handle($request, function($req) {
        echo "Middleware passed! Proceeding to controller...\n";
        return new \Illuminate\Http\Response('Controller logic would run here');
    });
    
    // If it's a redirect, print target
    if ($response->isRedirection()) {
        echo "Middleware Redirected to: " . $response->getTargetUrl() . "\n";
    }

} catch (\Throwable $e) {
    echo "CRASH IN MIDDLEWARE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit;
}

// 5. If middleware passed, test Controller Logic
echo "\nTesting Controller Logic...\n";
try {
    $controller = new \App\Http\Controllers\DashboardController();
    // Start capturing output buffer to avoid view rendering issues affecting console
    ob_start();
    $view = $controller->index();
    ob_end_clean();
    echo "Controller Executed Successfully!\n";
} catch (\Throwable $e) {
    echo "CRASH IN CONTROLLER: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
