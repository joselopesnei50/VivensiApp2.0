<?php

namespace Tests\Feature\Radar;

use App\Models\RadarFinding;
use App\Models\RadarMatch;
use App\Models\Tenant;
use App\Services\Radar\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchingService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MatchingService();

        $this->tenant = Tenant::create([
            'name'             => 'ONG Teste',
            'document'         => '12345678000100',
            'type'             => 'ngo',
            'radar_ibge_code'  => '3550308',
            'radar_areas'      => ['assistência social', 'criança e adolescente'],
        ]);
    }

    private function makeFinding(array $attrs = []): RadarFinding
    {
        return RadarFinding::create(array_merge([
            'source'          => 'querido_diario',
            'territory_ibge'  => '3550308',
            'dedupe_hash'     => hash('sha256', uniqid('', true)),
            'title'           => 'Chamamento público',
            'excerpt'         => 'Chamamento público para organizações de assistência social.',
            'source_url'      => 'https://diario.sp.gov.br/001.pdf',
            'published_at'    => '2026-07-22',
            'keyword_matched' => '"chamamento público"',
            'raw_payload'     => '{}',
            'status'          => 'aprovado',
        ], $attrs));
    }

    public function test_territory_match_gives_50_points(): void
    {
        $finding = $this->makeFinding(['territory_ibge' => '3550308']);
        $tenant  = tap($this->tenant)->update(['radar_areas' => []]);

        $result = $this->service->score($tenant->fresh(), $finding);

        $this->assertSame(50, $result['score']);
        $this->assertContains('territory_match', $result['reasons']);
    }

    public function test_no_territory_match_gives_zero_base(): void
    {
        $finding = $this->makeFinding(['territory_ibge' => '3304557', 'source' => 'querido_diario']);
        $tenant  = tap($this->tenant)->update(['radar_areas' => []]);

        $result = $this->service->score($tenant->fresh(), $finding);

        $this->assertSame(0, $result['score']);
    }

    public function test_transferegov_global_gives_20_points(): void
    {
        $finding = $this->makeFinding([
            'source'         => 'transferegov',
            'territory_ibge' => null,
        ]);
        $tenant = tap($this->tenant)->update(['radar_areas' => []]);

        $result = $this->service->score($tenant->fresh(), $finding);

        $this->assertSame(20, $result['score']);
        $this->assertContains('transferegov_global', $result['reasons']);
    }

    public function test_area_match_adds_10_per_area(): void
    {
        $finding = $this->makeFinding([
            'territory_ibge' => '3304557', // different territory
            'excerpt'        => 'Chamamento para criança e adolescente e assistência social.',
        ]);

        $result = $this->service->score($this->tenant, $finding);

        $this->assertSame(20, $result['score']); // 2 areas × 10
    }

    public function test_combined_score_territory_plus_areas(): void
    {
        $finding = $this->makeFinding([
            'territory_ibge' => '3550308',
            'excerpt'        => 'Chamamento voltado para assistência social em São Paulo.',
        ]);

        $result = $this->service->score($this->tenant, $finding);

        $this->assertSame(60, $result['score']); // territory 50 + 1 area 10
    }

    public function test_score_capped_at_100(): void
    {
        $tenant = tap($this->tenant)->update([
            'radar_areas' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        ]);

        $finding = $this->makeFinding([
            'territory_ibge' => '3550308',
            'excerpt'        => 'a b c d e f g',
        ]);

        $result = $this->service->score($tenant->fresh(), $finding);

        $this->assertSame(100, $result['score']);
    }

    public function test_generate_for_finding_creates_match(): void
    {
        $finding = $this->makeFinding(['territory_ibge' => '3550308']);

        $created = $this->service->generateForFinding($finding);

        $this->assertSame(1, $created);
        $this->assertDatabaseHas('radar_matches', [
            'tenant_id'        => $this->tenant->id,
            'radar_finding_id' => $finding->id,
        ]);
    }

    public function test_rejected_finding_is_not_matched(): void
    {
        $finding = $this->makeFinding([
            'territory_ibge' => '3550308',
            'status'         => 'rejeitado',
        ]);

        $total = $this->service->generateAll();

        $this->assertSame(0, $total);
        $this->assertDatabaseCount('radar_matches', 0);
    }

    public function test_finding_from_different_territory_not_matched_without_areas(): void
    {
        $tenant = tap($this->tenant)->update([
            'radar_ibge_code' => '3550308',
            'radar_areas'     => [],
        ]);

        $finding = $this->makeFinding([
            'territory_ibge' => '3304557',
            'source'         => 'querido_diario',
        ]);

        $created = $this->service->generateForFinding($finding);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('radar_matches', 0);
    }

    public function test_generate_for_finding_is_idempotent(): void
    {
        $finding = $this->makeFinding(['territory_ibge' => '3550308']);

        $this->service->generateForFinding($finding);
        $this->service->generateForFinding($finding);

        $this->assertDatabaseCount('radar_matches', 1);
    }
}
