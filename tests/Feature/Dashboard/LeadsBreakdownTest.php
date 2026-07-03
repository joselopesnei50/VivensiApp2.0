<?php

use App\Http\Controllers\DashboardController;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantOperationalProfile;
use App\Services\PerfilOperacionalService;

/**
 * P1.6 — Base de cadastros (cidade + tags) no dashboard do gestor.
 * Cobre o método privado resolveLeadsBreakdown via Reflection: filtra por
 * status ativo, agrega por cidade/tags com top 10 + "Outras", e devolve
 * null pra perfis que não vendem mobilização.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function lbTenantWithProfile(string $categoria): Tenant
{
    $tenant = Tenant::factory()->create();
    TenantOperationalProfile::create([
        'tenant_id' => $tenant->id,
        'categoria' => $categoria,
    ]);
    return $tenant;
}

function lbLead(Tenant $tenant, string $status, ?string $city = null, array $tags = []): Lead
{
    return Lead::create([
        'tenant_id'        => $tenant->id,
        'name'             => 'L' . uniqid(),
        'phone'            => '+55 11 9' . random_int(10000000, 99999999),
        'phone_normalized' => '5511' . random_int(900000000, 999999999),
        'status'           => $status,
        'city'             => $city,
        'tags'             => $tags,
    ]);
}

function lbInvoke(Tenant $tenant): ?array
{
    $controller = new DashboardController();
    $method = new \ReflectionMethod($controller, 'resolveLeadsBreakdown');
    $method->setAccessible(true);
    return $method->invoke($controller, $tenant, app(PerfilOperacionalService::class));
}

it('retorna null pra perfil que nao vende mobilizacao', function () {
    $tenant = lbTenantWithProfile(TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL);
    lbLead($tenant, Lead::STATUS_CONFIRMED, 'São Paulo', ['vip']);

    expect(lbInvoke($tenant))->toBeNull();
});

it('retorna estrutura vazia com placeholders quando perfil ok mas zero leads', function () {
    $tenant = lbTenantWithProfile(TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL);

    $r = lbInvoke($tenant);

    expect($r)->not->toBeNull();
    expect($r['total'])->toBe(0);
    expect($r['cities']['labels'])->toBe([]);
    expect($r['tags']['labels'])->toBe([]);
});

it('agrega cidade e tags so com status pending+confirmed', function () {
    $tenant = lbTenantWithProfile(TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL);

    lbLead($tenant, Lead::STATUS_PENDING,      'Ribeirão Preto', ['jovem', 'voluntario']);
    lbLead($tenant, Lead::STATUS_CONFIRMED,    'Ribeirão Preto', ['voluntario']);
    lbLead($tenant, Lead::STATUS_CONFIRMED,    'Sertãozinho',    ['jovem']);
    lbLead($tenant, Lead::STATUS_UNSUBSCRIBED, 'Ribeirão Preto', ['jovem']);   // deve sair
    lbLead($tenant, Lead::STATUS_BLOCKED,      'Sertãozinho',    ['voluntario']); // deve sair

    $r = lbInvoke($tenant);

    expect($r['total'])->toBe(3);
    expect($r['cities']['labels'])->toBe(['Ribeirão Preto', 'Sertãozinho']);
    expect($r['cities']['values'])->toBe([2, 1]);
    // jovem e voluntario empatam em 2 — ordem do desempate depende da ordem
    // que o DB devolve as rows (sqlite != mysql), entao compara sem ordem.
    $tags = array_combine($r['tags']['labels'], $r['tags']['values']);
    ksort($tags);
    expect($tags)->toBe([
        'jovem'      => 2,
        'voluntario' => 2,
    ]);
});

it('mantem top 10 e empilha resto como Outras', function () {
    $tenant = lbTenantWithProfile(TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL);

    // 11 cidades distintas: Capital com 12 leads + 10 cidades com 1 lead.
    // Top 10 = Capital + 9 cidades; a 11a vira "Outras" com 1.
    for ($i = 0; $i < 12; $i++) {
        lbLead($tenant, Lead::STATUS_CONFIRMED, 'Capital', []);
    }
    for ($i = 1; $i <= 10; $i++) {
        lbLead($tenant, Lead::STATUS_CONFIRMED, "Cidade {$i}", []);
    }

    $r = lbInvoke($tenant);

    expect(count($r['cities']['labels']))->toBe(11); // 10 + "Outras"
    expect($r['cities']['labels'][0])->toBe('Capital');
    expect($r['cities']['labels'][10])->toBe('Outras');
    expect(end($r['cities']['values']))->toBe(1);    // só 1 sobrou pra "Outras"
});

it('ignora cidade nula e tags vazias', function () {
    $tenant = lbTenantWithProfile(TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL);
    lbLead($tenant, Lead::STATUS_CONFIRMED, null, []);
    lbLead($tenant, Lead::STATUS_CONFIRMED, '   ', ['  ']);
    lbLead($tenant, Lead::STATUS_CONFIRMED, 'Belo Horizonte', ['ativo']);

    $r = lbInvoke($tenant);

    expect($r['total'])->toBe(3);
    expect($r['cities']['labels'])->toBe(['Belo Horizonte']);
    expect($r['tags']['labels'])->toBe(['ativo']);
});
