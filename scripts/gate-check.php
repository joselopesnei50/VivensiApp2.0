<?php
/**
 * Verifica se um user específico passa no Gate has-whatsapp-cloud.
 * Uso: sudo -u www-data php scripts/gate-check.php <email>
 * Ex.:  sudo -u www-data php scripts/gate-check.php metareview@vivensi.app.br
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'metareview@vivensi.app.br';

$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "### User $email nao encontrado\n";
    exit(1);
}

echo "User:        {$user->email}\n";
echo "ID:          {$user->id}\n";
echo "Role:        {$user->role}\n";
echo "Tenant ID:   {$user->tenant_id}\n";
echo "Plan ID:     " . ($user->tenant?->plan_id ?? '(sem plano)') . "\n";
echo "Plan name:   " . ($user->tenant?->plan?->name ?? '(sem plano)') . "\n";
echo "Plan capabilities:  " . json_encode($user->tenant?->plan?->capabilities) . "\n";
echo "Tenant hasCapability(whatsapp_cloud): "
   . ($user->tenant?->hasCapability('whatsapp_cloud') ? 'true' : 'false')
   . "\n";

// Testa o Gate
\Illuminate\Support\Facades\Auth::setUser($user);
$can = \Illuminate\Support\Facades\Gate::allows('has-whatsapp-cloud');
echo "\n>>> Gate has-whatsapp-cloud: " . ($can ? '✅ true (deve aparecer no menu)' : '❌ false (nao aparece)') . "\n";
