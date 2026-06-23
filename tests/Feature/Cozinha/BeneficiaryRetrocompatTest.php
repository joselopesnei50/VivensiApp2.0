<?php

use App\Models\Beneficiary;
use App\Models\Cozinha;
use App\Models\Tenant;
use App\Models\TermoColaboracao;

/**
 * Cozinha Solidária — Fase 0 (regressão R2 do guardião).
 *
 * Beneficiary é compartilhado entre NGO comum e Cozinha. A nova coluna
 * cozinha_id é NULLABLE e o filtro pelo BeneficiaryController não foi alterado
 * na Fase 0 — então NGO comum continua funcionando sem regressão.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('Beneficiary sem cozinha_id continua funcionando (NGO comum)', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo']);

    $b = Beneficiary::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Sem Cozinha',
        'status'    => 'active',
    ]);

    expect($b->cozinha_id)->toBeNull();
    expect(Beneficiary::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('Beneficiary pode ser vinculado a cozinha quando o tenant ativa o modulo', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo']);
    $termo  = TermoColaboracao::create([
        'tenant_id'           => $tenant->id,
        'numero'              => 'TC-' . uniqid(),
        'vigencia_inicio'     => now()->toDateString(),
        'vigencia_fim'        => now()->addYear()->toDateString(),
        'valor_global'        => 10000,
        'modalidade_execucao' => TermoColaboracao::MODALIDADE_INDIRETA,
        'status'              => TermoColaboracao::STATUS_VIGENTE,
    ]);
    $cozinha = Cozinha::create([
        'tenant_id'           => $tenant->id,
        'termo_id'            => $termo->id,
        'nome'                => 'Cozinha Norte',
        'meta_refeicoes_mes'  => 500,
        'modalidade_execucao' => Cozinha::MODALIDADE_INDIRETA,
        'status'              => Cozinha::STATUS_ATIVA,
    ]);

    $b = Beneficiary::create([
        'tenant_id'  => $tenant->id,
        'cozinha_id' => $cozinha->id,
        'name'       => 'Da Cozinha',
        'status'     => 'active',
    ]);

    expect($b->cozinha_id)->toBe($cozinha->id);
    expect(Beneficiary::where('cozinha_id', $cozinha->id)->count())->toBe(1);
});

it('Remover cozinha nao apaga beneficiarios (nullOnDelete)', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo']);
    $termo  = TermoColaboracao::create([
        'tenant_id'           => $tenant->id,
        'numero'              => 'TC-' . uniqid(),
        'vigencia_inicio'     => now()->toDateString(),
        'vigencia_fim'        => now()->addYear()->toDateString(),
        'valor_global'        => 10000,
        'modalidade_execucao' => TermoColaboracao::MODALIDADE_INDIRETA,
        'status'              => TermoColaboracao::STATUS_VIGENTE,
    ]);
    $cozinha = Cozinha::create([
        'tenant_id'           => $tenant->id,
        'termo_id'            => $termo->id,
        'nome'                => 'Para Excluir',
        'meta_refeicoes_mes'  => 100,
        'modalidade_execucao' => Cozinha::MODALIDADE_INDIRETA,
        'status'              => Cozinha::STATUS_ATIVA,
    ]);
    $b = Beneficiary::create([
        'tenant_id'  => $tenant->id,
        'cozinha_id' => $cozinha->id,
        'name'       => 'Orfao',
        'status'     => 'active',
    ]);

    $cozinha->forceDelete();

    $b->refresh();
    expect($b->cozinha_id)->toBeNull();
    expect(Beneficiary::find($b->id))->not->toBeNull();
});
