<?php

use App\Models\Contract;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;

/**
 * Roteamento do contrato de adesao por segmento (2026-08-28).
 *
 *  - tenant->type='ngo' -> template contract-content-ngo com MROSC + LGPD reforcada
 *  - demais tenants -> template default generico
 *  - CEBAS/CMAS/CNAS aparecem no template NGO quando preenchidos
 */

function ngoPaidPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'                  => 'Plano NGO',
        'target_audience'       => 'ngo',
        'price'                 => 149.90,
        'is_active'             => true,
        'is_courtesy'           => false,
        'abacatepay_product_id' => 'prod_test_ngo',
    ]);
}

function ngoEntityPayload(): array
{
    return [
        'razao_social'     => 'Associacao Beneficente NGO',
        'document'         => '12.345.678/0001-90',
        'endereco'         => 'Rua das Flores',
        'numero_endereco'  => '100',
        'complemento'      => null,
        'bairro'           => 'Centro',
        'cidade'           => 'Sao Paulo',
        'estado'           => 'SP',
        'cep'              => '01000-000',
        'signer_name'      => 'Presidente NGO',
        'signer_email'     => 'presidente@ngo.org',
        'signer_cpf'       => '123.456.789-00',
    ];
}

test('tenant tipo ngo gera contrato com clausulas MROSC e LGPD reforcada', function () {
    $plan   = ngoPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id'             => $plan->id,
        'subscription_status' => 'awaiting_contract',
        'type'                => 'ngo',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), ngoEntityPayload());

    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->firstOrFail();

    expect($contract->content)->toContain('MROSC');
    expect($contract->content)->toContain('Lei 13.019');
    expect($contract->content)->toContain('Sociedade Civil');
    expect($contract->content)->toContain('LGPD — TRATAMENTO DE DADOS PESSOAIS (REFORCADA)');
    expect($contract->content)->toContain('CONTROLADORA');
    expect($contract->content)->toContain('OPERADORA');
    expect($contract->content)->toContain('DADOS SENSIVEIS DE BENEFICIARIOS VULNERAVEIS');
    expect($contract->content)->toContain('72 (setenta e duas) horas');
    expect($contract->content)->toContain('Consentimento parental');
    expect($contract->content)->toContain('utilizara o nome, logo, marca');
    expect($contract->content)->toContain('USO DE MARCA E CASES');
});

test('tenant NAO ngo gera contrato default sem clausulas MROSC', function () {
    $plan   = ngoPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id'             => $plan->id,
        'subscription_status' => 'awaiting_contract',
        'type'                => 'common',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'common']);

    $this->actingAs($user)->post(route('adesao.store-data'), ngoEntityPayload());

    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->firstOrFail();

    expect($contract->content)->not->toContain('MROSC');
    expect($contract->content)->not->toContain('Lei 13.019');
    expect($contract->content)->not->toContain('DADOS SENSIVEIS DE BENEFICIARIOS VULNERAVEIS');
    expect($contract->content)->toContain('CONTRATO DE ADESAO — VIVENSI');
});

test('contrato NGO lista CEBAS CMAS CNAS quando tenant tem dados de qualificacao', function () {
    $plan   = ngoPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id'             => $plan->id,
        'subscription_status' => 'awaiting_contract',
        'type'                => 'ngo',
        'area_atuacao_cebas'  => 'Assistencia Social',
        'cmas_numero'         => 'CMAS-SP-12345',
        'cnas_numero'         => 'CNAS-98765',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), ngoEntityPayload());

    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->firstOrFail();

    expect($contract->content)->toContain('QUALIFICACOES DA OSC');
    expect($contract->content)->toContain('Assistencia Social');
    expect($contract->content)->toContain('CMAS-SP-12345');
    expect($contract->content)->toContain('CNAS-98765');
});

test('contrato NGO omite bloco de qualificacoes quando tenant nao tem CEBAS/CMAS/CNAS', function () {
    $plan   = ngoPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id'             => $plan->id,
        'subscription_status' => 'awaiting_contract',
        'type'                => 'ngo',
        'area_atuacao_cebas'  => null,
        'cmas_numero'         => null,
        'cnas_numero'         => null,
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), ngoEntityPayload());

    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->firstOrFail();

    expect($contract->content)->not->toContain('QUALIFICACOES DA OSC');
});
