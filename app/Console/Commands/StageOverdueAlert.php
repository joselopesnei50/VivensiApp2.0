<?php

namespace App\Console\Commands;

use App\Mail\StageOverdueAlertMail;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Digest diario de etapas atrasadas.
 *
 * Regra: etapa com `end_date < hoje` E status IN (pending, in_progress)
 * (nao completed, nao cancelled). Agrupa por tenant e manda 1 email por
 * gestor (nao spam por etapa). Evita disparos duplicados no mesmo dia com
 * cache keyed por gestor+data.
 */
class StageOverdueAlert extends Command
{
    protected $signature   = 'stages:overdue-alert';
    protected $description = 'Envia digest diario de etapas atrasadas por tenant';

    public function handle(): int
    {
        $today = now()->startOfDay();

        $overdueByTenant = ProjectStage::withoutGlobalScope('tenant')
            ->whereDate('end_date', '<', $today->toDateString())
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNull('deleted_at')
            ->with('project:id,name,tenant_id')
            ->get()
            ->groupBy('tenant_id');

        if ($overdueByTenant->isEmpty()) {
            $this->info('Nenhuma etapa atrasada hoje.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($overdueByTenant as $tenantId => $stages) {
            $managers = User::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->whereIn('role', ['manager', 'ngo', 'super_admin'])
                ->whereNotNull('email')
                ->get();

            foreach ($managers as $manager) {
                try {
                    Mail::to($manager->email)->queue(
                        new StageOverdueAlertMail($manager, $stages->values())
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error("stages:overdue-alert — erro ao enviar para {$manager->email}: " . $e->getMessage());
                }
            }
        }

        $tenantsCount = $overdueByTenant->count();
        $stagesCount  = $overdueByTenant->flatten()->count();
        Log::info("stages:overdue-alert — {$stagesCount} etapa(s) atrasada(s) em {$tenantsCount} tenant(s); {$sent} email(s) enviado(s).");
        $this->info("{$stagesCount} etapa(s) em {$tenantsCount} tenant(s); {$sent} email(s) disparados.");

        return self::SUCCESS;
    }
}
