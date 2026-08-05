<?php

use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappAntiBanAcceptance;
use App\Models\WhatsappInstance;
use App\Services\AntiBanTermService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

/**
 * P1 (2026-08-05) — travas de seguranca do broadcast de audio.
 *
 * Cobrimos as regras que impedem broadcast de audio virar spam com ban:
 *  - anti-ban aceito obrigatorio
 *  - audience=groups bloqueada
 *  - cadencia minima 20s
 *  - fluxo happy-path com aceite + cadencia OK persiste has_audio + fingerprint
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function basAcceptAntiBan(User $user): void
{
    $svc  = app(AntiBanTermService::class);
    $hash = $svc->currentHash() ?: hash('sha256', 'fallback');
    WhatsappAntiBanAcceptance::create([
        'tenant_id'   => $user->tenant_id,
        'user_id'     => $user->id,
        'version'     => $svc->currentVersion(),
        'terms_hash'  => $hash,
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'phpunit',
        'accepted_at' => now(),
    ]);
}

function basManager(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

function basAudioFile(): UploadedFile
{
    // UploadedFile::fake respeita o mimeType passado em getClientMimeType()
    // e a extensao aciona o validador `mimes` no controller.
    return UploadedFile::fake()->create('nota.mp3', 8, 'audio/mpeg');
}

it('rejeita broadcast de audio sem aceite do termo anti-ban', function () {
    Queue::fake();
    $user = basManager();
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience' => 'selected',
        'phones'   => '5511999990001',
        'cadence'  => 20,
        'broadcast_audio' => basAudioFile(),
    ]);
    $resp->assertRedirect(route('whatsapp.anti_ban.show'));
    expect(BroadcastCampaign::count())->toBe(0);
});

it('aceita broadcast de audio para grupos ate o cap de 10 grupos', function () {
    Queue::fake();
    $user = basManager();
    basAcceptAntiBan($user);
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'  => 'groups',
        'group_ids' => ['1@g.us', '2@g.us', '3@g.us'],
        'cadence'   => 20,
        'broadcast_audio' => basAudioFile(),
    ]);

    $resp->assertSessionHasNoErrors();
    expect(BroadcastCampaign::count())->toBe(1);
    expect(BroadcastCampaign::first()->has_audio)->toBeTrue();
});

it('rejeita broadcast de audio para mais de 10 grupos', function () {
    Queue::fake();
    $user = basManager();
    basAcceptAntiBan($user);
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $groupIds = array_map(fn($i) => "{$i}@g.us", range(1, 11)); // 11 grupos

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'  => 'groups',
        'group_ids' => $groupIds,
        'cadence'   => 20,
        'broadcast_audio' => basAudioFile(),
    ]);

    $resp->assertRedirect('/whatsapp/broadcast');
    expect(session('error'))->toContain('10 grupos');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita broadcast de audio com cadencia menor que 20s', function () {
    Queue::fake();
    $user = basManager();
    basAcceptAntiBan($user);
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience' => 'selected',
        'phones'   => '5511999990001',
        'cadence'  => 5,
        'broadcast_audio' => basAudioFile(),
    ]);

    $resp->assertRedirect('/whatsapp/broadcast');
    expect(session('error'))->toContain('cadência mínima');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('aceita broadcast de audio com aceite + cadencia 20s + audience valida', function () {
    Queue::fake();
    $user = basManager();
    basAcceptAntiBan($user);
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience' => 'selected',
        'phones'   => '5511999990001',
        'cadence'  => 20,
        'broadcast_audio' => basAudioFile(),
    ]);

    $resp->assertSessionHasNoErrors();
    expect(BroadcastCampaign::count())->toBe(1);

    $c = BroadcastCampaign::first();
    expect($c->has_audio)->toBeTrue();
    expect($c->audio_fingerprint)->toHaveLength(64); // sha256
    expect($c->audio_mime)->toContain('audio');
    expect($c->cadence)->toBe(20);
});
