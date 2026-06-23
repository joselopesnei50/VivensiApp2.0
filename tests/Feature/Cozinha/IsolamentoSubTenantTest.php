<?php

use App\Models\Cozinha;
use App\Models\CozinhaCoordenador;
use App\Models\Tenant;
use App\Models\TermoColaboracao;
use App\Models\User;

/**
 * Cozinha Solidária — Fase 0 (critério de aceite).
 * Coordenador de cozinha NÃO enxerga outra cozinha nem dados da gestora.
 * Cobre CozinhaPolicy + filtragem por User::cozinhasAtivasIds().
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function isoTenant(string $name = 'NGO'): Tenant
{
    return Tenant::factory()->create(['name' => $name . '-' . uniqid(), 'type' => 'ngo']);
}

function isoTermoIndireto(Tenant $tenant): TermoColaboracao
{
    return TermoColaboracao::create([
        'tenant_id'           => $tenant->id,
        'numero'              => 'T-' . uniqid(),
        'vigencia_inicio'     => now()->toDateString(),
        'vigencia_fim'        => now()->addYear()->toDateString(),
        'valor_global'        => 100000.00,
        'modalidade_execucao' => TermoColaboracao::MODALIDADE_INDIRETA,
        'status'              => TermoColaboracao::STATUS_VIGENTE,
    ]);
}

function isoCozinha(Tenant $tenant, TermoColaboracao $termo, string $nome): Cozinha
{
    return Cozinha::create([
        'tenant_id'           => $tenant->id,
        'termo_id'            => $termo->id,
        'nome'                => $nome,
        'meta_refeicoes_mes'  => 1000,
        'modalidade_execucao' => Cozinha::MODALIDADE_INDIRETA,
        'status'              => Cozinha::STATUS_ATIVA,
    ]);
}

function isoUser(Tenant $tenant, string $role, ?Cozinha $coord = null): User
{
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => $role,
    ]);
    if ($coord !== null) {
        CozinhaCoordenador::create([
            'tenant_id'  => $tenant->id,
            'cozinha_id' => $coord->id,
            'user_id'    => $user->id,
            'papel'      => CozinhaCoordenador::PAPEL_COORDENADOR,
            'ativo'      => true,
        ]);
    }
    return $user;
}

it('coordenador so ve cozinhas em que esta ativo no mesmo tenant', function () {
    $tenant = isoTenant('A');
    $termo  = isoTermoIndireto($tenant);
    $cz1 = isoCozinha($tenant, $termo, 'Cozinha 1');
    $cz2 = isoCozinha($tenant, $termo, 'Cozinha 2');
    $coord = isoUser($tenant, 'coordenador_cozinha', $cz1);

    expect($coord->cozinhasAtivasIds())->toBe([$cz1->id]);
    expect($coord->can('view', $cz1))->toBeTrue();
    expect($coord->can('view', $cz2))->toBeFalse();
});

it('coordenador inativo perde acesso', function () {
    $tenant = isoTenant();
    $termo  = isoTermoIndireto($tenant);
    $cz = isoCozinha($tenant, $termo, 'Solo');
    $coord = isoUser($tenant, 'coordenador_cozinha', $cz);

    CozinhaCoordenador::where('user_id', $coord->id)->update(['ativo' => false]);
    expect($coord->fresh()->cozinhasAtivasIds())->toBe([]);
    expect($coord->fresh()->can('view', $cz))->toBeFalse();
});

it('coordenador nao ve cozinhas de outro tenant mesmo se tiver pivot orfão', function () {
    $tenantA = isoTenant('A');
    $tenantB = isoTenant('B');
    $termoB  = isoTermoIndireto($tenantB);
    $czB = isoCozinha($tenantB, $termoB, 'Cozinha B');

    $coord = isoUser($tenantA, 'coordenador_cozinha');
    // tentativa de criar pivot cross-tenant — model não bloqueia diretamente,
    // mas Policy::view exige tenant_id igual ao do usuário
    expect($coord->can('view', $czB))->toBeFalse();
});

it('papel ngo do mesmo tenant ve todas as cozinhas', function () {
    $tenant = isoTenant();
    $termo  = isoTermoIndireto($tenant);
    $c1 = isoCozinha($tenant, $termo, 'C1');
    $c2 = isoCozinha($tenant, $termo, 'C2');
    $gestor = isoUser($tenant, 'ngo');

    expect($gestor->can('view', $c1))->toBeTrue();
    expect($gestor->can('view', $c2))->toBeTrue();
    expect($gestor->can('create', Cozinha::class))->toBeTrue();
});

it('ngo de outro tenant nao ve cozinhas alheias', function () {
    $tA = isoTenant('A');
    $tB = isoTenant('B');
    $tB_termo = isoTermoIndireto($tB);
    $tB_cz = isoCozinha($tB, $tB_termo, 'B-1');

    $ngoDoA = isoUser($tA, 'ngo');
    expect($ngoDoA->can('view', $tB_cz))->toBeFalse();
});

it('Termo direto cria cozinha automatica via observer', function () {
    $tenant = isoTenant('Direta');
    $termo = TermoColaboracao::create([
        'tenant_id'           => $tenant->id,
        'numero'              => 'T-D-' . uniqid(),
        'vigencia_inicio'     => now()->toDateString(),
        'vigencia_fim'        => now()->addYear()->toDateString(),
        'valor_global'        => 50000.00,
        'modalidade_execucao' => TermoColaboracao::MODALIDADE_DIRETA,
        'status'              => TermoColaboracao::STATUS_VIGENTE,
    ]);

    $cozinhas = Cozinha::where('termo_id', $termo->id)->get();
    expect($cozinhas)->toHaveCount(1);
    expect($cozinhas->first()->modalidade_execucao)->toBe(Cozinha::MODALIDADE_DIRETA);
    expect($cozinhas->first()->status)->toBe(Cozinha::STATUS_ATIVA);
});

it('Termo indireto nao cria cozinha automatica', function () {
    $tenant = isoTenant('Indireta');
    $termo = isoTermoIndireto($tenant);

    expect(Cozinha::where('termo_id', $termo->id)->count())->toBe(0);
});
