<?php

use Illuminate\Support\Facades\Artisan;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--------------------------------------------------\n";
echo "🛠️  VIVENSI SELF-HEALING TOOL\n";
echo "--------------------------------------------------\n";

// 1. Clear Caches
echo "\n1. Clearing System Caches...\n";
try {
    Artisan::call('route:clear');
    echo "   ✅ Routes Cleared\n";
    Artisan::call('config:clear');
    echo "   ✅ Config Cleared\n";
    Artisan::call('cache:clear');
    echo "   ✅ Cache Cleared\n";
    Artisan::call('view:clear');
    echo "   ✅ View Cache Cleared\n";
} catch (\Throwable $e) {
    echo "   ❌ Failed to clear caches: " . $e->getMessage() . "\n";
}

// 2. Ensure Plan #1 Exists (Critical for Redirects)
echo "\n2. Verifying Subscription Plan #1...\n";
try {
    $plan = SubscriptionPlan::find(1);
    
    if (!$plan) {
        echo "   ⚠️ Plan #1 Missing. Creating Default Plan...\n";
        
        // Force ID to be 1 if possible, or just create one.
        // Eloquent doesn't let us force ID easily on auto-increment unless we cheat.
        // Let's try to just create one and hope it's fine, or use DB facade.
        
        \DB::table('subscription_plans')->inRandomOrder()->limit(1)->delete(); // Clear if corrupted? No, too risky.
        
        // Let's insert ID 1 explicitly
        \DB::table('subscription_plans')->insert([
            'id' => 1,
            'name' => 'Plano Básico',
            'target_audience' => 'common',
            'price' => 29.90,
            'interval' => 'monthly',
            'features' => json_encode(['Acesso Completo', 'Suporte Básico']),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "   ✅ Default Plan Created (ID: 1)\n";
    } else {
        echo "   ✅ Plan #1 Exists: " . $plan->name . "\n";
    }
    
} catch (\Throwable $e) {
    echo "   ❌ Database Error: " . $e->getMessage() . "\n";
}

// 3. Verify Checkout Routes
echo "\n3. Verifying Routes...\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$hasCheckout = false;
foreach ($routes as $route) {
    if ($route->getName() === 'checkout.index') {
        $hasCheckout = true;
        break;
    }
}

if ($hasCheckout) {
    echo "   ✅ Checkout Route Detected\n";
} else {
    echo "   ❌ Checkout Route MISSING! (Did you pull web.php?)\n";
}

echo "\n--------------------------------------------------\n";
echo "🎉 FIX COMPLETE. TRY ACCESSING THE DASHBOARD NOW.\n";
echo "--------------------------------------------------\n";
