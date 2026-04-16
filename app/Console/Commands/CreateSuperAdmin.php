<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super
                            {--name=Super Admin : Nome do usuário}
                            {--email=admin@vivensi.app.br : Email do super admin}
                            {--password= : Senha (obrigatório)}';

    protected $description = 'Cria ou atualiza o usuário Super Admin no sistema';

    public function handle()
    {
        $password = $this->option('password');

        if (empty($password)) {
            $this->error('❌ Senha é obrigatória! Use: --password=SuaSenha123');
            return 1;
        }

        $email = $this->option('email');
        $name  = $this->option('name');

        // Garante que o Tenant 1 existe
        $tenant = DB::table('tenants')->find(1);
        if (!$tenant) {
            DB::table('tenants')->insert([
                'id'                  => 1,
                'name'                => 'Vivensi Platform',
                'type'                => 'business',
                'subscription_status' => 'active',
                'plan_id'             => 3,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $this->info('✅ Tenant principal criado.');
        }

        $existing = DB::table('users')->where('email', $email)->first();

        if ($existing) {
            // Atualiza o usuário existente
            DB::table('users')->where('email', $email)->update([
                'name'       => $name,
                'password'   => Hash::make($password),
                'role'       => 'super_admin',
                'status'     => 'active',
                'tenant_id'  => 1,
                'updated_at' => now(),
            ]);
            $this->info("✅ Super Admin atualizado com sucesso!");
        } else {
            // Cria novo usuário
            DB::table('users')->insert([
                'tenant_id'  => 1,
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($password),
                'role'       => 'super_admin',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ Super Admin criado com sucesso!");
        }

        $this->newLine();
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Email',    $email],
                ['Nome',     $name],
                ['Role',     'super_admin'],
                ['Status',   'active'],
                ['Tenant',   '1 (Vivensi Platform)'],
            ]
        );

        $this->newLine();
        $this->warn("⚠️  Guarde a senha em local seguro! Ela não pode ser recuperada.");

        return 0;
    }
}
