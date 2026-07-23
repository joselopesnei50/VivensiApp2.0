<?php

namespace Database\Seeders;

use App\Models\RadarTerritory;
use Illuminate\Database\Seeder;

class RadarTerritorySeeder extends Seeder
{
    public function run(): void
    {
        $territories = [
            ['ibge_code' => '3550308', 'name' => 'São Paulo',          'uf' => 'SP'],
            ['ibge_code' => '3304557', 'name' => 'Rio de Janeiro',     'uf' => 'RJ'],
            ['ibge_code' => '2304400', 'name' => 'Fortaleza',          'uf' => 'CE'],
            ['ibge_code' => '2927408', 'name' => 'Salvador',           'uf' => 'BA'],
            ['ibge_code' => '3106200', 'name' => 'Belo Horizonte',     'uf' => 'MG'],
            ['ibge_code' => '4106902', 'name' => 'Curitiba',           'uf' => 'PR'],
            ['ibge_code' => '2611606', 'name' => 'Recife',             'uf' => 'PE'],
            ['ibge_code' => '1302603', 'name' => 'Manaus',             'uf' => 'AM'],
            ['ibge_code' => '5300108', 'name' => 'Brasília',           'uf' => 'DF'],
            ['ibge_code' => '4314902', 'name' => 'Porto Alegre',       'uf' => 'RS'],
        ];

        foreach ($territories as $data) {
            RadarTerritory::updateOrCreate(
                ['ibge_code' => $data['ibge_code']],
                array_merge($data, ['active' => true])
            );
        }

        $this->command->info('RadarTerritorySeeder: ' . count($territories) . ' territórios inseridos/atualizados.');
    }
}
