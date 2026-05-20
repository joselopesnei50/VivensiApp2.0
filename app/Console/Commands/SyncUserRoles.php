<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncUserRoles extends Command
{
    protected $signature   = 'vivensi:sync-roles';
    protected $description = 'Sincroniza a coluna role dos usuários legados com as roles do Spatie Permission';

    // Mapeamento coluna role → role spatie
    private array $map = [
        'super_admin' => 'super_admin',
        'ngo'         => 'ngo_admin',
        'manager'     => 'manager_admin',
        'common'      => 'mei_owner',
        'employee'    => 'employee',
    ];

    public function handle(): int
    {
        $total   = User::count();
        $synced  = 0;
        $skipped = 0;

        $this->info("Sincronizando {$total} usuários...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        User::chunk(100, function ($users) use (&$synced, &$skipped, $bar) {
            foreach ($users as $user) {
                $spatieRole = $this->map[$user->role] ?? null;

                if (!$spatieRole || !Role::where('name', $spatieRole)->exists()) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Atribui role spatie apenas se ainda não tiver
                if (!$user->hasRole($spatieRole)) {
                    $user->syncRoles([$spatieRole]);
                    $synced++;
                } else {
                    $skipped++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Concluído: {$synced} sincronizados, {$skipped} ignorados.");

        return self::SUCCESS;
    }
}
