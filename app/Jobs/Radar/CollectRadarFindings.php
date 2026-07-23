<?php

namespace App\Jobs\Radar;

use App\Models\RadarFinding;
use App\Models\RadarTerritory;
use App\Services\Radar\QueridoDiarioService;
use App\Services\Radar\TransferegovService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectRadarFindings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 120;
    public array $backoff = [30, 60];

    private string $ibgeCode;

    public function __construct(string $ibgeCode = '')
    {
        $this->ibgeCode = $ibgeCode;
        $this->onQueue('default');
    }

    public function handle(QueridoDiarioService $qd, TransferegovService $tgov): void
    {
        $since    = now()->subDays(config('radar.collect.days_back', 7))->toDateString();
        $keywords = config('radar.keywords', []);
        $saved    = 0;

        // ── Querido Diário ────────────────────────────────────────────────────
        $territories = $this->ibgeCode
            ? RadarTerritory::where('ibge_code', $this->ibgeCode)->where('active', true)->get()
            : RadarTerritory::where('active', true)->get();

        foreach ($territories as $territory) {
            foreach ($keywords as $keyword) {
                $findings = $qd->search($territory->ibge_code, $keyword, $since);

                foreach ($findings as $f) {
                    if ($this->persist($f)) {
                        $saved++;
                    }
                }
            }

            $territory->update(['last_collected_at' => now()]);
        }

        // ── Transferegov (global, sem filtro de território) ───────────────────
        foreach ($keywords as $keyword) {
            $findings = $tgov->fetchChamamentos($keyword, $since);

            foreach ($findings as $f) {
                if ($this->persist($f)) {
                    $saved++;
                }
            }
        }

        Log::info("CollectRadarFindings: {$saved} novos achados persistidos.", [
            'since' => $since,
        ]);
    }

    private function persist(array $f): bool
    {
        $hash = RadarFinding::buildDedupeHash(
            $f['source'],
            $f['source_url'],
            $f['published_at'],
            $f['excerpt'],
        );

        $inserted = RadarFinding::withoutGlobalScopes()
            ->insertOrIgnore([[
                'source'          => $f['source'],
                'territory_ibge'  => $f['territory_ibge'] ?? null,
                'dedupe_hash'     => $hash,
                'title'           => mb_substr($f['title'], 0, 255),
                'excerpt'         => $f['excerpt'],
                'source_url'      => $f['source_url'],
                'published_at'    => $f['published_at'],
                'keyword_matched' => $f['keyword_matched'],
                'raw_payload'     => json_encode($f['raw_payload']),
                'status'          => 'novo',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]]);

        return $inserted > 0;
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CollectRadarFindings falhou após todas as tentativas.', [
            'error' => $e->getMessage(),
            'ibge'  => $this->ibgeCode ?: 'todos',
        ]);
    }
}
