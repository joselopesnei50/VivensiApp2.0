<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NgoSeeder extends Seeder
{
    public function run()
    {
        // 1. Create NGO Tenant
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'ONG Salve o Planeta',
            'document' => '00.000.000/0001-99',
            'type' => 'ngo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create NGO User
        DB::table('users')->insert([
            'tenant_id' => $tenantId,
            'name' => 'Diretor da ONG',
            'email' => 'ong@fin.com',
            'password_hash' => Hash::make('password'),
            'role' => 'ngo',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
