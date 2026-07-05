<?php

use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;

/**
 * Cobre Fase 2 do Anti-Ban 2026 — score de diversidade de destinatários.
 *
 * "Novo" = chat sem last_inbound_at. A regra: se >70% da audiência é nova,
 * o job entra em modo conservador (delay dobrado). Feature test porque
 * getNewRecipientRatio consulta whatsapp_chats no banco.
 */

function abFakeInstance(int $tenantId): WhatsappInstance
{
    $instance = new WhatsappInstance();
    $instance->setRawAttributes([
        'id'            => 999,
        'instance_name' => "test_instance_{$tenantId}",
        'tenant_id'     => $tenantId,
        'daily_limit'   => 500,
    ]);
    $instance->settings = [];
    return $instance;
}

function abManager(): AntiBanManager
{
    $api = Mockery::mock(EvolutionApiService::class);
    return new AntiBanManager($api);
}

afterEach(function () {
    Mockery::close();
});

it('retorna 0.0 quando a lista de wa_ids é vazia', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    expect(abManager()->getNewRecipientRatio($instance, []))->toBe(0.0);
});

it('retorna 1.0 quando nenhum destinatário respondeu antes', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    // Chats existem mas SEM last_inbound_at — todos "novos"
    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000001', 'last_inbound_at' => null]);
    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000002', 'last_inbound_at' => null]);

    $ratio = abManager()->getNewRecipientRatio($instance, ['5511900000001', '5511900000002']);

    expect($ratio)->toBe(1.0);
});

it('retorna 0.0 quando todos os destinatários já responderam antes', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000001', 'last_inbound_at' => now()->subDay()]);
    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000002', 'last_inbound_at' => now()->subHour()]);

    $ratio = abManager()->getNewRecipientRatio($instance, ['5511900000001', '5511900000002']);

    expect($ratio)->toBe(0.0);
});

it('calcula proporção corretamente em audiência mista', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    // 3 conhecidos + 7 novos = 70% novos (limite)
    for ($i = 1; $i <= 3; $i++) {
        WhatsappChat::factory()->create([
            'tenant_id' => $tenant->id,
            'wa_id' => "551190000000{$i}",
            'last_inbound_at' => now()->subDay(),
        ]);
    }
    // Os "novos" nem precisam existir na tabela — ausência = novo
    $waIds = ['5511900000001', '5511900000002', '5511900000003',
              '5511900000010', '5511900000011', '5511900000012', '5511900000013',
              '5511900000014', '5511900000015', '5511900000016'];

    $ratio = abManager()->getNewRecipientRatio($instance, $waIds);

    expect($ratio)->toBe(0.7);
});

it('trata wa_ids duplicados sem inflar denominador', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000001', 'last_inbound_at' => now()->subDay()]);

    // Duplicados no input não podem contar duas vezes
    $ratio = abManager()->getNewRecipientRatio($instance, [
        '5511900000001', '5511900000001',
        '5511900000002', '5511900000002',
    ]);

    // 2 únicos, 1 conhecido → 0.5
    expect($ratio)->toBe(0.5);
});

it('ignora strings vazias e nulls no array de wa_ids', function () {
    $tenant   = Tenant::factory()->create();
    $instance = abFakeInstance($tenant->id);

    WhatsappChat::factory()->create(['tenant_id' => $tenant->id, 'wa_id' => '5511900000001', 'last_inbound_at' => now()->subDay()]);

    $ratio = abManager()->getNewRecipientRatio($instance, [
        '5511900000001',
        '',
        null,
        '5511900000002',
    ]);

    // Após filtrar: 2 wa_ids, 1 conhecido → 0.5
    expect($ratio)->toBe(0.5);
});

it('escopo é por tenant_id — chats de outro tenant não contam como conhecidos', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $instance = abFakeInstance($tenantA->id);

    // Chat com last_inbound_at existe, MAS no tenant B
    WhatsappChat::factory()->create([
        'tenant_id' => $tenantB->id,
        'wa_id' => '5511900000001',
        'last_inbound_at' => now()->subDay(),
    ]);

    // Do ponto de vista do tenant A, esse wa_id é "novo"
    $ratio = abManager()->getNewRecipientRatio($instance, ['5511900000001']);

    expect($ratio)->toBe(1.0);
});

it('limiar de risco é 0.70 (constante pública)', function () {
    expect(AntiBanManager::NEW_RECIPIENT_RISK_THRESHOLD)->toBe(0.70);
});
