<?php

use App\Models\Prospect;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Auditoria 2026-08-29 P2 (media) — comando de backfill que limpa
 * prospects.website contendo scheme != http(s).
 */

uses(RefreshDatabase::class);

test('prospect:sanitize-websites zera URL javascript: mantendo URL http valida', function () {
    $t = Tenant::factory()->create();

    $bad = Prospect::create([
        'company_name' => 'Malicioso',
        'tenant_id'    => $t->id,
        'website'      => 'javascript:alert(1)',
        'status'       => 'raw',
    ]);
    $ok = Prospect::create([
        'company_name' => 'Legal',
        'tenant_id'    => $t->id,
        'website'      => 'https://example.com',
        'status'       => 'raw',
    ]);

    $this->artisan('prospect:sanitize-websites')
        ->assertExitCode(0);

    expect(Prospect::find($bad->id)->website)->toBeNull();
    expect(Prospect::find($ok->id)->website)->toBe('https://example.com');
});

test('dry-run nao altera nada', function () {
    $t = Tenant::factory()->create();
    Prospect::create([
        'company_name' => 'Malicioso',
        'tenant_id'    => $t->id,
        'website'      => 'data:text/html,<script>x</script>',
        'status'       => 'raw',
    ]);

    $this->artisan('prospect:sanitize-websites --dry-run')
        ->assertExitCode(0);

    expect(Prospect::first()->website)->toBe('data:text/html,<script>x</script>');
});
