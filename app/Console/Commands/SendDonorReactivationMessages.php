<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DonorRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDonorReactivationMessages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'donors:send-reactivation
                            {--tenant= : ID de um tenant específico (opcional)}
                            {--days=60 : Dias de inatividade para considerar o doador inativo}
                            {--dry-run : Simula o envio sem enviar de fato}';

    /**
     * The console command description.
     */
    protected $description = 'Envia mensagens de reativação para doadores inativos via WhatsApp';

    public function handle(DonorRetentionService $service): int
    {
        $daysInactive = (int) $this->option('days');
        $isDryRun     = $this->option('dry-run');
        $tenantId     = $this->option('tenant');

        $this->info("🔔 Iniciando envio de reativação (inatividade > {$daysInactive} dias)");

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN — nenhuma mensagem será enviada.');
        }

        // Filtra por tenant específico ou itera todos
        $tenantQuery = Tenant::query();
        if ($tenantId) {
            $tenantQuery->where('id', $tenantId);
        }

        $tenants = $tenantQuery->get();
        $totalSent = 0;

        foreach ($tenants as $tenant) {
            $this->line("  → Processando tenant: {$tenant->name} (#{$tenant->id})");

            if ($isDryRun) {
                $this->line("    [dry-run] Pulando envio...");
                continue;
            }

            try {
                $sent = $service->sendReactivationReminders($tenant->id, $daysInactive);
                $totalSent += $sent;
                $this->line("    ✅ {$sent} mensagens enviadas.");
            } catch (\Throwable $e) {
                $this->error("    ❌ Erro: " . $e->getMessage());
                Log::error("donors:send-reactivation error for tenant {$tenant->id}: " . $e->getMessage());
            }

            // Pausa entre tenants para evitar sobrecarga
            sleep(2);
        }

        $this->info("✅ Concluído! Total enviado: {$totalSent}");

        return Command::SUCCESS;
    }
}
