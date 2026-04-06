<?php
$evo = new App\Services\EvolutionApiService();
$name = 'qr_test_' . rand(1000, 9999);
echo "\n--- CREATING: $name ---\n";
$create = $evo->createInstance($name, 'dummy_token');
var_dump($create);

echo "\n--- FETCHING QR ---\n";
$evo->instanceName = $name;
var_dump($evo->getConnectionQr());

// Delete to clean up
echo "\n--- DELETING ---\n";
var_dump($evo->deleteInstance($name));
