<?php

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Support\Facades\Cache;

/**
 * Cobre Fase 1 do Anti-Ban 2026 — fingerprint de conteúdo.
 * A regra: até MAX_SAME_CONTENT_PER_DAY envios do mesmo conteúdo por
 * instância por dia. Conteúdo diferente ou instância diferente não
 * consomem a mesma cota.
 */

uses(Tests\TestCase::class);

function makeInstance(int $id = 999): WhatsappInstance
{
    $instance = new WhatsappInstance();
    $instance->setRawAttributes([
        'id'            => $id,
        'instance_name' => "test_instance_{$id}",
        'tenant_id'     => 1,
        'daily_limit'   => 500,
    ]);
    $instance->settings = [];
    return $instance;
}

function makeAntiBanFingerprint(): AntiBanManager
{
    $apiMock = Mockery::mock(EvolutionApiService::class);
    return new AntiBanManager($apiMock);
}

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Mockery::close();
});

test('mesmo conteúdo é permitido até o limite e bloqueado depois', function () {
    $ab       = makeAntiBanFingerprint();
    $instance = makeInstance();
    $msg      = 'Olá cliente, temos uma novidade pra você!';

    for ($i = 0; $i < AntiBanManager::MAX_SAME_CONTENT_PER_DAY; $i++) {
        expect($ab->contentFingerprintAllowed($instance, $msg))->toBeTrue("Envio #{$i} deveria ser permitido");
        $ab->recordContentSent($instance, $msg);
    }

    expect($ab->contentFingerprintAllowed($instance, $msg))->toBeFalse('Envio pós-limite deveria ser bloqueado');
});

test('conteúdo diferente não é afetado pela cota de outro fingerprint', function () {
    $ab       = makeAntiBanFingerprint();
    $instance = makeInstance();

    for ($i = 0; $i < AntiBanManager::MAX_SAME_CONTENT_PER_DAY; $i++) {
        $ab->recordContentSent($instance, 'Olá cliente');
    }

    expect($ab->contentFingerprintAllowed($instance, 'Olá cliente'))->toBeFalse();
    expect($ab->contentFingerprintAllowed($instance, 'Olá, tudo bem?'))->toBeTrue();
});

test('normalização de whitespace produz o mesmo fingerprint', function () {
    $ab       = makeAntiBanFingerprint();
    $instance = makeInstance();

    // Estourar cota com uma variação
    for ($i = 0; $i < AntiBanManager::MAX_SAME_CONTENT_PER_DAY; $i++) {
        $ab->recordContentSent($instance, 'Olá cliente!');
    }

    // Mesma mensagem com espaços/quebras diferentes deve colidir no mesmo hash
    expect($ab->contentFingerprintAllowed($instance, '  Olá   cliente!  '))->toBeFalse();
    expect($ab->contentFingerprintAllowed($instance, "Olá\ncliente!"))->toBeFalse();
});

test('normalização é case-insensitive', function () {
    $ab       = makeAntiBanFingerprint();
    $instance = makeInstance();

    for ($i = 0; $i < AntiBanManager::MAX_SAME_CONTENT_PER_DAY; $i++) {
        $ab->recordContentSent($instance, 'olá cliente');
    }

    expect($ab->contentFingerprintAllowed($instance, 'OLÁ CLIENTE'))->toBeFalse();
    expect($ab->contentFingerprintAllowed($instance, 'Olá Cliente'))->toBeFalse();
});

test('cota de fingerprint é isolada por instância', function () {
    $ab        = makeAntiBanFingerprint();
    $instanceA = makeInstance(1);
    $instanceB = makeInstance(2);

    for ($i = 0; $i < AntiBanManager::MAX_SAME_CONTENT_PER_DAY; $i++) {
        $ab->recordContentSent($instanceA, 'Mesmo texto');
    }

    expect($ab->contentFingerprintAllowed($instanceA, 'Mesmo texto'))->toBeFalse();
    expect($ab->contentFingerprintAllowed($instanceB, 'Mesmo texto'))->toBeTrue();
});

test('hasSpintax detecta variação {a|b} corretamente', function () {
    expect(AntiBanManager::hasSpintax('Olá cliente'))->toBeFalse();
    expect(AntiBanManager::hasSpintax('Olá {cliente|amigo}!'))->toBeTrue();
    expect(AntiBanManager::hasSpintax('{Oi|Olá|E aí} — {tudo bem|beleza}?'))->toBeTrue();

    // Casos negativos que já continham chaves mas não spintax
    expect(AntiBanManager::hasSpintax('Preço: R$ 100 { promoção }'))->toBeFalse();
    expect(AntiBanManager::hasSpintax('{sem_pipe_dentro}'))->toBeFalse();
});
