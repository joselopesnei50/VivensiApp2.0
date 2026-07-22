<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConfig;
use App\Services\ComplianceCalculationService;
use App\Services\EvolutionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConformidadeAlertCritical extends Command
{
    protected $signature = 'conformidade:alert-critical
                            {--tenant= : ID de um tenant específico (opcional)}
                            {--dry-run : Simula sem enviar mensagens}';

    protected $description = 'Envia alerta WhatsApp quando índice de conformidade fica abaixo de 50%';

    public function handle(ComplianceCalculationService $service): int
    {
        $isDryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $query = Tenant::where('subscription_status', 'active')->where('type', 'ngo');
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();
        $sent    = 0;

        foreach ($tenants as $tenant) {
            $cacheKey = "conformidade.critical_alert.{$tenant->id}." . now()->format('Y-m-d');

            if (! $isDryRun && Cache::has($cacheKey)) {
                $this->line("  → #{$tenant->id} {$tenant->name}: alerta já enviado hoje, pulando.");
                continue;
            }

            $dashboard = $service->dashboard($tenant->id);
            $indice    = (float) ($dashboard['indice_geral'] ?? 100);

            if ($indice >= 50) {
                $this->line("  → #{$tenant->id} {$tenant->name}: índice {$indice}% OK.");
                continue;
            }

            if ($isDryRun) {
                $this->warn("  [dry-run] #{$tenant->id} {$tenant->name}: índice {$indice}% → alerta NÃO enviado.");
                continue;
            }

            if ($this->enviarAlerta($tenant, $indice)) {
                Cache::put($cacheKey, true, now()->endOfDay());
                $sent++;
                $this->info("  ✅ Alerta enviado para {$tenant->name} (índice {$indice}%).");
                sleep(rand(3, 6));
            }
        }

        Log::info('conformidade:alert-critical concluído', ['enviados' => $sent, 'tenants' => $tenants->count()]);
        $this->info("Concluído: {$sent} alerta(s) enviado(s).");

        return 0;
    }

    private function enviarAlerta(Tenant $tenant, float $indice): bool
    {
        $config = WhatsappConfig::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return false;
        }

        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', ['ngo', 'manager'])
            ->whereNotNull('phone')
            ->first();

        if (! $admin) {
            return false;
        }

        $phone = preg_replace('/\D/', '', $admin->phone);
        if (! str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        $firstName = explode(' ', $admin->name)[0];
        $url       = url('/ngo/conformidade');

        $message = "⚠️ Olá, *{$firstName}*!\n\n"
            . "O índice de conformidade da sua organização está em *{$indice}%*, "
            . "abaixo do mínimo recomendado de 50%.\n\n"
            . "Há pendências críticas que podem afetar renovações do CEBAS, "
            . "habilitação SUAS ou prestações de contas MROSC.\n\n"
            . "🔗 Acesse o painel para ver as pendências:\n{$url}\n\n"
            . "_Vivensi · Conformidade Contínua_";

        try {
            $evo    = new EvolutionApiService($config);
            $result = $evo->sendMessage($phone, $message, null, 3);

            if (isset($result['error'])) {
                Log::warning('conformidade:alert-critical falha no envio', [
                    'tenant_id' => $tenant->id,
                    'error'     => $result['error'],
                ]);
                return false;
            }

            Log::info('conformidade:alert-critical enviado', [
                'tenant_id' => $tenant->id,
                'indice'    => $indice,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('conformidade:alert-critical exceção', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
