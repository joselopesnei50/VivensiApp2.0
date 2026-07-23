<?php

namespace App\Services\Radar;

use App\Models\RadarFinding;
use App\Models\RadarNotification;
use App\Models\RadarTerritory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoApproveService
{
    private array  $highPrecisionKeywords;
    private int    $minFeedbackCount;
    private float  $minUtilRate;

    public function __construct()
    {
        $this->highPrecisionKeywords = config('radar.auto_approve.high_precision_keywords', []);
        $this->minFeedbackCount      = (int)   config('radar.auto_approve.min_feedback_count', 10);
        $this->minUtilRate           = (float) config('radar.auto_approve.min_util_rate', 0.70);
    }

    public function run(): int
    {
        $candidates = RadarFinding::where('status', 'novo')
            ->where('is_relevant', true)
            ->whereNotNull('ai_processed_at')
            ->get();

        $activeIbgeCodes = RadarTerritory::where('active', true)->pluck('ibge_code')->all();
        $qualityStats    = $this->keywordQualityStats();
        $approved        = 0;

        foreach ($candidates as $finding) {
            if ($this->shouldAutoApprove($finding, $activeIbgeCodes, $qualityStats)) {
                $finding->update([
                    'status'        => 'aprovado',
                    'auto_approved' => true,
                    'curated_at'    => now(),
                ]);
                $approved++;
            }
        }

        Log::info("AutoApproveService: {$approved} findings auto-aprovados.");

        return $approved;
    }

    private function shouldAutoApprove(RadarFinding $finding, array $activeIbgeCodes, array $stats): bool
    {
        // Keyword must be in high-precision list
        if (!in_array($finding->keyword_matched, $this->highPrecisionKeywords, true)) {
            return false;
        }

        // Territory must be active (Transferegov global findings always pass)
        if ($finding->territory_ibge !== null && !in_array($finding->territory_ibge, $activeIbgeCodes, true)) {
            return false;
        }

        // Regra de ouro: only auto-approve if keyword has sufficient feedback
        $keyStats = $stats[$finding->keyword_matched] ?? null;

        if ($keyStats === null || $keyStats['total'] < $this->minFeedbackCount) {
            return false;
        }

        if ($keyStats['util_rate'] < $this->minUtilRate) {
            return false;
        }

        return true;
    }

    public function keywordQualityStats(): array
    {
        $rows = DB::table('radar_notifications as rn')
            ->join('radar_findings as rf', 'rf.id', '=', 'rn.radar_finding_id')
            ->whereNotNull('rn.feedback')
            ->select(
                'rf.keyword_matched',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN rn.feedback = 'util' THEN 1 ELSE 0 END) as util_count"),
                DB::raw("SUM(CASE WHEN rn.feedback = 'nao_util' THEN 1 ELSE 0 END) as nao_util_count"),
            )
            ->groupBy('rf.keyword_matched')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $total    = (int) $row->total;
            $util     = (int) $row->util_count;
            $naoUtil  = (int) $row->nao_util_count;

            $stats[$row->keyword_matched] = [
                'total'        => $total,
                'util_count'   => $util,
                'nao_util_count' => $naoUtil,
                'util_rate'    => $total > 0 ? round($util / $total, 4) : 0.0,
                'nao_util_rate'=> $total > 0 ? round($naoUtil / $total, 4) : 0.0,
                'flagged'      => $total >= $this->minFeedbackCount
                    && ($total > 0 ? ($naoUtil / $total) : 0) >= (float) config('radar.auto_approve.flag_nao_util_rate', 0.30),
            ];
        }

        return $stats;
    }

    public function sourceQualityStats(): array
    {
        $rows = DB::table('radar_notifications as rn')
            ->join('radar_findings as rf', 'rf.id', '=', 'rn.radar_finding_id')
            ->whereNotNull('rn.feedback')
            ->select(
                'rf.source',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN rn.feedback = 'util' THEN 1 ELSE 0 END) as util_count"),
                DB::raw("SUM(CASE WHEN rn.feedback = 'nao_util' THEN 1 ELSE 0 END) as nao_util_count"),
            )
            ->groupBy('rf.source')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $total   = (int) $row->total;
            $util    = (int) $row->util_count;
            $naoUtil = (int) $row->nao_util_count;

            $stats[$row->source] = [
                'total'          => $total,
                'util_count'     => $util,
                'nao_util_count' => $naoUtil,
                'util_rate'      => $total > 0 ? round($util / $total, 4) : 0.0,
                'nao_util_rate'  => $total > 0 ? round($naoUtil / $total, 4) : 0.0,
            ];
        }

        return $stats;
    }
}
