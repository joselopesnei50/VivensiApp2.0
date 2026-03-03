<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Services\EvolutionApiService;
use App\Models\WhatsappConfig;
use App\Models\Tenant;

$configs = WhatsappConfig::all();
echo "Total Configs found: " . count($configs) . "\n";
foreach($configs as $c) {
    if ($c->tenant_id) {
         $t = Tenant::find($c->tenant_id);
         echo "Tenant: " . ($t ? $t->name : 'N/A') . " (ID: $c->tenant_id)\n";
         echo "Instance in Tenant Model: " . ($t ? $t->evolution_instance_name : 'N/A') . "\n";
         
         $evo = new EvolutionApiService($t);
         echo "State: \n";
         print_r($evo->getConnectionState());
         
         echo "-----\n";
    }
}
