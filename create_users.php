<?php
/**
 * Script para CRIAR usuários de teste
 * Execute: php create_users.php
 */

// Carregar Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===================================\n";
echo "CRIANDO USUÁRIOS DE TESTE\n";
echo "===================================\n\n";

// 1. GESTOR DE PROJETOS
$tenantGestor = \App\Models\Tenant::firstOrCreate(
    ['document' => '11111111111111'],
    [
        'name' => 'Gestor Financeiro',
        'type' => 'manager',
        'subscription_status' => 'active',
        'trial_ends_at' => now()->addDays(30),
    ]
);

$userGestor = \App\Models\User::firstOrCreate(
    ['email' => 'gestor@fin.com'],
    [
        'name' => 'Gestor Projetos',
        'password' => bcrypt('123456'),
        'role' => 'admin',
        'tenant_id' => $tenantGestor->id,
        'email_verified_at' => now(),
    ]
);
echo "✅ gestor@fin.com - Gestor de Projetos\n";

// 2. SUPER ADMIN
$tenantSuper = \App\Models\Tenant::firstOrCreate(
    ['document' => '22222222222222'],
    [
        'name' => 'Super Admin',
        'type' => 'manager',
        'subscription_status' => 'active',
        'trial_ends_at' => now()->addDays(365),
    ]
);

$userSuper = \App\Models\User::firstOrCreate(
    ['email' => 'super@fin.com'],
    [
        'name' => 'Super Admin',
        'password' => bcrypt('123456'),
        'role' => 'super_admin',
        'tenant_id' => $tenantSuper->id,
        'email_verified_at' => now(),
    ]
);
echo "✅ super@fin.com - Super Admin\n";

// 3. ONG / TERCEIRO SETOR
$tenantONG = \App\Models\Tenant::firstOrCreate(
    ['document' => '33333333333333'],
    [
        'name' => 'ONG Exemplo',
        'type' => 'ngo',
        'subscription_status' => 'active',
        'trial_ends_at' => now()->addDays(30),
    ]
);

$userONG = \App\Models\User::firstOrCreate(
    ['email' => 'ong@fin.com'],
    [
        'name' => 'Admin ONG',
        'password' => bcrypt('123456'),
        'role' => 'admin',
        'tenant_id' => $tenantONG->id,
        'email_verified_at' => now(),
    ]
);
echo "✅ ong@fin.com - Terceiro Setor (ONG)\n";

// 4. PESSOA COMUM
$tenantComum = \App\Models\Tenant::firstOrCreate(
    ['document' => '44444444444444'],
    [
        'name' => 'Usuário Comum',
        'type' => 'common',
        'subscription_status' => 'active',
        'trial_ends_at' => now()->addDays(30),
    ]
);

$userComum = \App\Models\User::firstOrCreate(
    ['email' => 'user@fin.com'],
    [
        'name' => 'Pessoa Comum',
        'password' => bcrypt('123456'),
        'role' => 'user',
        'tenant_id' => $tenantComum->id,
        'email_verified_at' => now(),
    ]
);
echo "✅ user@fin.com - Pessoa Comum\n";

echo "\n===================================\n";
echo "SUCESSO! USUÁRIOS CRIADOS\n";
echo "===================================\n\n";

echo "📋 CREDENCIAIS:\n\n";
echo "1️⃣ Gestor:      gestor@fin.com / 123456\n";
echo "2️⃣ Super Admin: super@fin.com / 123456\n";
echo "3️⃣ ONG:         ong@fin.com / 123456\n";
echo "4️⃣ Comum:       user@fin.com / 123456\n\n";

echo "🌐 URL: http://localhost/vivensi-laravel/public/login\n\n";

echo "===================================\n";
echo "TESTE DE AUTENTICAÇÃO\n";
echo "===================================\n\n";

// Verificar senhas
if (\Hash::check('123456', $userGestor->password)) {
    echo "✅ Senha do Gestor verificada!\n";
}
if (\Hash::check('123456', $userSuper->password)) {
    echo "✅ Senha do Super Admin verificada!\n";
}
if (\Hash::check('123456', $userONG->password)) {
    echo "✅ Senha da ONG verificada!\n";
}
if (\Hash::check('123456', $userComum->password)) {
    echo "✅ Senha do Comum verificada!\n";
}

echo "\n✅ TUDO PRONTO! Pode fazer login agora!\n";
