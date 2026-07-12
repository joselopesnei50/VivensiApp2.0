<?php

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * C4 — Encryption at-rest PII em Lead e User (LGPD art. 46).
 */

uses(RefreshDatabase::class);

// ── Lead.phone ────────────────────────────────────────────────────────────────

it('Lead.phone: setter cifra + gera bidx', function () {
    $tenant = Tenant::factory()->create();
    $lead   = Lead::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Ana',
        'phone'     => '5511999998888',
        'phone_normalized' => '5511999998888',
    ]);

    // Raw no banco esta cifrado
    $raw = DB::table('leads')->where('id', $lead->id)->value('phone');
    expect($raw)->not->toBe('5511999998888');
    expect(Crypt::decryptString($raw))->toBe('5511999998888');

    // Bidx populado
    $bidx = DB::table('leads')->where('id', $lead->id)->value('phone_bidx');
    expect($bidx)->toBe(hash_hmac('sha256', '5511999998888', config('app.key')));

    // Getter descriptografa transparente
    expect($lead->fresh()->phone)->toBe('5511999998888');
});

it('Lead.phone: null limpa bidx tambem', function () {
    $tenant = Tenant::factory()->create();
    $lead   = Lead::create([
        'tenant_id' => $tenant->id,
        'name'      => 'X',
        'phone'     => '5511999998888',
        'phone_normalized' => '5511999998888',
    ]);

    $lead->update(['phone' => null]);

    $row = DB::table('leads')->where('id', $lead->id)->first();
    expect($row->phone)->toBeNull();
    expect($row->phone_bidx)->toBeNull();
});

// ── Lead.email ────────────────────────────────────────────────────────────────

it('Lead.email: setter normaliza + cifra + gera bidx', function () {
    $tenant = Tenant::factory()->create();
    $lead   = Lead::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Ana',
        'email'     => '  ANA@Exemplo.COM  ', // com espacos + maiuscula
    ]);

    // Raw cifrado
    $raw = DB::table('leads')->where('id', $lead->id)->value('email');
    expect(Crypt::decryptString($raw))->toBe('ana@exemplo.com');

    // Bidx do valor normalizado (nao do input original)
    $bidx = DB::table('leads')->where('id', $lead->id)->value('email_bidx');
    expect($bidx)->toBe(Lead::hashForBidx('ana@exemplo.com'));

    // Getter retorna normalizado
    expect($lead->fresh()->email)->toBe('ana@exemplo.com');
});

it('Lead.hashForBidx e deterministico', function () {
    $hash1 = Lead::hashForBidx('foo@bar.com');
    $hash2 = Lead::hashForBidx('foo@bar.com');
    expect($hash1)->toBe($hash2);
    expect(strlen($hash1))->toBe(64);
});

// ── LeadService.findOrCreateByEmail via bidx ─────────────────────────────────

it('LeadService.findOrCreateByEmail encontra lead cifrado por bidx', function () {
    $tenant  = Tenant::factory()->create();
    $service = new LeadService();

    // Primeiro cria
    $lead1 = $service->findOrCreateByEmail($tenant, 'foo@bar.com', ['name' => 'Foo']);

    // Segundo call — deve reencontrar mesmo cifrado
    $lead2 = $service->findOrCreateByEmail($tenant, 'foo@bar.com', ['name' => 'Foo Updated']);

    expect($lead2->id)->toBe($lead1->id);
});

it('LeadService.findOrCreateByEmail normaliza case', function () {
    $tenant  = Tenant::factory()->create();
    $service = new LeadService();

    $lead1 = $service->findOrCreateByEmail($tenant, 'foo@bar.com');
    $lead2 = $service->findOrCreateByEmail($tenant, 'FOO@BAR.COM');

    expect($lead2->id)->toBe($lead1->id);
});

// ── User.phone ────────────────────────────────────────────────────────────────

it('User.phone: setter cifra + gera bidx', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
        'phone'     => '5511987654321',
    ]);

    $raw  = DB::table('users')->where('id', $user->id)->value('phone');
    $bidx = DB::table('users')->where('id', $user->id)->value('phone_bidx');

    expect($raw)->not->toBe('5511987654321');
    expect(Crypt::decryptString($raw))->toBe('5511987654321');
    expect($bidx)->toBe(hash_hmac('sha256', '5511987654321', config('app.key')));
    expect($user->fresh()->phone)->toBe('5511987654321');
});

// ── Plaintext legado (compat pre-backfill) ────────────────────────────────────

it('Lead.phone: getter aceita plaintext legado sem falhar', function () {
    $tenant = Tenant::factory()->create();
    $lead   = Lead::create([
        'tenant_id' => $tenant->id,
        'name'      => 'X',
        'phone'     => 'temp',
        'phone_normalized' => 'temp',
    ]);

    // Simula linha legada: raw plaintext direto
    DB::table('leads')->where('id', $lead->id)->update(['phone' => '5511999998888']);

    // Getter deve retornar plaintext como veio (sem exception)
    expect($lead->fresh()->phone)->toBe('5511999998888');
});

// ── Backfill command ─────────────────────────────────────────────────────────

it('pii:backfill-encryption cifra plaintext legado em Lead', function () {
    $tenant = Tenant::factory()->create();

    // Insere plaintext direto (bypassa mutator)
    DB::table('leads')->insert([
        'tenant_id'        => $tenant->id,
        'name'             => 'Legado',
        'phone'            => '5511999998888',
        'phone_normalized' => '5511999998888',
        'email'            => 'legado@teste.com',
        'status'           => 'pending',
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    $this->artisan('pii:backfill-encryption', ['--model' => 'lead'])
        ->assertSuccessful();

    $row = DB::table('leads')->where('name', 'Legado')->first();

    // phone e email agora cifrados
    expect(Crypt::decryptString($row->phone))->toBe('5511999998888');
    expect(Crypt::decryptString($row->email))->toBe('legado@teste.com');

    // bidx populados
    expect($row->phone_bidx)->toBe(hash_hmac('sha256', '5511999998888', config('app.key')));
    expect($row->email_bidx)->toBe(hash_hmac('sha256', 'legado@teste.com', config('app.key')));
});

it('pii:backfill-encryption e idempotente (2 runs nao corrompem)', function () {
    $tenant = Tenant::factory()->create();

    DB::table('leads')->insert([
        'tenant_id'        => $tenant->id,
        'name'             => 'Idem',
        'phone'            => '5511999997777',
        'phone_normalized' => '5511999997777',
        'status'           => 'pending',
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    $this->artisan('pii:backfill-encryption', ['--model' => 'lead'])->assertSuccessful();
    $firstCipher = DB::table('leads')->where('name', 'Idem')->value('phone');

    $this->artisan('pii:backfill-encryption', ['--model' => 'lead'])->assertSuccessful();
    $secondCipher = DB::table('leads')->where('name', 'Idem')->value('phone');

    // Nao mudou (segunda run detectou que ja esta cifrado e pulou)
    expect($secondCipher)->toBe($firstCipher);

    // Descrifragem ainda funciona
    expect(Crypt::decryptString($secondCipher))->toBe('5511999997777');
});

it('pii:backfill-encryption --dry-run nao altera dados', function () {
    $tenant = Tenant::factory()->create();

    DB::table('leads')->insert([
        'tenant_id'        => $tenant->id,
        'name'             => 'Dry',
        'phone'            => '5511999996666',
        'phone_normalized' => '5511999996666',
        'status'           => 'pending',
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    $this->artisan('pii:backfill-encryption', ['--model' => 'lead', '--dry-run' => true])
        ->assertSuccessful();

    $row = DB::table('leads')->where('name', 'Dry')->first();
    expect($row->phone)->toBe('5511999996666'); // ainda plaintext
    expect($row->phone_bidx)->toBeNull();
});

it('pii:backfill-encryption cifra User.phone', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    // Insere phone plaintext bypassando mutator
    DB::table('users')->where('id', $user->id)->update(['phone' => '5511987654321']);

    $this->artisan('pii:backfill-encryption', ['--model' => 'user'])
        ->assertSuccessful();

    $row = DB::table('users')->where('id', $user->id)->first();
    expect(Crypt::decryptString($row->phone))->toBe('5511987654321');
    expect($row->phone_bidx)->toBe(hash_hmac('sha256', '5511987654321', config('app.key')));
});
