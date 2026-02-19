<?php

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking SubscriptionPlan schema and data...\n";

if (Schema::hasColumn('subscription_plans', 'target_audience')) {
    echo "Column 'target_audience' exists.\n";
    $audiences = SubscriptionPlan::distinct()->pluck('target_audience')->toArray();
    echo "Distinct Audiences:\n";
    print_r($audiences);
    
    $plans = SubscriptionPlan::all()->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'target_audience' => $p->target_audience,
            'price' => $p->price
        ];
    });
    print_r($plans->toArray());
} else {
    echo "Column 'target_audience' DOES NOT exist.\n";
}
