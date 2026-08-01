<?php

use App\Jobs\ProcessBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Queue;

/**
 * Auditoria 2026-08-01 — achado 1 do Disparo em Massa:
 * `phones` não tinha regra de validação; audience=selected com phones vazio
 * caía no fallback do getRecipients que devolvia a base INTEIRA do tenant
 * sem exigir opt-in. Agora: required_if no controller + guard no job.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bagManager(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

it('sendBroadcast rejeita audience=selected sem phones', function () {
    $user = bagManager();

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'message'  => 'Olá {tudo bem|como vai}?',
        'audience' => 'selected',
    ]);

    $resp->assertSessionHasErrors('phones');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('sendBroadcast aceita audience=selected com phones', function () {
    Queue::fake();
    $user = bagManager();
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'message'  => 'Olá!',
        'audience' => 'selected',
        'phones'   => '5511999990001,5511999990002',
    ]);

    $resp->assertSessionHasNoErrors();

    $camp = BroadcastCampaign::where('tenant_id', $user->tenant_id)->first();
    expect($camp)->not->toBeNull();
    expect($camp->audience_type)->toBe('selected');
    expect($camp->phones)->toBe('5511999990001,5511999990002');
});

it('job com selected e phones vazio retorna zero destinatarios (nao cai na base inteira)', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    // Base do tenant que o fallback antigo devolveria
    WhatsappChat::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'audience_type' => 'selected',
        'phones'        => null,
        'message'       => 'Teste',
        'status'        => 'queued',
    ]);

    $job = new ProcessBroadcastCampaignJob($campaign->id, $tenant->id);
    $m   = new ReflectionMethod($job, 'getRecipients');
    $m->setAccessible(true);

    $recipients = $m->invoke($job, $campaign);

    expect($recipients)->toBeEmpty();
});

it('employee recebe 403 no label-count (gate access-whatsapp)', function () {
    $tenant   = Tenant::factory()->create(['subscription_status' => 'active']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    $this->actingAs($employee)
        ->getJson('/whatsapp/broadcast/label-count?ids[]=1')
        ->assertStatus(403);
});

it('fallback de audience desconhecida exige opt-in', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'opt_in_at' => now()->subDay()]);
    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'opt_in_at' => null]);

    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'audience_type' => 'legacy_desconhecido',
        'message'       => 'Teste',
        'status'        => 'queued',
    ]);

    $job = new ProcessBroadcastCampaignJob($campaign->id, $tenant->id);
    $m   = new ReflectionMethod($job, 'getRecipients');
    $m->setAccessible(true);

    $recipients = $m->invoke($job, $campaign);

    expect($recipients)->toHaveCount(1);
    expect($recipients->first()->opt_in_at)->not->toBeNull();
});

it('job com selected e phones preenchido retorna apenas os selecionados', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511999990001']);
    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511888880002']);

    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'audience_type' => 'selected',
        'phones'        => '5511999990001',
        'message'       => 'Teste',
        'status'        => 'queued',
    ]);

    $job = new ProcessBroadcastCampaignJob($campaign->id, $tenant->id);
    $m   = new ReflectionMethod($job, 'getRecipients');
    $m->setAccessible(true);

    $recipients = $m->invoke($job, $campaign);

    expect($recipients)->toHaveCount(1);
    expect($recipients->first()->wa_id)->toBe('5511999990001');
});
