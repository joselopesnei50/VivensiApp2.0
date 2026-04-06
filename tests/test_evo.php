<?php
$evo = new App\Services\EvolutionApiService();
echo "\n--- CREATING ---\n";
$create = $evo->createInstance('teste_vivensi_z22', 'dummy_token');
var_dump($create);

echo "\n--- FETCHING QR ---\n";
$evo->instanceName = 'teste_vivensi_z22';
var_dump($evo->getConnectionQr());

// Delete to clean up
echo "\n--- DELETING ---\n";
var_dump($evo->deleteInstance('teste_vivensi_z22'));
