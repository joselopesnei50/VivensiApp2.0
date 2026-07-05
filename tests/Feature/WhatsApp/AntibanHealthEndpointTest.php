<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Support\Str;

/**
 * Cobre Fase 5 do Anti-Ban 2026 — endpoint /whatsapp/instances/{id}/health
 * usado pelo dashboard de saúde da instância. Testa o shape do JSON e o
 * cálculo do traffic_light nos cenários green/yellow/red.
 */

function hEnv(array $instanceAttrs = [], array $settings = []): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $instance = WhatsappInstance::create(array_merge([
        'tenant_id'           => $tenant->id,
        'instance_name'       => 'test-' . Str::random(6),
        'instance_token'      => Str::random(32),
        'status'              => 'open',
        'safe_window_start'   => '00:00', // dentro da janela sempre
        'safe_window_end'     => '23:59',
        'daily_limit'         => 100,
        'messages_sent_today' => 0,
        'daily_reset_at'      => now(),
        'settings'            => $settings,
    ], $instanceAttrs));

    return [$user, $tenant, $instance];
}

it('endpoint exige autenticação', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $instance = WhatsappInstance::create([
        'tenant_id'      => $tenant->id,
        'instance_name'  => 'x',
        'instance_token' => Str::random(32),
        'status'         => 'open',
    ]);

    $this->getJson("/whatsapp/instances/{$instance->id}/health")
        ->assertStatus(401);
});

it('retorna 200 e a estrutura esperada', function () {
    [$user, , $instance] = hEnv();

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    $r->assertStatus(200)
      ->assertJsonStructure([
          'instance_id',
          'instance_name',
          'status' => [
              'can_send', 'is_restricted', 'is_warming',
              'sent_today', 'daily_limit', 'within_window',
              'response_rate_7d', 'response_rate_risk',
          ],
          'traffic_light',
      ]);
});

it('traffic_light = green para instância saudável', function () {
    [$user, , $instance] = hEnv();

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    expect($r->json('traffic_light'))->toBe('green');
});

it('traffic_light = yellow quando response_rate_risk está ativo', function () {
    [$user, , $instance] = hEnv([], [
        'response_rate_7d' => 0.02, // abaixo do limiar saudável (5%)
        'response_rate_updated_at' => now()->toIso8601String(),
    ]);

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    expect($r->json('traffic_light'))->toBe('yellow')
        ->and($r->json('status.response_rate_risk'))->toBeTrue();
});

it('traffic_light = yellow quando enviados >= 80% do limite', function () {
    [$user, , $instance] = hEnv(['messages_sent_today' => 80, 'daily_limit' => 100]);

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    expect($r->json('traffic_light'))->toBe('yellow');
});

it('traffic_light = red quando instância está restrita', function () {
    [$user, , $instance] = hEnv([], [
        'restricted_until' => now()->addHours(12)->toIso8601String(),
        'restricted_reason'=> 'ban_signal_detected',
        'restricted_at'    => now()->toIso8601String(),
    ]);

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    expect($r->json('traffic_light'))->toBe('red')
        ->and($r->json('status.is_restricted'))->toBeTrue();
});

it('traffic_light = red quando fora da janela horária', function () {
    // Janela horária impossível de conter o "agora" (min = max = agora + 1min é frágil).
    // Aqui usamos 00:00-00:01 — nunca conterá horário de teste real.
    [$user, , $instance] = hEnv([
        'safe_window_start' => '00:00',
        'safe_window_end'   => '00:01',
    ]);

    $r = $this->actingAs($user)->getJson("/whatsapp/instances/{$instance->id}/health");

    // Se o teste rodar exatamente 00:00-00:01 UTC-3 vai falhar; aceitável.
    $now = now()->timezone('America/Sao_Paulo');
    if ($now->format('H:i') === '00:00' || $now->format('H:i') === '00:01') {
        $this->markTestSkipped('Teste dependente de horário — pulando entre 00:00 e 00:01 São Paulo');
    }

    expect($r->json('traffic_light'))->toBe('red')
        ->and($r->json('status.within_window'))->toBeFalse();
});

it('não permite acessar instância de outro tenant', function () {
    // Setup: instância no tenant A
    [, , $instanceA] = hEnv();

    // User no tenant B
    $tenantB = Tenant::factory()->create(['subscription_status' => 'active']);
    $userB   = User::factory()->create(['tenant_id' => $tenantB->id, 'role' => 'manager']);

    $this->actingAs($userB)
        ->getJson("/whatsapp/instances/{$instanceA->id}/health")
        ->assertStatus(404);
});
