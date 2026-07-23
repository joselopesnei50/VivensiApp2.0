<?php

namespace Tests\Feature\Radar;

use App\Jobs\Radar\CollectRadarFindings;
use App\Models\RadarFinding;
use App\Models\RadarTerritory;
use App\Services\Radar\QueridoDiarioService;
use App\Services\Radar\TransferegovService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectRadarTest extends TestCase
{
    use RefreshDatabase;

    private function makeFinding(array $overrides = []): array
    {
        return array_merge([
            'source'          => 'querido_diario',
            'territory_ibge'  => '3550308',
            'title'           => 'Chamamento público OSC',
            'excerpt'         => 'Prefeitura abre chamamento público para organizações da sociedade civil.',
            'source_url'      => 'https://diario.exemplo.gov.br/2026/07/22/edicao-001.pdf',
            'published_at'    => '2026-07-22',
            'keyword_matched' => '"chamamento público"',
            'raw_payload'     => ['test' => true],
        ], $overrides);
    }

    public function test_persist_creates_finding_with_status_novo(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => true,
        ]);

        $finding = $this->makeFinding();

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->method('search')->willReturn([$finding]);
        $tgov->method('fetchChamamentos')->willReturn([]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);

        $this->assertDatabaseHas('radar_findings', [
            'source' => 'querido_diario',
            'status' => 'novo',
            'title'  => 'Chamamento público OSC',
        ]);
    }

    public function test_deduplication_prevents_duplicate_on_second_run(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => true,
        ]);

        $finding = $this->makeFinding();

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->method('search')->willReturn([$finding]);
        $tgov->method('fetchChamamentos')->willReturn([]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);
        $job->handle($qd, $tgov);

        $this->assertDatabaseCount('radar_findings', 1);
    }

    public function test_two_different_findings_are_persisted(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => true,
        ]);

        $f1 = $this->makeFinding(['source_url' => 'https://diario.exemplo.gov.br/001.pdf', 'excerpt' => 'Primeiro achado de teste único.']);
        $f2 = $this->makeFinding(['source_url' => 'https://diario.exemplo.gov.br/002.pdf', 'excerpt' => 'Segundo achado completamente diferente.']);

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->method('search')->willReturn([$f1, $f2]);
        $tgov->method('fetchChamamentos')->willReturn([]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);

        $this->assertDatabaseCount('radar_findings', 2);
    }

    public function test_transferegov_findings_are_persisted(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => true,
        ]);

        $chamamento = $this->makeFinding([
            'source'         => 'transferegov',
            'territory_ibge' => null,
            'excerpt'        => 'Chamamento público federal para OSCs atuantes em assistência social.',
            'source_url'     => 'https://transferegov.gov.br/chamamentos/999',
        ]);

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->method('search')->willReturn([]);
        $tgov->method('fetchChamamentos')->willReturn([$chamamento]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);

        $this->assertDatabaseHas('radar_findings', [
            'source' => 'transferegov',
            'status' => 'novo',
        ]);
    }

    public function test_territories_last_collected_at_is_updated(): void
    {
        $territory = RadarTerritory::create([
            'ibge_code'          => '3550308',
            'name'               => 'São Paulo',
            'uf'                 => 'SP',
            'active'             => true,
            'last_collected_at'  => null,
        ]);

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->method('search')->willReturn([]);
        $tgov->method('fetchChamamentos')->willReturn([]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);

        $this->assertNotNull($territory->fresh()->last_collected_at);
    }

    public function test_inactive_territory_is_skipped(): void
    {
        RadarTerritory::create([
            'ibge_code' => '3550308',
            'name'      => 'São Paulo',
            'uf'        => 'SP',
            'active'    => false,
        ]);

        $qd   = $this->createMock(QueridoDiarioService::class);
        $tgov = $this->createMock(TransferegovService::class);

        $qd->expects($this->never())->method('search');
        $tgov->method('fetchChamamentos')->willReturn([]);

        $job = new CollectRadarFindings('3550308');
        $job->handle($qd, $tgov);

        $this->assertDatabaseCount('radar_findings', 0);
    }

    public function test_build_dedupe_hash_is_deterministic(): void
    {
        $hash1 = RadarFinding::buildDedupeHash('querido_diario', 'https://ex.com/1', '2026-07-22', 'Texto do trecho.');
        $hash2 = RadarFinding::buildDedupeHash('querido_diario', 'https://ex.com/1', '2026-07-22', 'Texto do trecho.');

        $this->assertSame($hash1, $hash2);
    }

    public function test_build_dedupe_hash_differs_for_different_inputs(): void
    {
        $hash1 = RadarFinding::buildDedupeHash('querido_diario', 'https://ex.com/1', '2026-07-22', 'Texto A.');
        $hash2 = RadarFinding::buildDedupeHash('querido_diario', 'https://ex.com/1', '2026-07-22', 'Texto B.');

        $this->assertNotSame($hash1, $hash2);
    }
}
