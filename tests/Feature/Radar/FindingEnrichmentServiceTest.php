<?php

namespace Tests\Feature\Radar;

use App\Models\RadarFinding;
use App\Services\DeepSeekService;
use App\Services\Radar\FindingEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeFinding(array $attrs = []): RadarFinding
    {
        return RadarFinding::create(array_merge([
            'source'          => 'querido_diario',
            'territory_ibge'  => '3550308',
            'dedupe_hash'     => hash('sha256', uniqid('', true)),
            'title'           => 'Chamamento público',
            'excerpt'         => 'A Prefeitura abre chamamento público para organizações de assistência social com prazo até 31/08/2026 e valor de R$ 50.000,00.',
            'source_url'      => 'https://diario.sp.gov.br/001.pdf',
            'published_at'    => '2026-07-22',
            'keyword_matched' => '"chamamento público"',
            'raw_payload'     => '{}',
            'status'          => 'novo',
        ], $attrs));
    }

    private function fakeDeepSeek(array $response): DeepSeekService
    {
        $mock = $this->createMock(DeepSeekService::class);
        $mock->method('chat')->willReturn([
            'choices' => [['message' => ['content' => json_encode($response)]]],
        ]);
        return $mock;
    }

    public function test_enrich_fills_all_fields_from_ai_response(): void
    {
        $finding = $this->makeFinding();
        $ds      = $this->fakeDeepSeek([
            'areas'          => ['assistência social'],
            'object_summary' => 'Chamamento para OSCs de assistência social.',
            'deadline'       => '2026-08-31',
            'value_total'    => 50000.00,
            'is_relevant'    => true,
        ]);

        $service = new FindingEnrichmentService($ds);
        $result  = $service->enrich($finding);

        $this->assertTrue($result);
        $finding->refresh();

        $this->assertNotNull($finding->ai_processed_at);
        $this->assertTrue($finding->is_relevant);
        $this->assertEquals(['assistência social'], $finding->areas);
        $this->assertEquals('Chamamento para OSCs de assistência social.', $finding->object_summary);
        $this->assertEquals('2026-08-31', $finding->deadline->format('Y-m-d'));
        $this->assertEquals('50000.00', $finding->value_total);
    }

    public function test_null_deadline_is_stored_as_null(): void
    {
        $finding = $this->makeFinding([
            'excerpt' => 'Chamamento para assistência social sem prazo definido.',
        ]);
        $ds = $this->fakeDeepSeek([
            'areas'          => ['assistência social'],
            'object_summary' => 'Chamamento.',
            'deadline'       => null,
            'value_total'    => null,
            'is_relevant'    => true,
        ]);

        $service = new FindingEnrichmentService($ds);
        $service->enrich($finding);

        $this->assertNull($finding->fresh()->deadline);
        $this->assertNull($finding->fresh()->value_total);
    }

    public function test_invalid_date_format_stored_as_null(): void
    {
        $finding = $this->makeFinding();
        $ds      = $this->fakeDeepSeek([
            'areas'          => [],
            'object_summary' => null,
            'deadline'       => 'agosto de 2026',
            'value_total'    => null,
            'is_relevant'    => false,
        ]);

        $service = new FindingEnrichmentService($ds);
        $service->enrich($finding);

        $this->assertNull($finding->fresh()->deadline);
    }

    public function test_already_processed_finding_is_skipped(): void
    {
        $finding = $this->makeFinding(['ai_processed_at' => now()]);

        $ds = $this->createMock(DeepSeekService::class);
        $ds->expects($this->never())->method('chat');

        $service = new FindingEnrichmentService($ds);
        $result  = $service->enrich($finding);

        $this->assertFalse($result);
    }

    public function test_deepseek_error_returns_false_and_leaves_unprocessed(): void
    {
        $finding = $this->makeFinding();

        $ds = $this->createMock(DeepSeekService::class);
        $ds->method('chat')->willReturn(['error' => 'ai_not_configured']);

        $service = new FindingEnrichmentService($ds);
        $result  = $service->enrich($finding);

        $this->assertFalse($result);
        $this->assertNull($finding->fresh()->ai_processed_at);
    }

    public function test_matching_uses_ai_areas_over_keyword_search(): void
    {
        // Finding enriched by AI with specific area
        $finding = $this->makeFinding([
            'excerpt' => 'Texto sem a palavra área aqui.',
            'areas'   => ['assistência social'],
            'ai_processed_at' => now(),
        ]);

        $tenant = \App\Models\Tenant::create([
            'name'            => 'ONG Teste',
            'document'        => '12345678000100',
            'type'            => 'ngo',
            'radar_ibge_code' => '9999999',
            'radar_areas'     => ['assistência social'],
        ]);

        $service = new \App\Services\Radar\MatchingService();
        $result  = $service->score($tenant, $finding);

        // Should match via ai_area (15 points) despite no territory match
        $this->assertGreaterThan(0, $result['score']);
        $this->assertContains('ai_area:assistência social', $result['reasons']);
    }
}
