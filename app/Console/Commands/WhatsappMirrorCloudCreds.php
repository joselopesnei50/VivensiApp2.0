<?php

namespace App\Console\Commands;

use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use Illuminate\Console\Command;

class WhatsappMirrorCloudCreds extends Command
{
    protected $signature = 'whatsapp:mirror-cloud-creds
                            {--tenant= : Espelha apenas o tenant informado (opcional)}
                            {--dry-run : Mostra o que seria feito sem gravar}';

    protected $description = 'Espelha creds Cloud (waba_id/phone_number_id/graph_access_token) das WhatsappInstance pro WhatsappConfig do mesmo tenant. Idempotente. Backfill de tenants conectados antes do fix 2026-08-17.';

    public function handle(): int
    {
        $query = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('provider', WhatsappInstance::PROVIDER_CLOUD_API)
            ->whereNotNull('phone_number_id')
            ->whereNotNull('graph_access_token');

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $instances = $query->get();
        $count     = $instances->count();

        if ($count === 0) {
            $this->warn('Nenhuma WhatsappInstance Cloud com creds encontrada.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$count} instance(s) Cloud pra espelhar.");
        $dryRun  = (bool) $this->option('dry-run');
        $updated = 0;
        $created = 0;

        foreach ($instances as $inst) {
            if (!$inst->tenant_id) {
                $this->warn("  #{$inst->id} sem tenant_id — pulando.");
                continue;
            }

            $existing = WhatsappConfig::withoutGlobalScopes()
                ->where('tenant_id', $inst->tenant_id)
                ->first();

            $action = $existing ? 'UPDATE' : 'CREATE';
            $this->line("  → tenant #{$inst->tenant_id} instance #{$inst->id}: {$action} config com phone_number_id={$inst->phone_number_id}");

            if ($dryRun) {
                continue;
            }

            WhatsappConfig::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $inst->tenant_id],
                [
                    'meta_waba_id'         => $inst->waba_id,
                    'meta_phone_number_id' => $inst->phone_number_id,
                    'meta_access_token'    => $inst->graph_access_token,
                ]
            );

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN — nada gravado. Rode sem --dry-run pra aplicar.');
        } else {
            $this->info("Concluido: {$updated} config(s) atualizado(s), {$created} criado(s).");
        }

        return self::SUCCESS;
    }
}
