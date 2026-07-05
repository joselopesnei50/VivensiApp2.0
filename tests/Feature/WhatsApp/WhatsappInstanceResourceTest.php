<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Support\Str;

/**
 * Fix 2 do relatório de segurança (2026-07-05): WhatsappInstanceResource
 * garante que campos sensíveis do settings JSON (proxy_url, restricted_reason,
 * instance_data raw) NÃO vazam nas respostas de /whatsapp/instances.
 */

function wirEnv(array $settings = []): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $instance = WhatsappInstance::create([
        'tenant_id'      => $tenant->id,
        'instance_name'  => 'test-' . Str::random(6),
        'instance_token' => Str::random(32),
        'status'         => 'open',
        'settings'       => $settings,
    ]);

    return [$user, $tenant, $instance];
}

it('index não expõe proxy_url do settings', function () {
    [$user] = wirEnv([
        'proxy_url' => 'socks5://user:senhaSecreta@proxy.example.com:1080',
    ]);

    $r = $this->actingAs($user)->getJson('/api/whatsapp/instances');

    $r->assertStatus(200);

    $body = json_encode($r->json());
    expect($body)->not->toContain('senhaSecreta')
        ->and($body)->not->toContain('proxy.example.com')
        ->and($body)->not->toContain('proxy_url');

    // Mas expõe o sinal booleano seguro
    $r->assertJsonPath('data.0.has_proxy', true);
});

it('index não expõe restricted_reason (sinaliza método de detecção)', function () {
    [$user] = wirEnv([
        'restricted_until'  => now()->addHours(12)->toIso8601String(),
        'restricted_reason' => 'ban_signal_detected',
    ]);

    $r = $this->actingAs($user)->getJson('/api/whatsapp/instances');

    $r->assertStatus(200)
      ->assertJsonMissing(['restricted_reason' => 'ban_signal_detected']);

    // Mas expõe restrição via bool + until
    $r->assertJsonPath('data.0.restriction.active', true);
});

it('index não expõe instance_token nem instance_token_bidx', function () {
    [$user, , $instance] = wirEnv();

    $r = $this->actingAs($user)->getJson('/api/whatsapp/instances');

    $body = json_encode($r->json());
    expect($body)->not->toContain($instance->instance_token)
        ->and($body)->not->toContain('instance_token')
        ->and($body)->not->toContain('instance_token_bidx');
});

it('index expõe campos operacionais esperados', function () {
    [$user, , $instance] = wirEnv([
        'warming_mode'       => true,
        'warming_profile'    => 'ultra_safe',
        'warming_started_at' => '2026-06-15',
        'response_rate_7d'   => 0.42,
    ]);

    $r = $this->actingAs($user)->getJson('/api/whatsapp/instances');

    $r->assertStatus(200)
      ->assertJsonStructure([
          'data' => [
              '*' => [
                  'id', 'instance_name', 'phone_number', 'status',
                  'daily_limit', 'messages_sent_today',
                  'warming' => ['active', 'profile', 'started_at'],
                  'response_rate' => ['rate_7d', 'updated_at'],
                  'restriction' => ['active', 'until'],
                  'has_proxy',
              ],
          ],
      ])
      ->assertJsonPath('data.0.warming.profile', 'ultra_safe')
      ->assertJsonPath('data.0.warming.active', true)
      ->assertJsonPath('data.0.response_rate.rate_7d', 0.42);
});

it('restriction.active vira false quando restricted_until expirou', function () {
    [$user] = wirEnv([
        'restricted_until'  => now()->subDay()->toIso8601String(), // passado
        'restricted_reason' => 'ban_signal_detected',
    ]);

    $r = $this->actingAs($user)->getJson('/api/whatsapp/instances');

    $r->assertStatus(200)
      ->assertJsonPath('data.0.restriction.active', false);
});
