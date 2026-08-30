<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use App\Services\LeadSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backfill da auditoria 2026-08-29 P2 (media) — limpa URLs armazenadas em
 * prospects.website que nao passam pelo filtro de scheme (javascript:,
 * data:, file:, //, etc). Aplica LeadSearchService::sanitizeExternalUrl
 * em cada linha e zera o campo se ficar null.
 */
class ProspectSanitizeWebsites extends Command
{
    protected $signature = 'prospect:sanitize-websites {--dry-run : So conta sem alterar} {--chunk=200}';
    protected $description = 'Zera URLs invalidas em prospects.website (auditoria XSS 2026-08-29 P2)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));

        $total   = 0;
        $limpos  = 0;
        $mantidos = 0;

        Prospect::withoutGlobalScopes()
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->select(['id', 'website'])
            ->chunkById($chunk, function ($chunk) use (&$total, &$limpos, &$mantidos, $dry) {
                foreach ($chunk as $p) {
                    $total++;
                    $safe = LeadSearchService::sanitizeExternalUrl($p->website);
                    if ($safe === $p->website) {
                        $mantidos++;
                        continue;
                    }
                    // Diferente — vai virar $safe (que pode ser null).
                    $limpos++;
                    if (!$dry) {
                        Prospect::withoutGlobalScopes()->where('id', $p->id)->update(['website' => $safe]);
                    }
                }
            });

        $tag = $dry ? '(dry-run)' : '';
        $this->info("prospect:sanitize-websites {$tag} — {$total} varridos | {$mantidos} OK | {$limpos} alterados");
        Log::info("prospect:sanitize-websites", compact('dry', 'total', 'mantidos', 'limpos'));

        return self::SUCCESS;
    }
}
