<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesStagesSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('sales_stages')->count() > 0) {
            return;
        }

        $now = now();
        DB::table('sales_stages')->insert([
            ['name' => 'Novo Lead',     'color' => '#6366f1', 'position' => 1, 'is_won' => false, 'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Contatado',     'color' => '#f59e0b', 'position' => 2, 'is_won' => false, 'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Demo Agendada', 'color' => '#3b82f6', 'position' => 3, 'is_won' => false, 'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Proposta',      'color' => '#8b5cf6', 'position' => 4, 'is_won' => false, 'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Negociação',    'color' => '#ec4899', 'position' => 5, 'is_won' => false, 'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ganho',         'color' => '#22c55e', 'position' => 6, 'is_won' => true,  'is_lost' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Perdido',       'color' => '#ef4444', 'position' => 7, 'is_won' => false, 'is_lost' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
