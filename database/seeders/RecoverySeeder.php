<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RecoverySeeder extends Seeder
{
    public function run()
    {
        // 0. Create Default Subscription Plans
        $plans = [
            [
                'name' => 'Plano Pessoa Comum',
                'target_audience' => 'common',
                'price' => 29.90,
                'interval' => 'monthly',
                'features' => json_encode(['Controle Financeiro Pessoal', 'Metas de Orçamento']),
            ],
            [
                'name' => 'Plano Terceiro Setor',
                'target_audience' => 'ngo',
                'price' => 89.90,
                'interval' => 'monthly',
                'features' => json_encode(['Gestão de Doadores', 'Portal de Transparência', 'Relatórios para Editais']),
            ],
            [
                'name' => 'Plano Gestor de Projetos/Empresas',
                'target_audience' => 'manager',
                'price' => 149.90,
                'interval' => 'monthly',
                'features' => json_encode(['Gestão de Equipes', 'Analíticos Avançados Bruce AI', 'Multi-projetos']),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['name' => $plan['name']],
                $plan + ['created_at' => now(), 'updated_at' => now()]
            );
        }
        // 1. Ensure Tenant exits
        $tenant = DB::table('tenants')->find(1);
        if (!$tenant) {
            DB::table('tenants')->insert([
                'id' => 1,
                'name' => 'Demo Tenant',
                'type' => 'business',
                'subscription_status' => 'active',
                'plan_id' => 3, // Gestor
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Ensure Super Admin User exists
        $user = DB::table('users')->where('email', 'super@fin.com')->first();
        if (!$user) {
            DB::table('users')->insert([
                'id' => 1,
                'tenant_id' => 1,
                'name' => 'Super Admin',
                'email' => 'super@fin.com',
                'password' => Hash::make('password'), // Reset password or use known hash
                'role' => 'super_admin',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // 3. Ensure Common User exists
        $common = DB::table('users')->where('email', 'user@fin.com')->first();
        if (!$common) {
            DB::table('users')->insert([
                'tenant_id' => 1,
                'name' => 'User Common',
                'email' => 'user@fin.com',
                'password' => Hash::make('password'),
                'role' => 'common',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // 4. Ensure NGO Tenant exists
        $ngoTenant = DB::table('tenants')->find(2);
        if (!$ngoTenant) {
            DB::table('tenants')->insert([
                'id' => 2,
                'name' => 'ONG Esperança',
                'type' => 'ngo',
                'subscription_status' => 'active',
                'plan_id' => 2, // Terceiro Setor
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. User for NGO (Third Sector)
        $ngoUser = DB::table('users')->where('email', 'ong@fin.com')->first();
        if (!$ngoUser) {
            DB::table('users')->insert([
                'tenant_id' => 2, // Using NGO Tenant
                'name' => 'Gestor ONG',
                'email' => 'ong@fin.com',
                'password' => Hash::make('password'),
                'role' => 'ngo',
                'status' => 'active',
                'department' => 'Diretoria',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. User for Business (Project Manager)
        $businessUser = DB::table('users')->where('email', 'gestor@fin.com')->first();
        if (!$businessUser) {
            DB::table('users')->insert([
                'tenant_id' => 1, // Using Default Business Tenant
                'name' => 'Gestor Projetos',
                'email' => 'gestor@fin.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'status' => 'active',
                'department' => 'Projetos',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // 7. Ensure Personal Tenant exists
        $personalTenant = DB::table('tenants')->find(3);
        if (!$personalTenant) {
            DB::table('tenants')->insert([
                'id' => 3,
                'name' => 'João Silva (Pessoal)',
                'type' => 'personal',
                'subscription_status' => 'active',
                'plan_id' => 1, // Pessoa Comum
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. User for Personal flow
        $personalUser = DB::table('users')->where('email', 'pessoa@fin.com')->first();
        if (!$personalUser) {
            DB::table('users')->insert([
                'tenant_id' => 3,
                'name' => 'João Silva',
                'email' => 'pessoa@fin.com',
                'password' => Hash::make('password'),
                'role' => 'common',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // 9. Default Financial Categories
        $categories = [
            ['name' => 'Doações', 'type' => 'income', 'is_system_default' => true, 'color' => '#10b981'],
            ['name' => 'Vendas/Serviços', 'type' => 'income', 'is_system_default' => true, 'color' => '#3b82f6'],
            ['name' => 'Marketing', 'type' => 'expense', 'is_system_default' => true, 'color' => '#f59e0b'],
            ['name' => 'Salários/RH', 'type' => 'expense', 'is_system_default' => true, 'color' => '#ef4444'],
            ['name' => 'Infraestrutura', 'type' => 'expense', 'is_system_default' => true, 'color' => '#6366f1'],
        ];

        foreach ([1, 2, 3] as $tid) {
            foreach ($categories as $cat) {
                DB::table('financial_categories')->updateOrInsert(
                    ['tenant_id' => $tid, 'name' => $cat['name']],
                    $cat + ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 10. Dummy Data for Demo (Tenant 1)
        if (DB::table('projects')->where('tenant_id', 1)->count() === 0) {
            $projectId = DB::table('projects')->insertGetId([
                'tenant_id' => 1,
                'name' => 'Expansão 2026',
                'description' => 'Projeto de expansão da rede.',
                'budget' => 25000.00,
                'status' => 'active',
                'created_at' => now(),
            ]);

            // Add Team members to project
            $users = DB::table('users')->where('tenant_id', 1)->get();
            foreach ($users as $user) {
                DB::table('project_members')->insert([
                    'tenant_id' => 1,
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'access_level' => ($user->role == 'manager') ? 'admin' : 'editor',
                    'created_at' => now()
                ]);
            }

            // Add some tasks
            DB::table('tasks')->insert([
                [
                    'tenant_id' => 1,
                    'project_id' => $projectId,
                    'title' => 'Revisar metas do trimestre',
                    'description' => 'Garantir que todos os KPIs estejam alinhados.',
                    'status' => 'todo',
                    'priority' => 'high',
                    'assigned_to' => $users->where('role', 'employee')->first()->id ?? $users->first()->id,
                    'created_at' => now()
                ],
                [
                    'tenant_id' => 1,
                    'project_id' => $projectId,
                    'title' => 'Preparar relatório mensal',
                    'description' => 'Compilar dados financeiros.',
                    'status' => 'doing',
                    'priority' => 'medium',
                    'assigned_to' => $users->where('role', 'manager')->first()->id ?? $users->first()->id,
                    'created_at' => now()
                ]
            ]);

            if (DB::table('transactions')->where('tenant_id', 1)->count() === 0) {
                DB::table('transactions')->insert([
                    'tenant_id' => 1,
                    'project_id' => $projectId,
                    'description' => 'Aporte Inicial Projeto',
                    'amount' => 5000.00,
                    'type' => 'income',
                    'date' => now(),
                    'status' => 'paid',
                    'created_at' => now(),
                ]);
            }
        }
    }
}

