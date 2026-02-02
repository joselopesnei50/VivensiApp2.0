<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

echo "--- ASAAS WEBHOOK SIMULATION TEST ---\n";

// 1. Setup Data
$tenant = Tenant::first();
$tenant->asaas_customer_id = 'cus_simulation_123';
$tenant->subscription_status = 'pending';
$tenant->save();

SystemSetting::setValue('asaas_webhook_token', 'SIMULATED_TOKEN_2026');

echo "Pre-condition: Tenant ID {$tenant->id} has status '{$tenant->subscription_status}'\n";

// 2. Simulate Webhook Call
$url = url('/api/webhooks/asaas');
// Since url() might return 'http://localhost', we might need to manually set it if using cli
if (strpos($url, 'public') === false) {
    $url = 'http://localhost/vivensi-laravel/public/api/webhooks/asaas'; // Adjust for local environment
}

echo "Calling Webhook URL: $url\n";

$payload = [
    'event' => 'PAYMENT_RECEIVED',
    'subscription' => 'sub_simulated_999',
    'payment' => [
        'customer' => 'cus_simulation_123',
        'value' => 100.00,
        'status' => 'RECEIVED'
    ]
];

$response = Http::withHeaders([
    'asaas-access-token' => 'SIMULATED_TOKEN_2026'
])->post($url, $payload);

echo "Webhook Response: [" . $response->status() . "] " . $response->body() . "\n";

// 3. Verify Result
$tenant->refresh();
echo "Post-condition: Tenant ID {$tenant->id} now has status '{$tenant->subscription_status}'\n";

if ($tenant->subscription_status === 'active') {
    echo "\n[SUCCESS] Webhook processed successfully and tenant activated!\n";
} else {
    echo "\n[FAILURE] Tenant status did not change to active.\n";
}

echo "--- END TEST ---\n";
