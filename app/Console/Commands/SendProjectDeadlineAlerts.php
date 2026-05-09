<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ProjectAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendProjectDeadlineAlerts extends Command
{
    protected $signature = 'projects:deadline-alerts
                            {--tenant= : ID de um tenant específico (opcional)}
                            {--dry-run : Simula sem enviar mensagens}';

    protected $description = 'Envia alertas de prazo e orçamento de projetos via WhatsApp para gestores';

    public function handle(ProjectAlertService $service): int
    {
        $isDryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $this->info('🔔 Verificando alertas de projetos...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN — nenhuma mensagem será enviada.');
        }

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants   = $query->get();
        $totalSent = 0;

        foreach ($tenants as $tenant) {
            $this->line("  → Tenant: {$tenant->name} (#{$tenant->id})");

            if ($isDryRun) {
                $this->line('    [dry-run] Pulando envio...');
                continue;
            }

            try {
                $result = $service->runAlertsForTenant($tenant->id);
                $totalSent += $result['sent'];

                $this->line("    ✅ {$result['sent']} alerta(s) enviado(s) | {$result['projects_checked']} projeto(s) verificado(s)");
            } catch (\Throwable $e) {
                $this->error("    ❌ Erro: " . $e->getMessage());
                Log::error("projects:deadline-alerts error for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        $this->info("✅ Concluído! Total de alertas enviados: {$totalSent}");

        return Command::SUCCESS;
    }
}
