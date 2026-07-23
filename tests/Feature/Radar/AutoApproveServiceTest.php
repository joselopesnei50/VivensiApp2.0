<?php

namespace Tests\Feature\Radar;

use App\Models\RadarFinding;
use App\Models\RadarNotification;
use App\Models\RadarTerritory;
use App\Services\Radar\AutoApproveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoApproveServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTerritory(): RadarTerritory
    {
        return RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => true,
        ]);
    }

    private function makeFinding(array $attrs = []): RadarFinding
    {
        return RadarFinding::create(array_merge([
            'source'          => 'querido_diario',
            'territory_ibge'  => '3550308',
            'dedupe_hash'     => hash('sha256', uniqid('', true)),
            'title'           => 'Chamamento público OSC',
            'excerpt'         => 'Chamamento para assistência social.',
            'source_url'      => 'https://diario.sp.gov.br/' . uniqid() . '.pdf',
            'published_at'    => '2026-07-22',
            'keyword_matched' => '"chamamento público"',
            'raw_payload'     => '{}',
            'status'          => 'novo',
            'is_relevant'     => true,
            'ai_processed_at' => now(),
        ], $attrs));
    }

    private function addFeedbacks(int $findingId, int $util, int $naoUtil): void
    {
        // Create different tenants for each feedback
        $tenantBase = 90000;
        for ($i = 0; $i < $util; $i++) {
            RadarNotification::create([
                'tenant_id'        => $tenantBase + $i,
                'radar_finding_id' => $findingId,
                'channel'          => 'painel',
                'sent_at'          => now(),
                'feedback'         => 'util',
                'feedback_at'      => now(),
            ]);
        }
        for ($i = 0; $i < $naoUtil; $i++) {
            RadarNotification::create([
                'tenant_id'        => $tenantBase + 1000 + $i,
                'radar_finding_id' => $findingId,
                'channel'          => 'painel',
                'sent_at'          => now(),
                'feedback'         => 'nao_util',
                'feedback_at'      => now(),
            ]);
        }
    }

    public function test_auto_approve_requires_sufficient_feedback_volume(): void
    {
        $this->makeTerritory();
        $finding = $this->makeFinding();

        // Only 5 feedbacks — below min of 10
        $this->addFeedbacks($finding->id, 5, 0);

        $service = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(0, $approved);
        $this->assertEquals('novo', $finding->fresh()->status);
    }

    public function test_auto_approve_requires_min_util_rate(): void
    {
        $this->makeTerritory();
        $finding = $this->makeFinding();

        // 10 feedbacks but only 50% útil — below 70% threshold
        $this->addFeedbacks($finding->id, 5, 5);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(0, $approved);
        $this->assertEquals('novo', $finding->fresh()->status);
    }

    public function test_auto_approves_when_all_criteria_met(): void
    {
        $this->makeTerritory();
        $finding = $this->makeFinding();

        // 10 feedbacks, 80% útil — above both thresholds
        $this->addFeedbacks($finding->id, 8, 2);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(1, $approved);
        $fresh = $finding->fresh();
        $this->assertEquals('aprovado', $fresh->status);
        $this->assertTrue($fresh->auto_approved);
        $this->assertNotNull($fresh->curated_at);
    }

    public function test_low_precision_keyword_never_auto_approved(): void
    {
        $this->makeTerritory();
        $finding = $this->makeFinding([
            'keyword_matched' => '"organização da sociedade civil"',
        ]);

        // Even with great feedback — not in high_precision list
        $this->addFeedbacks($finding->id, 10, 0);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(0, $approved);
    }

    public function test_is_relevant_false_never_auto_approved(): void
    {
        $this->makeTerritory();
        $finding = $this->makeFinding(['is_relevant' => false]);

        $this->addFeedbacks($finding->id, 10, 0);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(0, $approved);
    }

    public function test_inactive_territory_not_auto_approved(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => false,
        ]);

        $finding = $this->makeFinding();
        $this->addFeedbacks($finding->id, 10, 0);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(0, $approved);
    }

    public function test_transferegov_global_auto_approved_without_territory(): void
    {
        // No territory needed for transferegov global
        $finding = $this->makeFinding([
            'source'         => 'transferegov',
            'territory_ibge' => null,
        ]);

        $this->addFeedbacks($finding->id, 8, 2);

        $service  = app(AutoApproveService::class);
        $approved = $service->run();

        $this->assertSame(1, $approved);
    }

    public function test_keyword_quality_stats_calculates_rates(): void
    {
        $finding = $this->makeFinding();
        $this->addFeedbacks($finding->id, 7, 3);

        $service = app(AutoApproveService::class);
        $stats   = $service->keywordQualityStats();

        $kw = '"chamamento público"';
        $this->assertArrayHasKey($kw, $stats);
        $this->assertSame(10, $stats[$kw]['total']);
        $this->assertSame(7, $stats[$kw]['util_count']);
        $this->assertEqualsWithDelta(0.7, $stats[$kw]['util_rate'], 0.01);
    }

    public function test_flagged_keyword_detected_when_nao_util_high(): void
    {
        $finding = $this->makeFinding();
        // 40% não útil — above 30% flag threshold
        $this->addFeedbacks($finding->id, 6, 4);

        $service = app(AutoApproveService::class);
        $stats   = $service->keywordQualityStats();

        $this->assertTrue($stats['"chamamento público"']['flagged']);
    }
}
