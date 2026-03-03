<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Models\WhatsappConfig;

$configs = WhatsappConfig::all();
echo "Configs: " . count($configs) . "\n";
foreach($configs as $c) {
    print_r($c->toArray());
}

$conn = new mysqli("127.0.0.1", "root", "", "finmanage_pro");
$result = $conn->query("SELECT id, tenant_id, evolution_instance_name FROM users WHERE evolution_instance_name IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
}
$conn->close();
