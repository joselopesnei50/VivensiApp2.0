<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing SystemSetting::getValue...\n";
try {
    $val = \App\Models\SystemSetting::getValue('pagseguro_email');
    echo "SystemSetting::getValue returned: " . json_encode($val) . "\n";
} catch (\Throwable $e) {
    echo "CRASH in SystemSetting: " . $e->getMessage() . "\n";
}

echo "\nTesting PagSeguroService Instantiation...\n";
try {
    $service = $app->make(\App\Services\PagSeguroService::class);
    echo "PagSeguroService Instantiated Successfully!\n";
} catch (\Throwable $e) {
    echo "CRASH in PagSeguroService: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\nTesting CheckoutController Instantiation...\n";
try {
    $controller = $app->make(\App\Http\Controllers\CheckoutController::class);
    echo "CheckoutController Instantiated Successfully!\n";
} catch (\Throwable $e) {
    echo "CRASH in CheckoutController: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
