<?php
use Illuminate\Http\Request;
use App\Http\Controllers\SmartAnalysisController;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate authenticated user
$user = User::first(); 
if (!$user) die("No user found.");
auth()->login($user);

echo "Testing Smart Analysis for Tenant ID: " . $user->tenant_id . "\n";

$request = Request::create('/smart-analysis/deep', 'POST');
$controller = new SmartAnalysisController();

try {
    $response = $controller->generateDeepAnalysis($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    $content = json_decode($response->getContent(), true);
    
    if (isset($content['analysis'])) {
        echo "AI Analysis Received:\n";
        echo substr($content['analysis'], 0, 500) . "...\n";
    } else {
        echo "No analysis returned. Response: " . $response->getContent() . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
