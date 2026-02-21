<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\FinancialCategory;

echo "TENANTS:\n";
foreach (Tenant::all() as $t) {
    echo "ID: " . $t->id . " | Name: " . $t->name . " | Type: " . $t->type . "\n";
}

echo "\nCATEGORIES COUNT: " . FinancialCategory::count() . "\n";
