<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fix user roles to match their tenant types
$users = \App\Models\User::with('tenant')->whereNotNull('tenant_id')->get();

foreach ($users as $user) {
    if (!$user->tenant) continue;
    
    $correctRole = match($user->tenant->type) {
        'manager' => 'manager',
        'ngo' => 'ngo',
        'common' => 'user',
        default => $user->role
    };
    
    if ($user->role !== $correctRole && $user->role !== 'super_admin') {
        $user->update(['role' => $correctRole]);
        echo "✅ Corrigido: {$user->email} -> role: {$correctRole}\n";
    }
}

echo "\n==================================\n";
echo "CREDENCIAIS ATUALIZADAS:\n";
echo "==================================\n\n";

echo "1️⃣ Super Admin:\n";
echo "   Email: admin@vivensi.com\n";
echo "   Painel: /admin\n\n";

echo "2️⃣ ONG (Terceiro Setor):\n";
echo "   Email: ngo@teste.com\n";
echo "   Senha: 123456\n";
echo "   Painel: Dashboard NGO\n\n";

echo "3️⃣ Gestor de Projetos:\n";
echo "   Email: gestor@teste.com\n";
echo "   Senha: 123456\n";
echo "   Painel: Dashboard Gestor\n\n";

echo "4️⃣ Pessoa Comum:\n";
echo "   Email: comum@teste.com\n";
echo "   Senha: 123456\n";
echo "   Painel: Dashboard Pessoa Comum\n\n";

echo "🎓 VIVENSI ACADEMY:\n";
echo "   URL: /academy (após login)\n";
echo "   Admin: /admin/academy (Super Admin)\n\n";
