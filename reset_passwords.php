<?php
/**
 * Script para resetar senhas diretamente
 * Execute: php reset_passwords.php
 */

// Carregar Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===================================\n";
echo "RESETANDO SENHAS DOS USUÁRIOS\n";
echo "===================================\n\n";

$emails = [
    'gestor@fin.com',
    'super@fin.com',
    'ong@fin.com',
    'user@fin.com'
];

$novaSenha = '123456';
$senhaHash = bcrypt($novaSenha);

foreach ($emails as $email) {
    $user = \App\Models\User::where('email', $email)->first();
    
    if ($user) {
        $user->password = $senhaHash;
        $user->save();
        
        echo "✅ {$email}\n";
        echo "   Nome: {$user->name}\n";
        echo "   Role: {$user->role}\n";
        echo "   Tenant ID: {$user->tenant_id}\n";
        echo "   Senha resetada para: {$novaSenha}\n\n";
    } else {
        echo "❌ {$email} - NÃO ENCONTRADO\n\n";
    }
}

echo "===================================\n";
echo "TESTANDO AUTENTICAÇÃO\n";
echo "===================================\n\n";

// Testar se a senha funciona
$testUser = \App\Models\User::where('email', 'gestor@fin.com')->first();
if ($testUser) {
    if (\Hash::check('123456', $testUser->password)) {
        echo "✅ Senha de gestor@fin.com FUNCIONA!\n";
    } else {
        echo "❌ Senha de gestor@fin.com NÃO funciona\n";
    }
}

echo "\n===================================\n";
echo "RESUMO FINAL\n";
echo "===================================\n\n";
echo "Todos os usuários com senha: 123456\n\n";
echo "Para testar:\n";
echo "1. Abra: http://localhost/vivensi-laravel/public/login\n";
echo "2. Use qualquer email acima com senha: 123456\n";
