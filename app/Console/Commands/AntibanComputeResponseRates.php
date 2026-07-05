<?php

namespace App\Console\Commands;

use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Anti-Ban 2026 — Fase 3. Taxa de resposta 7d por instância.
 *
 * Contas legítimas recebem resposta; contas de spam quase nunca. Este
 * comando calcula, por instância ativa, a razão entre "chats que também
 * responderam" e "chats para os quais enviamos" nos últimos 7 dias, e
 * grava em `settings.response_rate_7d`. Instâncias abaixo do limiar
 * saudável (RESPONSE_RATE_MIN_HEALTHY = 0.05 = 5%) emitem alerta.
 *
 * A métrica é calculada por TENANT (WhatsappMessage não tem instance_id
 * ainda) e replicada para cada instância ativa do tenant — quando um
 * tenant tem múltiplas instâncias, o valor é o mesmo em todas.
 *
 * Agendado semanalmente (domingo 03:00). Rodar manualmente:
 *   php artisan antiban:compute-response-rates [--dry-run]
 */
class AntibanComputeResponseRates extends Command
{
    protected $signature   = 'antiban:compute-response-rates {--dry-run : Calcula e loga sem gravar em settings}';
    protected $description = 'Calcula taxa de resposta 7d de cada instância ativa e grava em settings.response_rate_7d.';

    private const WINDOW_DAYS = 7;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $since  = now()->subDays(self::WINDOW_DAYS);

        // Uma métrica por tenant (WhatsappMessage não tem instance_id ainda);
        // cache local pra não recalcular quando o tenant tem >1 instância ativa.
        $ratesByTenant = [];
        $processed     = 0;
        $riskCount     = 0;

        // withoutGlobalScope('tenant') — comando roda sem Auth. BelongsToTenant
        // se auto-desativa no console (isRunningInConsole), mas mantemos
        // explícito por defesa em profundidade caso a política mude.
        $instances = WhatsappInstance::withoutGlobalScope('tenant')
            ->active()
            ->get();

        if ($instances->isEmpty()) {
            $this->info('Nenhuma instância ativa — nada a calcular.');
            return self::SUCCESS;
        }

        foreach ($instances as $instance) {
            $tenantId = $instance->tenant_id;

            if (!array_key_exists($tenantId, $ratesByTenant)) {
                $ratesByTenant[$tenantId] = $this->computeRateForTenant($tenantId, $since);
            }

            $rate = $ratesByTenant[$tenantId];
            $processed++;

            // rate === null significa "sem outbound no período" — não gravamos
            // porque ratio zero/dividido-por-zero não representa saúde real.
            if ($rate === null) {
                $this->line("· instance #{$instance->id} (tenant {$tenantId}): sem outbound no período, ignorando.");
                continue;
            }

            $isRisk = $rate < AntiBanManager::RESPONSE_RATE_MIN_HEALTHY;
            if ($isRisk) {
                $riskCount++;
            }

            $emoji = $isRisk ? '⚠️' : '✅';
            $this->line(sprintf(
                '%s instance #%d (tenant %d): response_rate_7d = %.2f%%',
                $emoji,
                $instance->id,
                $tenantId,
                $rate * 100
            ));

            if ($dryRun) {
                continue;
            }

            $settings = $instance->settings ?? [];
            // Cast explícito pra float — round() pode retornar int em Windows
            // quando o rate é 0 (0/N sem casas decimais).
            $settings['response_rate_7d']         = (float) round($rate, 4);
            $settings['response_rate_updated_at'] = now()->toIso8601String();
            $instance->update(['settings' => $settings]);

            if ($isRisk) {
                Log::critical('AntiBan: taxa de resposta 7d abaixo do limiar saudável', [
                    'instance_id'   => $instance->id,
                    'instance_name' => $instance->instance_name,
                    'tenant_id'     => $tenantId,
                    'rate'          => $rate,
                    'threshold'     => AntiBanManager::RESPONSE_RATE_MIN_HEALTHY,
                    'window_days'   => self::WINDOW_DAYS,
                ]);
            }
        }

        $this->info(sprintf(
            'Processadas: %d instâncias | Em risco: %d | Modo: %s',
            $processed,
            $riskCount,
            $dryRun ? 'dry-run (nada gravado)' : 'gravado'
        ));

        return self::SUCCESS;
    }

    /**
     * Calcula a taxa de resposta 7d de um tenant. Retorna null quando não
     * houve outbound no período (denominador zero — sem sinal de saúde).
     */
    private function computeRateForTenant(int $tenantId, \Carbon\Carbon $since): ?float
    {
        $sentChatIds = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $since)
            ->whereNotNull('chat_id')
            ->distinct()
            ->pluck('chat_id');

        $total = $sentChatIds->count();
        if ($total === 0) {
            return null;
        }

        $responded = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $since)
            ->whereIn('chat_id', $sentChatIds->all())
            ->distinct('chat_id')
            ->count('chat_id');

        return $responded / $total;
    }
}
