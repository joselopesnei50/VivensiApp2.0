<?php

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;

/**
 * Cobre Tarefa 3.2 da auditoria — tuning configurável do AntiBanManager.
 * Testa que perfis vêm de config, suportam override por instância, e mantêm
 * comportamento default idêntico ao anterior (constants deprecated).
 */

/**
 * Helper local: cria instância "leve" sem persistir no banco — getters do
 * AntiBanManager só leem $instance->settings, não tocam DB.
 */
function fakeInstance(array $settings = []): WhatsappInstance
{
    $instance = new WhatsappInstance();
    $instance->setRawAttributes([
        'id'            => 999,
        'instance_name' => 'test_instance',
        'tenant_id'     => 1,
        'settings'      => $settings,
        'daily_limit'   => 500,
    ]);
    // Cast manual de settings (JSON column normalmente é castada via $casts)
    $instance->settings = $settings;
    return $instance;
}

function makeAntiBan(): AntiBanManager
{
    // EvolutionApiService só é usado em métodos que mandam presence/HTTP
    // — os getters de tuning não tocam $this->api. Mock simples basta.
    $apiMock = Mockery::mock(EvolutionApiService::class);
    return new AntiBanManager($apiMock);
}

afterEach(function () {
    Mockery::close();
});

// ── max_per_hour ───────────────────────────────────────────────────────────

test('getMaxPerHour cai no config quando instância não tem override', function () {
    config(['whatsapp.antiban.max_per_hour' => 55]);

    $instance = fakeInstance();
    $ab       = makeAntiBan();

    expect($ab->getMaxPerHour($instance))->toBe(55);
});

test('getMaxPerHour respeita override da instância sobre o config', function () {
    config(['whatsapp.antiban.max_per_hour' => 55]);

    $instance = fakeInstance(['max_per_hour' => 25]);
    $ab       = makeAntiBan();

    expect($ab->getMaxPerHour($instance))->toBe(25);
});

test('getMaxPerHour ignora override inválido (não-int ou <= 0)', function () {
    config(['whatsapp.antiban.max_per_hour' => 55]);

    $ab = makeAntiBan();

    expect($ab->getMaxPerHour(fakeInstance(['max_per_hour' => 0])))->toBe(55);
    expect($ab->getMaxPerHour(fakeInstance(['max_per_hour' => -5])))->toBe(55);
    expect($ab->getMaxPerHour(fakeInstance(['max_per_hour' => 'string'])))->toBe(55);
});

// ── ban_restriction_hours ──────────────────────────────────────────────────

test('getBanRestrictionHours lê do config', function () {
    config(['whatsapp.antiban.default_ban_restriction_hours' => 48]);

    $ab = makeAntiBan();

    expect($ab->getBanRestrictionHours())->toBe(48);
});

// ── warming_profile ────────────────────────────────────────────────────────

test('getWarmingProfile retorna default quando settings não especifica', function () {
    config(['whatsapp.antiban.warming_profiles' => [
        'default'      => [1 => 20, 14 => 370],
        'conservative' => [1 => 15, 14 => 150],
    ]]);

    $instance = fakeInstance();
    $ab       = makeAntiBan();

    expect($ab->getWarmingProfile($instance))->toBe([1 => 20, 14 => 370]);
});

test('getWarmingProfile retorna o perfil escolhido em settings', function () {
    config(['whatsapp.antiban.warming_profiles' => [
        'default'      => [1 => 20, 14 => 370],
        'conservative' => [1 => 15, 14 => 150],
    ]]);

    $instance = fakeInstance(['warming_profile' => 'conservative']);
    $ab       = makeAntiBan();

    expect($ab->getWarmingProfile($instance))->toBe([1 => 15, 14 => 150]);
});

test('getWarmingProfile cai em default se perfil escolhido não existe', function () {
    config(['whatsapp.antiban.warming_profiles' => [
        'default'      => [1 => 20, 14 => 370],
        'conservative' => [1 => 15, 14 => 150],
    ]]);

    $instance = fakeInstance(['warming_profile' => 'profile_que_nao_existe']);
    $ab       = makeAntiBan();

    expect($ab->getWarmingProfile($instance))->toBe([1 => 20, 14 => 370]);
});

test('getWarmingProfile cai no constant deprecated se config corrompido', function () {
    config(['whatsapp.antiban.warming_profiles' => null]);

    $instance = fakeInstance();
    $ab       = makeAntiBan();

    // Constant WARMING_PROFILE tem 14 chaves
    expect($ab->getWarmingProfile($instance))->toHaveCount(14);
    expect($ab->getWarmingProfile($instance)[1])->toBe(20);
});

// ── compat: defaults não mudaram ───────────────────────────────────────────

test('com config zerado, comportamento eh identico ao pré-3.2', function () {
    // Simula deploy sem alterações de env/config — só os fallbacks aos constants
    config(['whatsapp.antiban' => null]);

    $instance = fakeInstance();
    $ab       = makeAntiBan();

    expect($ab->getMaxPerHour($instance))->toBe(AntiBanManager::MAX_PER_HOUR);
    expect($ab->getBanRestrictionHours())->toBe(AntiBanManager::DEFAULT_BAN_RESTRICTION_HOURS);
    expect($ab->getWarmingProfile($instance))->toBe(AntiBanManager::WARMING_PROFILE);
});
