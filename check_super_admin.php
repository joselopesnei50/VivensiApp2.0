<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admins = \App\Models\User::where('role', 'super_admin')->get(['id', 'name', 'email']);
if ($admins->isEmpty()) {
    echo "⚠️ Nenhum usuário com role 'super_admin' encontrado.\n";
    echo "Verificando usuários com role 'admin' e email contendo 'admin' ou 'super':\n";
    $candidates = \App\Models\User::where('role', 'admin')
        ->where(function($q) {
            $q->where('email', 'like', '%admin%')->orWhere('email', 'like', '%super%');
        })->get(['id', 'name', 'email', 'role']);
    foreach ($candidates as $user) {
        echo "- ID: {$user->id} | Nome: {$user->name} | Email: {$user->email} | Role: {$user->role}\n";
    }
} else {
    echo "✅ Super Admins encontrados:\n";
    foreach ($admins as $admin) {
        echo "- ID: {$admin->id} | Nome: {$admin->name} | Email: {$admin->email}\n";
    }
}
