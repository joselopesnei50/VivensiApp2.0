<?php
/**
 * Verificar roles dos usuários
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \App\Models\User::whereIn('email', ['gestor@fin.com', 'super@fin.com', 'ong@fin.com', 'user@fin.com'])->get();

echo "================================\n";
echo "VERIFICANDO ROLES DOS USUÁRIOS\n";
echo "================================\n\n";

foreach ($users as $user) {
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
    echo "Tenant Type: {$user->tenant->type}\n";
    echo "Dashboard esperado: ";
    
    switch ($user->role) {
        case 'super_admin':
            echo "/admin\n";
            break;
        case 'manager':
            echo "dashboards.manager\n";
            break;
        case 'ngo':
            echo "dashboards.ngo\n";
            break;
        default:
            echo "dashboards.common\n";
    }
    echo "\n";
}

echo "================================\n";
echo "PROBLEMA IDENTIFICADO:\n";
echo "================================\n\n";

$gestor = \App\Models\User::where('email', 'gestor@fin.com')->first();
if ($gestor && $gestor->role !== 'manager') {
    echo "❌ ERRO: gestor@fin.com tem role '{$gestor->role}'\n";
    echo "   DEVERIA SER: 'manager'\n";
    echo "   CORRIGINDO...\n\n";
    
    $gestor->update(['role' => 'manager']);
    echo "✅ Role corrigido para 'manager'\n";
}

$ong = \App\Models\User::where('email', 'ong@fin.com')->first();
if ($ong && $ong->role !== 'ngo') {
    echo "❌ ERRO: ong@fin.com tem role '{$ong->role}'\n";
    echo "   DEVERIA SER: 'ngo'\n";
    echo "   CORRIGINDO...\n\n";
    
    $ong->update(['role' => 'ngo']);
    echo "✅ Role corrigido para 'ngo'\n";
}
