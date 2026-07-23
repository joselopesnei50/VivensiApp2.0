<?php

namespace App\Services\Radar;

use App\Models\RadarFinding;
use App\Models\RadarMatch;
use App\Models\Tenant;

class MatchingService
{
    public function score(Tenant $tenant, RadarFinding $finding): array
    {
        $score   = 0;
        $reasons = [];

        // Territory: finding published in tenant's municipality
        if ($tenant->radar_ibge_code && $finding->territory_ibge === $tenant->radar_ibge_code) {
            $score  += 50;
            $reasons[] = 'territory_match';
        }

        // Transferegov findings are federal — relevant to all tenants
        if ($finding->source === 'transferegov' && $finding->territory_ibge === null) {
            $score  += 20;
            $reasons[] = 'transferegov_global';
        }

        // Area overlap: use AI-extracted areas when available (more precise),
        // fall back to keyword search in raw excerpt
        $tenantAreas  = $tenant->radar_areas ?? [];
        $findingAreas = $finding->areas ?? [];

        if (!empty($findingAreas)) {
            foreach ($tenantAreas as $tenantArea) {
                foreach ($findingAreas as $findingArea) {
                    if (str_contains(mb_strtolower($findingArea), mb_strtolower($tenantArea))
                        || str_contains(mb_strtolower($tenantArea), mb_strtolower($findingArea))) {
                        $score     = min(100, $score + 15);
                        $reasons[] = "ai_area:{$findingArea}";
                        break;
                    }
                }
            }
        } else {
            $excerpt = mb_strtolower($finding->excerpt ?? '');
            foreach ($tenantAreas as $area) {
                if ($area && str_contains($excerpt, mb_strtolower($area))) {
                    $score     = min(100, $score + 10);
                    $reasons[] = "area:{$area}";
                }
            }
        }

        return [
            'score'   => min(100, $score),
            'reasons' => $reasons,
        ];
    }

    public function generateForFinding(RadarFinding $finding): int
    {
        $tenants  = Tenant::whereNotNull('radar_ibge_code')->get();
        $created  = 0;

        foreach ($tenants as $tenant) {
            $result = $this->score($tenant, $finding);

            if ($result['score'] > 0) {
                $match = RadarMatch::updateOrCreate(
                    [
                        'tenant_id'        => $tenant->id,
                        'radar_finding_id' => $finding->id,
                    ],
                    [
                        'score'         => $result['score'],
                        'score_reasons' => $result['reasons'],
                    ]
                );

                if ($match->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    public function generateForTenant(Tenant $tenant): int
    {
        $total    = 0;
        $findings = RadarFinding::where('status', 'aprovado')->get();

        foreach ($findings as $finding) {
            $result = $this->score($tenant, $finding);

            if ($result['score'] > 0) {
                $match = RadarMatch::updateOrCreate(
                    [
                        'tenant_id'        => $tenant->id,
                        'radar_finding_id' => $finding->id,
                    ],
                    [
                        'score'         => $result['score'],
                        'score_reasons' => $result['reasons'],
                    ]
                );

                if ($match->wasRecentlyCreated) {
                    $total++;
                }
            }
        }

        return $total;
    }

    public function generateAll(): int
    {
        $total    = 0;
        $findings = RadarFinding::where('status', 'aprovado')->get();

        foreach ($findings as $finding) {
            $total += $this->generateForFinding($finding);
        }

        return $total;
    }
}
