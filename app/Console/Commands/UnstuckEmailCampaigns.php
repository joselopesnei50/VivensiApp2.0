<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Detecta e resolve EmailCampaign travadas em status='sending' ha mais de
 * `--min-age` (default 60min). Cenario tipico: worker foi morto no meio do
 * disparo (deploy `sudo supervisorctl restart all`, OOM kill, host reboot),
 * o callback failed() do job NAO rodou, e a campanha ficou orfa.
 *
 * Estrategia:
 *  - Se `brevo_campaign_id` estiver preenchido: consulta o Brevo. Se a
 *    campanha existe la (stats retornam algo, mesmo `_no_data`), assume que
 *    foi disparada -> marca 'sent'. Se retornou vazio (404), o Brevo nunca
 *    processou -> marca 'error' + refunda cota.
 *  - Se `brevo_campaign_id` for null: job morreu antes de criar a campanha
 *    no Brevo -> marca 'error' + refunda cota.
 *
 * Rodar manualmente (dry-run):
 *   php artisan emails:unstuck-campaigns --dry-run
 *
 * Ativado no scheduler (App\Console\Kernel) a cada 15min.
 */
class UnstuckEmailCampaigns extends Command
{
    protected $signature = 'emails:unstuck-campaigns
                            {--min-age=60 : minutos de sending pra considerar travada}
                            {--dry-run : nao altera nada, so lista o que faria}';

    protected $description = 'Marca como sent/error campanhas de e-mail que travaram em status=sending';

    public function handle(BrevoService $brevo, EmailQuotaService $quota): int
    {
        $minAge = (int) $this->option('min-age');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMinutes($minAge);

        $stuck = EmailCampaign::withoutGlobalScopes()
            ->where('status', 'sending')
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('Nenhuma campanha travada encontrada (min-age=' . $minAge . 'min).');
            return self::SUCCESS;
        }

        $this->info('Encontradas ' . $stuck->count() . ' campanhas em sending ha mais de ' . $minAge . 'min.');
        if ($dryRun) $this->warn('DRY-RUN ativado — nenhuma alteracao sera feita.');

        $marked_sent = 0;
        $marked_error = 0;

        foreach ($stuck as $campaign) {
            $decision = $this->decide($campaign, $brevo);

            $this->line(sprintf(
                '  #%d "%s" (tenant %d, %s) -> %s',
                $campaign->id,
                mb_strimwidth($campaign->name, 0, 40, '...'),
                $campaign->tenant_id,
                $campaign->updated_at->diffForHumans(),
                $decision['action']
            ));

            if ($dryRun) continue;

            if ($decision['action'] === 'sent') {
                $campaign->update([
                    'status'  => 'sent',
                    'sent_at' => $campaign->updated_at, // aproxima; nao sabemos o instante exato
                    'error_message' => null,
                ]);
                $marked_sent++;
            } else {
                $campaign->update([
                    'status'        => 'error',
                    'error_message' => $decision['reason'],
                ]);
                $this->refundQuotaFor($campaign, $quota);
                $marked_error++;
            }

            Log::warning('UnstuckEmailCampaigns: campanha travada resolvida', [
                'campaign_id'      => $campaign->id,
                'tenant_id'        => $campaign->tenant_id,
                'action'           => $decision['action'],
                'reason'           => $decision['reason'] ?? null,
                'age_minutes'      => $campaign->updated_at->diffInMinutes(now()),
                'had_brevo_id'     => (bool) $campaign->brevo_campaign_id,
            ]);
        }

        $this->info("Resolvidas: {$marked_sent} marcadas como sent, {$marked_error} como error.");
        return self::SUCCESS;
    }

    /**
     * @return array{action:'sent'|'error', reason?:string}
     */
    private function decide(EmailCampaign $campaign, BrevoService $brevo): array
    {
        if (!$campaign->brevo_campaign_id) {
            return [
                'action' => 'error',
                'reason' => 'Job travou antes de criar a campanha no Brevo (worker morto no meio). Cota refundada — pode reenviar.',
            ];
        }

        try {
            $stats = $brevo->getBrevoEmailCampaignStats((int) $campaign->brevo_campaign_id);
        } catch (\Throwable $e) {
            // Se nao conseguimos falar com Brevo, deixamos como esta pro proximo tick
            Log::warning('UnstuckEmailCampaigns: erro ao consultar Brevo, adiando decisao', [
                'campaign_id' => $campaign->id,
                'brevo_id'    => $campaign->brevo_campaign_id,
                'error'       => $e->getMessage(),
            ]);
            return [
                'action' => 'error',
                'reason' => 'Nao foi possivel confirmar status no Brevo apos travamento. Verifique manualmente.',
            ];
        }

        // stats = [] -> Brevo nao conhece essa campanha (404 ou erro API) -> nao foi disparada
        // stats = ['_no_data' => true] -> Brevo confirma que existe mas ainda sem metricas -> foi disparada
        // stats = ['delivered' => X, ...] -> foi disparada e ja ha metricas
        if (empty($stats)) {
            return [
                'action' => 'error',
                'reason' => 'Campanha nao encontrada no Brevo (Brevo respondeu 404 ou erro). Cota refundada — pode reenviar.',
            ];
        }

        return ['action' => 'sent'];
    }

    private function refundQuotaFor(EmailCampaign $campaign, EmailQuotaService $quota): void
    {
        if (!$campaign->tenant_id || !$campaign->recipient_count) {
            // Sem recipient_count nao da pra saber quanto refundar. Log e segue —
            // nao vale a pena travar o unstuck por isso.
            return;
        }
        $tenant = Tenant::withoutGlobalScopes()->find($campaign->tenant_id);
        if (!$tenant) return;

        try {
            $quota->refund($tenant, (int) $campaign->recipient_count);
        } catch (\Throwable $e) {
            Log::warning('UnstuckEmailCampaigns: refund quota falhou', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
