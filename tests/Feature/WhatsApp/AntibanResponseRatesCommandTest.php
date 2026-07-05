<?php

use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Support\Str;

/**
 * Cobre Fase 3 do Anti-Ban 2026 — comando antiban:compute-response-rates.
 * Cria mensagens no banco cobrindo cenários de tenant sem outbound, tenant
 * com resposta alta e tenant com resposta abaixo do limiar de risco.
 */

function rrTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function rrInstance(Tenant $tenant, array $attrs = []): WhatsappInstance
{
    return WhatsappInstance::create(array_merge([
        'tenant_id'      => $tenant->id,
        'instance_name'  => 'test-instance-' . Str::random(6),
        'instance_token' => Str::random(32),
        'status'         => 'open',
    ], $attrs));
}

function rrChat(Tenant $tenant): WhatsappChat
{
    return WhatsappChat::factory()->create(['tenant_id' => $tenant->id]);
}

function rrMessage(Tenant $tenant, WhatsappChat $chat, string $direction, ?\Carbon\Carbon $when = null): WhatsappMessage
{
    $m = WhatsappMessage::create([
        'tenant_id' => $tenant->id,
        'chat_id'   => $chat->id,
        'message_id'=> 'MSG_' . Str::random(10),
        'content'   => "test {$direction}",
        'direction' => $direction,
        'type'      => 'text',
    ]);
    if ($when) {
        $m->created_at = $when;
        $m->save();
    }
    return $m;
}

it('não faz nada quando não há instâncias ativas', function () {
    $this->artisan('antiban:compute-response-rates')
        ->expectsOutput('Nenhuma instância ativa — nada a calcular.')
        ->assertSuccessful();
});

it('ignora instância sem outbound no período (não grava nada)', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instance->refresh();
    expect($instance->settings['response_rate_7d'] ?? null)->toBeNull();
});

it('calcula e grava taxa de resposta 7d correta', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    // 4 chats com outbound — 3 responderam, 1 não
    for ($i = 1; $i <= 4; $i++) {
        $chat = rrChat($tenant);
        rrMessage($tenant, $chat, 'outbound');
        if ($i <= 3) {
            rrMessage($tenant, $chat, 'inbound');
        }
    }

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instance->refresh();
    expect($instance->settings['response_rate_7d'])->toBe(0.75)
        ->and($instance->settings['response_rate_updated_at'] ?? null)->not->toBeNull();
});

it('marca risco quando taxa fica abaixo de 5%', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    // 25 chats outbound, só 1 respondeu → 4% (abaixo de 5%)
    for ($i = 1; $i <= 25; $i++) {
        $chat = rrChat($tenant);
        rrMessage($tenant, $chat, 'outbound');
        if ($i === 1) {
            rrMessage($tenant, $chat, 'inbound');
        }
    }

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instance->refresh();
    expect($instance->settings['response_rate_7d'])->toBe(0.04);

    // Confirma que o getInstanceStatus marca risco corretamente
    $ab = new AntiBanManager(Mockery::mock(\App\Services\EvolutionApiService::class));
    $status = $ab->getInstanceStatus($instance);
    expect($status['response_rate_risk'])->toBeTrue();
});

it('não marca risco quando taxa está acima do limiar saudável', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    // 10 outbound, 5 inbound → 50%
    for ($i = 1; $i <= 10; $i++) {
        $chat = rrChat($tenant);
        rrMessage($tenant, $chat, 'outbound');
        if ($i <= 5) {
            rrMessage($tenant, $chat, 'inbound');
        }
    }

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instance->refresh();
    $ab = new AntiBanManager(Mockery::mock(\App\Services\EvolutionApiService::class));
    $status = $ab->getInstanceStatus($instance);

    expect($status['response_rate_7d'])->toBe(0.5)
        ->and($status['response_rate_risk'])->toBeFalse();
});

it('ignora outbound fora da janela de 7 dias', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    // 1 outbound velho (fora do window) — deveria ser ignorado
    $oldChat = rrChat($tenant);
    rrMessage($tenant, $oldChat, 'outbound', now()->subDays(30));

    // 2 outbound recentes com 1 resposta → 50%
    $c1 = rrChat($tenant);
    $c2 = rrChat($tenant);
    rrMessage($tenant, $c1, 'outbound');
    rrMessage($tenant, $c2, 'outbound');
    rrMessage($tenant, $c1, 'inbound');

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instance->refresh();
    expect($instance->settings['response_rate_7d'])->toBe(0.5);
});

it('modo dry-run não grava em settings', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    $chat = rrChat($tenant);
    rrMessage($tenant, $chat, 'outbound');
    rrMessage($tenant, $chat, 'inbound');

    $this->artisan('antiban:compute-response-rates --dry-run')->assertSuccessful();

    $instance->refresh();
    expect($instance->settings['response_rate_7d'] ?? null)->toBeNull();
});

it('escopo por tenant — mensagens de outro tenant não contam', function () {
    $tenantA = rrTenant();
    $tenantB = rrTenant();
    $instanceA = rrInstance($tenantA);

    // Tenant A: 2 outbound, 0 inbound → 0%
    for ($i = 0; $i < 2; $i++) {
        rrMessage($tenantA, rrChat($tenantA), 'outbound');
    }

    // Tenant B: 1 outbound + 1 inbound (não deveria influenciar A)
    $chatB = rrChat($tenantB);
    rrMessage($tenantB, $chatB, 'outbound');
    rrMessage($tenantB, $chatB, 'inbound');

    $this->artisan('antiban:compute-response-rates')->assertSuccessful();

    $instanceA->refresh();
    // JSON round-trip transforma 0.0 em 0 — testamos numericamente
    expect((float) $instanceA->settings['response_rate_7d'])->toBe(0.0);
});

it('getInstanceStatus retorna null e sem risco quando taxa não foi calculada', function () {
    $tenant   = rrTenant();
    $instance = rrInstance($tenant);

    $ab = new AntiBanManager(Mockery::mock(\App\Services\EvolutionApiService::class));
    $status = $ab->getInstanceStatus($instance);

    expect($status['response_rate_7d'])->toBeNull()
        ->and($status['response_rate_risk'])->toBeFalse()
        ->and($status['response_rate_updated_at'])->toBeNull();
});
