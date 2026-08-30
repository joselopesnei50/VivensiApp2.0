<?php

use App\Models\MeetingBooking;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regressao — auditoria 2026-08-29 P3.b (baixa).
 *
 * P3.b.1: VolunteerCertificate ganhou tenant_id + BelongsToTenant.
 *         Fecha IDOR cross-tenant no download de certificado.
 * P3.b.3: MeetingBooking usa confirmation_token_bidx (HMAC + hash_equals).
 *         Se DB vazar, token plaintext sem app.key nao serve.
 */

uses(RefreshDatabase::class);

// ── P3.b.1 — VolunteerCertificate ────────────────────────────────────────────

it('VolunteerCertificate scoped por tenant: user de A nao ve cert de B', function () {
    // PHPUnit roda em CLI e BelongsToTenant auto-desliga em runningInConsole.
    // Reflection forca o caminho HTTP (mesmo padrao usado em CrossTenantIdorTest).
    $prop = new ReflectionProperty($this->app, 'isRunningInConsole');
    $prop->setAccessible(true);
    $prop->setValue($this->app, false);

    $ta = Tenant::factory()->create();
    $tb = Tenant::factory()->create();
    $ua = User::factory()->create(['tenant_id' => $ta->id, 'role' => 'ngo']);

    $va = Volunteer::create(['tenant_id' => $ta->id, 'name' => 'A Volunt', 'email' => 'a@a.com', 'status' => 'active']);
    $vb = Volunteer::create(['tenant_id' => $tb->id, 'name' => 'B Volunt', 'email' => 'b@b.com', 'status' => 'active']);

    // Cria certs manualmente com tenant_id explicito pra simular pos-backfill
    $ca = VolunteerCertificate::create([
        'tenant_id'            => $ta->id,
        'volunteer_id'         => $va->id,
        'uuid'                 => 'uuid-a',
        'activity_description' => 'x',
        'hours'                => 1,
        'issued_at'            => now(),
        'file_path'            => 'certificates/a.pdf',
    ]);
    $cb = VolunteerCertificate::create([
        'tenant_id'            => $tb->id,
        'volunteer_id'         => $vb->id,
        'uuid'                 => 'uuid-b',
        'activity_description' => 'y',
        'hours'                => 2,
        'issued_at'            => now(),
        'file_path'            => 'certificates/b.pdf',
    ]);

    $this->actingAs($ua);

    // Autenticado como user do tenant A — global scope filtra pra tenant A
    expect(VolunteerCertificate::find($ca->id))->not->toBeNull();
    expect(VolunteerCertificate::find($cb->id))->toBeNull();
    expect(VolunteerCertificate::count())->toBe(1);
});

it('VolunteerCertificate auto-preenche tenant_id no create via BelongsToTenant', function () {
    $t = Tenant::factory()->create();
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $v = Volunteer::create(['tenant_id' => $t->id, 'name' => 'V', 'email' => 'v@v.com', 'status' => 'active']);

    $this->actingAs($u);

    $cert = VolunteerCertificate::create([
        'volunteer_id'         => $v->id,
        'uuid'                 => 'auto',
        'activity_description' => 'z',
        'hours'                => 5,
        'issued_at'            => now(),
        'file_path'            => 'certificates/auto.pdf',
    ]);

    expect((int) $cert->tenant_id)->toBe((int) $t->id);
});

// ── P3.b.3 — MeetingBooking token bidx ───────────────────────────────────────

it('MeetingBooking calcula bidx automaticamente no creating', function () {
    $b = MeetingBooking::create([
        'name' => 'X', 'email' => 'x@x.com', 'phone' => '11', 'notes' => null,
        'meeting_date' => now()->addDays(7)->toDateString(),
        'meeting_time' => '10:00',
        'status' => 'confirmed',
    ]);

    expect($b->confirmation_token)->not->toBeNull();
    expect(strlen($b->confirmation_token))->toBe(40);
    expect($b->confirmation_token_bidx)->toBe(MeetingBooking::hashToken($b->confirmation_token));
});

it('MeetingBooking::hashToken e deterministico e usa app.key (HMAC)', function () {
    $t1 = MeetingBooking::hashToken('teste-token-x');
    $t2 = MeetingBooking::hashToken('teste-token-x');
    $t3 = MeetingBooking::hashToken('outro-token');

    expect($t1)->toBe($t2);
    expect($t1)->not->toBe($t3);
    expect(strlen($t1))->toBe(64); // sha256 hex
});

it('cancel: token valido cancela reuniao (backwards-compat)', function () {
    $b = MeetingBooking::create([
        'name' => 'Cancelavel', 'email' => 'c@c.com', 'phone' => '11', 'notes' => null,
        'meeting_date' => now()->addDays(3)->toDateString(),
        'meeting_time' => '14:00',
        'status' => 'confirmed',
    ]);
    $token = $b->confirmation_token;

    $this->get("/agendar/cancelar/{$token}")->assertOk();

    $b->refresh();
    expect($b->status)->toBe('cancelled');
});

it('cancel: token invalido retorna 404 (nao vaza state)', function () {
    MeetingBooking::create([
        'name' => 'X', 'email' => 'x@x.com', 'phone' => '11', 'notes' => null,
        'meeting_date' => now()->addDays(3)->toDateString(),
        'meeting_time' => '14:00',
        'status' => 'confirmed',
    ]);

    $this->get('/agendar/cancelar/token-invalido-inexistente')->assertStatus(404);
});

it('cancel: reuniao ja cancelada retorna 404 (idempotente)', function () {
    $b = MeetingBooking::create([
        'name' => 'X', 'email' => 'x@x.com', 'phone' => '11', 'notes' => null,
        'meeting_date' => now()->addDays(3)->toDateString(),
        'meeting_time' => '14:00',
        'status' => 'cancelled',
    ]);

    $this->get("/agendar/cancelar/{$b->confirmation_token}")->assertStatus(404);
});
