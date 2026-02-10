<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'admin@vivensi.com')->first();
if ($user) {
    $user->password = bcrypt('123456');
    $user->save();
    echo "✅ Senha do usuário 'admin@vivensi.com' resetada para '123456'.\n";
} else {
    echo "❌ Usuário 'admin@vivensi.com' não encontrado.\n";
}
