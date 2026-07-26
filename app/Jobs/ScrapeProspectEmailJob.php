<?php

namespace App\Jobs;

use App\Models\Prospect;
use App\Services\ProspectEmailScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Faz scraping best-effort do e-mail de um prospect a partir do site dele.
 *
 * Roda na queue 'emails' (mesma do SendEmailCampaignJob) porque é o worker
 * já dedicado a operações de e-mail — ver memória supervisor:
 * vivensi-worker-emails.
 *
 * Falha silenciosa: se não achar nada ou o site estiver fora do ar, apenas
 * loga em nível info e não sinaliza retry (é normal). Máximo 1 tentativa —
 * scraping repetido não melhora resultado.
 */
class ScrapeProspectEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public int $prospectId)
    {
    }

    public function handle(ProspectEmailScraperService $scraper): void
    {
        $prospect = Prospect::withoutGlobalScope('tenant')->find($this->prospectId);

        // Prospect deletado, sem site, ou já tem e-mail — nada a fazer.
        if (!$prospect || empty($prospect->website) || !empty($prospect->email)) {
            return;
        }

        try {
            $emails = $scraper->scrape($prospect->website);
        } catch (\Throwable $e) {
            Log::info('ScrapeProspectEmailJob: falha silenciosa', [
                'prospect_id' => $this->prospectId,
                'website'     => $prospect->website,
                'error'       => $e->getMessage(),
            ]);
            return;
        }

        if (empty($emails)) {
            // Não achou — normal. Marca com um "carimbo" pra não tentar de novo.
            $prospect->update(['email_found_at' => now()]);
            return;
        }

        // Pega o primeiro (já vem rankeado por prioridade).
        // opt_in permanece FALSE — user precisa confirmar consentimento LGPD
        // antes de qualquer envio.
        $prospect->update([
            'email'          => $emails[0],
            'email_opt_in'   => false,
            'email_source'   => 'scraped',
            'email_found_at' => now(),
        ]);

        Log::info('ScrapeProspectEmailJob: e-mail encontrado', [
            'prospect_id' => $this->prospectId,
            'email'       => $emails[0],
            'candidates'  => count($emails),
        ]);
    }
}
