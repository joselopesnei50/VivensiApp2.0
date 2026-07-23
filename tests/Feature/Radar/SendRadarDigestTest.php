<?php

namespace Tests\Feature\Radar;

use App\Jobs\Radar\SendRadarDigest;
use App\Models\RadarFinding;
use App\Models\RadarMatch;
use App\Models\RadarNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendRadarDigestTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $attrs = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'                  => 'ONG Digest Teste',
            'document'              => '12345678000199',
            'type'                  => 'ngo',
            'subscription_status'   => 'active',
            'radar_ibge_code'       => '3550308',
            'radar_digest_channel'  => 'email',
            'radar_min_score'       => 30,
        ], $attrs));
    }

    private function makeUser(int $tenantId): User
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name'      => 'Admin ONG',
            'email'     => 'admin@ong.test',
            'password'  => bcrypt('secret'),
            'role'      => 'admin',
            'status'    => 'active',
        ]);
    }

    private function makeFinding(array $attrs = []): RadarFinding
    {
        return RadarFinding::create(array_merge([
            'source'          => 'querido_diario',
            'territory_ibge'  => '3550308',
            'dedupe_hash'     => hash('sha256', uniqid('', true)),
            'title'           => 'Chamamento público OSC',
            'excerpt'         => 'Chamamento para organizações de assistência social.',
            'source_url'      => 'https://diario.sp.gov.br/' . uniqid() . '.pdf',
            'published_at'    => '2026-07-22',
            'keyword_matched' => '"chamamento público"',
            'raw_payload'     => '{}',
            'status'          => 'aprovado',
        ], $attrs));
    }

    private function makeMatch(int $tenantId, int $findingId, int $score = 70): RadarMatch
    {
        return RadarMatch::create([
            'tenant_id'        => $tenantId,
            'radar_finding_id' => $findingId,
            'score'            => $score,
            'score_reasons'    => ['territory_match'],
        ]);
    }

    private function fakeBrevo(bool $returns = true): BrevoService
    {
        $mock = $this->createMock(BrevoService::class);
        $mock->method('sendEmail')->willReturn($returns);
        return $mock;
    }

    private function fakeQuota(bool $wouldExceed = false): EmailQuotaService
    {
        $mock = $this->createMock(EmailQuotaService::class);
        $mock->method('wouldExceed')->willReturn($wouldExceed);
        $mock->method('tryConsume')->willReturn(true);
        return $mock;
    }

    private function fakeAntiban(): AntiBanManager
    {
        return $this->createMock(AntiBanManager::class);
    }

    public function test_email_digest_is_sent_and_notification_recorded(): void
    {
        $tenant  = $this->makeTenant();
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 70);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->once())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertDatabaseHas('radar_notifications', [
            'tenant_id'        => $tenant->id,
            'radar_finding_id' => $finding->id,
            'channel'          => 'email',
        ]);
    }

    public function test_same_finding_not_sent_twice(): void
    {
        $tenant  = $this->makeTenant();
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 70);

        // Pre-record as already sent
        RadarNotification::create([
            'tenant_id'        => $tenant->id,
            'radar_finding_id' => $finding->id,
            'channel'          => 'email',
            'sent_at'          => now()->subDays(3),
        ]);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->never())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);
    }

    public function test_tenant_with_channel_desligado_receives_no_digest(): void
    {
        $tenant  = $this->makeTenant(['radar_digest_channel' => null]);
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 70);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->never())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertDatabaseCount('radar_notifications', 0);
    }

    public function test_finding_below_min_score_not_included(): void
    {
        $tenant  = $this->makeTenant(['radar_min_score' => 60]);
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 40); // score 40 < min 60

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->never())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertDatabaseCount('radar_notifications', 0);
    }

    public function test_email_quota_exceeded_blocks_send(): void
    {
        $tenant  = $this->makeTenant();
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 70);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(wouldExceed: true);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->never())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertDatabaseCount('radar_notifications', 0);
    }

    public function test_rejected_finding_never_appears_in_digest(): void
    {
        $tenant  = $this->makeTenant();
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding(['status' => 'rejeitado']);

        RadarMatch::create([
            'tenant_id'        => $tenant->id,
            'radar_finding_id' => $finding->id,
            'score'            => 80,
            'score_reasons'    => [],
        ]);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        $brevo->expects($this->never())->method('sendEmail');

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertDatabaseCount('radar_notifications', 0);
    }

    public function test_last_digest_at_updated_after_successful_send(): void
    {
        $tenant  = $this->makeTenant(['radar_last_digest_at' => null]);
        $user    = $this->makeUser($tenant->id);
        $finding = $this->makeFinding();
        $this->makeMatch($tenant->id, $finding->id, 70);

        $brevo   = $this->fakeBrevo(true);
        $quota   = $this->fakeQuota(false);
        $antiban = $this->fakeAntiban();

        app(SendRadarDigest::class)->handle($brevo, $quota, $antiban);

        $this->assertNotNull($tenant->fresh()->radar_last_digest_at);
    }
}
