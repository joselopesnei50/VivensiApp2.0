<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa cache de permissões antes de criar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── PERMISSÕES ────────────────────────────────────────────────────────

        $permissions = [
            // Admin
            'manage-settings',
            'manage-tenants',
            'manage-plans',
            'view-email-logs',
            'manage-lgpd',

            // Projetos
            'manage-projects',
            'view-projects',
            'manage-tasks',

            // ONG
            'manage-donors',
            'manage-grants',
            'manage-beneficiaries',
            'manage-hr',
            'manage-inventory',
            'manage-assets',
            'manage-receipts',
            'manage-contracts',
            'manage-transparency',
            'view-audit-trail',

            // Financeiro
            'manage-transactions',
            'view-reports',
            'manage-budget',
            'manage-reconciliation',

            // WhatsApp
            'access-whatsapp',
            'manage-broadcast',
            'manage-whatsapp-automations',

            // Marketing & Social
            'access-manager',
            'manage-marketing',
            'manage-prospecting',
            'manage-landing-pages',
            'manage-social-media',
            'access-social-ai',

            // Módulo pessoal / MEI
            'manage-personal-finance',
            'manage-clients',

            // Geral
            'view-dashboard',
            'manage-profile',
            'access-support',
            'access-academy',
            'manage-raffles',
            'manage-branding',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── ROLES ─────────────────────────────────────────────────────────────

        // SUPER ADMIN — acesso total (Gate::before já garante, mas registramos as permissões)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // NGO ADMIN — painel ONG completo
        $ngoAdmin = Role::firstOrCreate(['name' => 'ngo_admin', 'guard_name' => 'web']);
        $ngoAdmin->syncPermissions([
            'view-dashboard', 'manage-profile', 'access-support', 'access-academy',
            'manage-donors', 'manage-grants', 'manage-beneficiaries', 'manage-hr',
            'manage-inventory', 'manage-assets', 'manage-receipts', 'manage-contracts',
            'manage-transparency', 'view-audit-trail',
            'manage-transactions', 'view-reports', 'manage-budget', 'manage-reconciliation',
            'access-whatsapp', 'manage-broadcast',
            'access-manager', 'manage-marketing', 'manage-landing-pages',
            'manage-social-media', 'access-social-ai',
            'manage-raffles', 'manage-branding',
            'manage-prospecting',
        ]);

        // NGO EDITOR — cria e edita, sem deletar / sem configurações sensíveis
        $ngoEditor = Role::firstOrCreate(['name' => 'ngo_editor', 'guard_name' => 'web']);
        $ngoEditor->syncPermissions([
            'view-dashboard', 'manage-profile', 'access-support', 'access-academy',
            'manage-donors', 'manage-grants', 'manage-beneficiaries',
            'manage-receipts', 'manage-contracts', 'manage-transparency',
            'manage-transactions', 'view-reports', 'manage-budget',
            'access-whatsapp',
            'access-social-ai', 'manage-social-media',
        ]);

        // MANAGER ADMIN — gestão de projetos completa
        $managerAdmin = Role::firstOrCreate(['name' => 'manager_admin', 'guard_name' => 'web']);
        $managerAdmin->syncPermissions([
            'view-dashboard', 'manage-profile', 'access-support', 'access-academy',
            'manage-projects', 'view-projects', 'manage-tasks',
            'manage-transactions', 'view-reports', 'manage-budget', 'manage-reconciliation',
            'access-whatsapp', 'manage-broadcast', 'manage-whatsapp-automations',
            'access-manager', 'manage-marketing', 'manage-landing-pages',
            'manage-social-media', 'access-social-ai',
            'manage-raffles', 'manage-branding',
            'manage-prospecting',
        ]);

        // MEI OWNER — módulo pessoal e MEI
        $meiOwner = Role::firstOrCreate(['name' => 'mei_owner', 'guard_name' => 'web']);
        $meiOwner->syncPermissions([
            'view-dashboard', 'manage-profile', 'access-support', 'access-academy',
            'manage-personal-finance', 'manage-clients',
            'manage-transactions', 'view-reports', 'manage-budget', 'manage-reconciliation',
            'access-whatsapp',
            'access-social-ai',
        ]);

        // EMPLOYEE — colaborador (somente leitura + tarefas atribuídas)
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            'view-dashboard', 'manage-profile', 'access-support', 'access-academy',
            'view-projects', 'manage-tasks',
            'view-reports',
            'access-whatsapp',
        ]);

        $this->command->info('Roles e permissões criadas com sucesso!');
        $this->command->table(
            ['Role', 'Permissões'],
            Role::with('permissions')->get()->map(fn($r) => [
                $r->name,
                $r->permissions->pluck('name')->implode(', ')
            ])->toArray()
        );
    }
}
