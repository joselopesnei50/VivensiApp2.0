<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// === CRIAR TENANT NGO ===
$tenantNgo = \App\Models\Tenant::create([
    'name' => 'ONG Teste Local',
    'document' => '12345678901234',
    'type' => 'ngo',
    'subscription_status' => 'active',
    'trial_ends_at' => now()->addDays(30),
]);

// === CRIAR USUÁRIO NGO ===
$userNgo = \App\Models\User::create([
    'name' => 'Admin NGO',
    'email' => 'ngo@teste.com',
    'password' => bcrypt('123456'),
    'role' => 'admin',
    'tenant_id' => $tenantNgo->id,
    'email_verified_at' => now(),
]);

echo "✅ Usuário NGO criado!\n";
echo "Email: ngo@teste.com\n";
echo "Senha: 123456\n\n";

// === CRIAR TENANT MANAGER ===
$tenantManager = \App\Models\Tenant::create([
    'name' => 'Gestor Teste Local',
    'document' => '98765432109876',
    'type' => 'manager',
    'subscription_status' => 'active',
    'trial_ends_at' => now()->addDays(30),
]);

// === CRIAR USUÁRIO MANAGER ===
$userManager = \App\Models\User::create([
    'name' => 'Gestor Projetos',
    'email' => 'gestor@teste.com',
    'password' => bcrypt('123456'),
    'role' => 'admin',
    'tenant_id' => $tenantManager->id,
    'email_verified_at' => now(),
]);

echo "✅ Usuário Gestor criado!\n";
echo "Email: gestor@teste.com\n";
echo "Senha: 123456\n\n";

// === CRIAR TENANT COMUM ===
$tenantComum = \App\Models\Tenant::create([
    'name' => 'Pessoa Comum Teste',
    'document' => '11223344556677',
    'type' => 'common',
    'subscription_status' => 'active',
    'trial_ends_at' => now()->addDays(30),
]);

// === CRIAR USUÁRIO COMUM ===
$userComum = \App\Models\User::create([
    'name' => 'Usuário Comum',
    'email' => 'comum@teste.com',
    'password' => bcrypt('123456'),
    'role' => 'user',
    'tenant_id' => $tenantComum->id,
    'email_verified_at' => now(),
]);

echo "✅ Usuário Comum criado!\n";
echo "Email: comum@teste.com\n";
echo "Senha: 123456\n\n";

echo "==================================\n";
echo "RESUMO DAS CREDENCIAIS:\n";
echo "==================================\n\n";

echo "1️⃣ Super Admin:\n";
echo "   Email: admin@vivensi.com\n";
echo "   Senha: (use a senha original)\n\n";

echo "2️⃣ ONG (Terceiro Setor):\n";
echo "   Email: ngo@teste.com\n";
echo "   Senha: 123456\n\n";

echo "3️⃣ Gestor de Projetos:\n";
echo "   Email: gestor@teste.com\n";
echo "   Senha: 123456\n\n";

echo "4️⃣ Pessoa Comum:\n";
echo "   Email: comum@teste.com\n";
echo "   Senha: 123456\n\n";
