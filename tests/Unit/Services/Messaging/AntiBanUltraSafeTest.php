<?php

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Support\Facades\Log;

/**
 * Cobre Fase 4 do Anti-Ban 2026 — perfil ultra_safe (21 dias) e
 * warning defensivo no fim do warming quando daily_limit > 150.
 */

uses(Tests\TestCase::class);

function usInstance(array $settings = [], int $dailyLimit = 300): WhatsappInstance
{
    $instance = new WhatsappInstance();
    $instance->setRawAttributes([
        'id'            => 42,
        'instance_name' => 'test_us',
        'tenant_id'     => 1,
        'settings'      => $settings,
        'daily_limit'   => $dailyLimit,
    ]);
    $instance->settings = $settings;
    return $instance;
}

function usManager(): AntiBanManager
{
    return new AntiBanManager(Mockery::mock(EvolutionApiService::class));
}

afterEach(function () {
    Mockery::close();
});

test('perfil ultra_safe está registrado no config e tem 21 dias', function () {
    $profile = config('whatsapp.antiban.warming_profiles.ultra_safe');

    expect($profile)->toBeArray()
        ->and(count($profile))->toBe(21)
        ->and(max(array_keys($profile)))->toBe(21);
});

test('perfil ultra_safe começa em 10 e termina em 370', function () {
    $profile = config('whatsapp.antiban.warming_profiles.ultra_safe');

    expect($profile[1])->toBe(10)
        ->and($profile[21])->toBe(370);
});

test('getWarmingProfile retorna ultra_safe quando settings especifica', function () {
    $ab       = usManager();
    $instance = usInstance(['warming_profile' => 'ultra_safe']);

    $profile = $ab->getWarmingProfile($instance);

    expect($profile[1])->toBe(10)
        ->and(max(array_keys($profile)))->toBe(21);
});

test('warning é logado quando warming termina com daily_limit > 150', function () {
    $ab = usManager();

    // Simula fim de warming: warming_started há 22 dias, perfil ultra_safe (21d max)
    $instance = usInstance([
        'warming_mode'       => true,
        'warming_profile'    => 'ultra_safe',
        'warming_started_at' => now()->subDays(22)->toDateString(),
    ], dailyLimit: 300);

    // Sem update real no banco — evita side-effect no teste unit
    $instance->exists = false;

    Log::spy();

    $ab->getWarmingDailyLimit($instance);

    Log::shouldHaveReceived('info')
        ->withArgs(function ($msg) {
            return str_contains($msg, 'warming concluído com daily_limit=300');
        })
        ->atLeast()->once();
});

test('warning NÃO é logado quando daily_limit está dentro da recomendação', function () {
    $ab = usManager();

    $instance = usInstance([
        'warming_mode'       => true,
        'warming_profile'    => 'ultra_safe',
        'warming_started_at' => now()->subDays(22)->toDateString(),
    ], dailyLimit: 100);
    $instance->exists = false;

    Log::spy();

    $ab->getWarmingDailyLimit($instance);

    Log::shouldNotHaveReceived('info', function ($msg) {
        return is_string($msg) && str_contains($msg, 'recomendado para 2026');
    });
});

test('RECOMMENDED_POST_WARMING_LIMIT_2026 é 150', function () {
    expect(AntiBanManager::RECOMMENDED_POST_WARMING_LIMIT_2026)->toBe(150);
});
