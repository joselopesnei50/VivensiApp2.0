<?php

use App\Models\RegraCompliance;
use App\Models\User;
use App\Services\RegrasComplianceService;

/**
 * Cozinha Solidária — Fase 0 (critério de aceite).
 *
 * "Mudar a portaria vigente troca as regras aplicadas sem deploy" — cobre
 * resolução por data + recusa a aplicar regra não-curada (R1 do guardião).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function rcRegra(array $attrs): RegraCompliance
{
    return RegraCompliance::create(array_merge([
        'chave'                => 'taxa_administracao_teto',
        'parametros'           => ['percentual' => 0.15],
        'fonte_legal'          => 'Portaria MDS 1.000/2025',
        'vigencia_inicio'      => '2025-01-01',
        'vigencia_fim'         => null,
        'aplica_a_modalidade'  => RegraCompliance::APLICA_AMBAS,
        'curado_por_user_id'   => 1,
        'curado_em'            => now(),
    ], $attrs));
}

it('resolve a regra vigente para uma data', function () {
    rcRegra([
        'parametros'      => ['percentual' => 0.10],
        'vigencia_inicio' => '2024-01-01',
        'vigencia_fim'    => '2024-12-31',
        'fonte_legal'     => 'Portaria 900/2024',
    ]);
    rcRegra([
        'parametros'      => ['percentual' => 0.15],
        'vigencia_inicio' => '2025-01-01',
        'vigencia_fim'    => null,
        'fonte_legal'     => 'Portaria 1000/2025',
    ]);

    $svc = app(RegrasComplianceService::class);
    $em2024 = $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2024-06-15'));
    $em2025 = $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15'));

    expect($em2024['percentual'])->toEqual(0.10);
    expect($em2025['percentual'])->toEqual(0.15);
});

it('recusa regra sem curadoria juridica', function () {
    rcRegra([
        'parametros'         => null,
        'curado_por_user_id' => null,
        'curado_em'          => null,
        'fonte_legal'        => 'PENDENTE — aguardando curadoria',
    ]);

    $svc = app(RegrasComplianceService::class);
    expect(fn () => $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15')))
        ->toThrow(RuntimeException::class, 'não foi curada');
});

it('recusa regra inexistente para a data', function () {
    rcRegra([
        'vigencia_inicio' => '2030-01-01',
    ]);

    $svc = app(RegrasComplianceService::class);
    expect(fn () => $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15')))
        ->toThrow(RuntimeException::class, 'não encontrada');
});

it('respeita filtro por modalidade', function () {
    rcRegra([
        'parametros'         => ['percentual' => 0.20],
        'aplica_a_modalidade' => RegraCompliance::APLICA_DIRETA,
    ]);
    rcRegra([
        'parametros'         => ['percentual' => 0.10],
        'aplica_a_modalidade' => RegraCompliance::APLICA_AMBAS,
        'vigencia_inicio'    => '2025-01-02',
    ]);

    $svc = app(RegrasComplianceService::class);
    $direta = $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15'), RegraCompliance::APLICA_DIRETA);
    $indireta = $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15'), RegraCompliance::APLICA_INDIRETA);

    // Direta pode bater na específica ou na geral — a mais recente vence.
    expect($direta['percentual'])->toBeIn([0.20, 0.10]);
    // Indireta NÃO bate na regra direta — só na "ambas".
    expect($indireta['percentual'])->toEqual(0.10);
});

it('validar aponta violacao do teto de taxa de administracao', function () {
    rcRegra(['parametros' => ['percentual' => 0.15]]);
    $svc = app(RegrasComplianceService::class);

    $okBate = $svc->validar(
        'taxa_administracao_teto',
        new DateTimeImmutable('2025-06-15'),
        ['percentual_aplicado' => 0.18]
    );
    $okBom = $svc->validar(
        'taxa_administracao_teto',
        new DateTimeImmutable('2025-06-15'),
        ['percentual_aplicado' => 0.10]
    );

    expect($okBate['ok'])->toBeFalse();
    expect($okBate['violacao'])->toContain('Taxa de administração');
    expect($okBate['fonte_legal'])->toBe('Portaria MDS 1.000/2025');

    expect($okBom['ok'])->toBeTrue();
    expect($okBom['violacao'])->toBeNull();
});

it('registrarCuradoria habilita aplicacao da regra', function () {
    $regra = rcRegra([
        'parametros'         => null,
        'curado_por_user_id' => null,
        'curado_em'          => null,
    ]);
    $jurista = User::factory()->create(['role' => 'super_admin']);
    $svc = app(RegrasComplianceService::class);

    // Antes da curadoria: lança
    expect(fn () => $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15')))
        ->toThrow(RuntimeException::class);

    $svc->registrarCuradoria(
        $regra,
        $jurista,
        ['percentual' => 0.12],
        'Portaria MDS Nova/2026'
    );

    $parametros = $svc->resolverRegra('taxa_administracao_teto', new DateTimeImmutable('2025-06-15'));
    expect($parametros['percentual'])->toEqual(0.12);
});
